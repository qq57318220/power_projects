<?php

namespace app\admin\controller\ledger;

use app\admin\controller\general\Config;
use app\common\controller\Backend;
use app\admin\model\warning\Plate;
use app\admin\model\ledger\Ledger as LedgerModel;
use fast\Tree;
use Endroid\QrCode\QrCode;
use think\Db;
use app\admin\model\Admin;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 台账管理
 *
 * @icon fa fa-circle-o
 */
class Ledger extends Backend
{
    
    /**
     * Ledger模型对象
     * @var \app\admin\model\ledger\Ledger
     */
    protected $model = null;
    //protected $dataLimit = 'auth';
    //protected $dataLimitField = 'merchant_id';
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ledger\Ledger;

        $groupList = collection(Plate::select())->toArray();

        Tree::instance()->init($groupList);
        $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        $result = getTree($result, 0, 0);
        foreach ($result as $key => $value){
            if($value['level'] > 1){
                unset($result[$key]);
            }
        }

        $groupName = [0 => __('None')];
        foreach ($result as $k => $v)
        {
            $groupName[$v['id']] = $v['name'];
        }

        $this->view->assign('groupName', $groupName);
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                //dump($this->request->request('keyField'));exit;
                return $this->selectLedger();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['admin','user','powercustomer','powermechanism','powerproduct'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['admin','user','powercustomer','powermechanism','powerproduct'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                unset($row['point']);
                $row->getRelation('admin')->visible(['username']);
				$row->getRelation('user')->visible(['nickname']);
				$row->getRelation('powercustomer')->visible(['name']);
				$row->getRelation('powermechanism')->visible(['name']);
				$row->getRelation('powerproduct')->visible(['name']);
				$row['name'] = $row['device_name'];
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $order_product_id = input('order_id');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $order_product_id = $params['order'];
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
//                halt($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $admin_row = Admin::where('id', $this->auth->id)->find();

//                    $params['merchant_id'] = DB::table('wgc_admin')->where('id', $this->auth->id)->value('merchant_id');
                    $params['create_time'] = time();
                    //台账编号
                    $params['ledger_number'] = $order_product_id.time();

                    //获取设备号、设备编号、订单号、购买人id
                    $order_prodcut_row = Db::table('wgc_shop_order_product')
                        ->alias('p')
                        ->join('wgc_shop_order o', 'o.id = p.order_id')
                        ->where('o.id', $order_product_id)
                        ->field('p.name,p.product_sn,o.order_sn,o.buyer_id,p.ledger_input_status,p.id,o.merchant_id')
                        ->find();
                    if($order_prodcut_row['ledger_input_status'] == 1){
                        $this->error('已经录入过了');
                    }
                    if(empty($order_prodcut_row)){
                        $this->error('暂无商品台账！');
                    }
                    $params['order'] = $order_prodcut_row['order_sn'];
                    $params['device_name'] = $order_prodcut_row['name'];
                    $params['device_number'] = $order_prodcut_row['product_sn'];
                    $params['user_id'] = $order_prodcut_row['buyer_id'];
                    $params['merchant_id'] = $order_prodcut_row['merchant_id'];
//                    halt($params['merchant_id']);

                    //台账二维码生成
                    $code_content = 'www.baidu.com';
                    $ledger_number = $params['ledger_number'];
                    $username = $admin_row['username'];
                    $install_address = $params['install_address'];
                    $new_path = ledgerQRcode($code_content, $ledger_number, $username, $install_address);
                    $params['ledger_qrcode'] = $new_path;

                    //经纬度
                    $ak = \think\Config::get('amapAk');//'33efbc883fe11d1736efceffc876c86e';
                    $address = $params['install_address'];
                    $city = '';
                    $url ="https://restapi.amap.com/v3/geocode/geo?output=JSON&address=".$address."&city=".$city."&key=".$ak;
                    $data = file_get_contents($url);
                    $data = json_decode($data, true);

                    if($data['status'] == 1){
                        if(empty($data['geocodes'])){
                            $this->error('安装地址有误，查询不到相关地址!');
                        }
                        $location = $data['geocodes'][0]['location'];
                        $location_arr = explode(',', $location);
                        $params['longitude'] = $location_arr[0];
                        $params['latitude'] = $location_arr[1];
                    }
//                    halt($params);
                    $result = $this->model->allowField(true)->save($params);
                    if($result){
                        $ledger_new_id = $this->model->getLastInsID();
                        Db::table('wgc_ledger_authorize')->insert([
                            'merchant_id' => $this->auth->id,
                            'device_id' => $ledger_new_id,
                            'user_id' => $order_prodcut_row['buyer_id'],
                            'type' => 1,
                            'create_time' => time()
                        ]);
                        Db::table('wgc_shop_order_product')->where('id', $order_prodcut_row['id'])->update(['ledger_input_status' => 1]);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('order_product_id',$order_product_id);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $admin_row = Admin::where('id', $this->auth->id)->find();

                    //台账二维码生成
                    $code_content = 'www.baidu.com';
                    $ledger_number = $row['ledger_number'];
                    $username = $admin_row['username'];
                    $install_address = $params['install_address'];
                    $new_path = ledgerQRcode($code_content, $ledger_number, $username, $install_address);
                    $params['ledger_qrcode'] = $new_path;

                    //经纬度
                    $ak = \think\Config::get('amapAk');//'33efbc883fe11d1736efceffc876c86e';
                    $address = $params['install_address'];
                    $city = '';
                    $url ="https://restapi.amap.com/v3/geocode/geo?output=JSON&address=".$address."&city=".$city."&key=".$ak;
                    $data = file_get_contents($url);
                    $data = json_decode($data, true);
                    if($data['status'] == 1){
                        $location = $data['geocodes'][0]['location'];
                        $location_arr = explode(',', $location);
                        $params['longitude'] = $location_arr[0];
                        $params['latitude'] = $location_arr[1];
                    }else{
                        $params['longitude'] = '';
                        $params['latitude'] = '';
                    }

                    $result = $row->allowField(true)->save($params) !== false;
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result) {
                    $this->success();
                } else {
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids){
        $row = $this->model->get($ids);


        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}

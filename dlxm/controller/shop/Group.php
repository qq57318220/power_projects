<?php

namespace app\admin\controller\shop;

use app\common\controller\Backend;
use app\admin\controller\Common;
use think\Db;
use app\admin\model\shop\Prop;
use app\admin\model\shop\GroupProductProp;
use app\admin\model\shop\Product;

/**
 * 团购活动管理
 *
 * @icon fa fa-group
 */
class Group extends Backend
{
    
    /**
     * Group模型对象
     * @var \app\admin\model\shop\Group
     */
    protected $model = null;
    protected $common = null;
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shop\Group;
        $this->common = new \app\admin\controller\Common;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("autoSwitchList", $this->model->getAutoSwitchList());
    }


    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法a
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->with('merchant')
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function get_product_props(){
        $product_id = $this->request->param('product_id');

        $total = model('shop.product.prop')->where('product_id','=',$product_id)->count();

        $list = model('shop.product.prop')->where('product_id','=',$product_id)->select();
        foreach($list as $k=>$v){
            $attr = [];
            $temp = explode(';', $v['props']);
            foreach($temp as $tk=>$tv){
                $temp_t = explode(':', $tv);
                $attr[] = $temp_t[1];
//                foreach($temp_t as $wd=>$val){
//                    $attr[] = $val;
//                }
            }
            $list[$k]['attr'] = implode('-', model('shop.prop')->where('id','in', $attr)->column('name') );
        }
        $list = collection($list)->toArray();

        $result = array("total"=>$total,"rows" => $list);

        return json($result);
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = true;
                $this->model->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $params['min_price'] = $params['price'];
                    if($params['has_prop'] == 1){
                        $params['inventory'] = array_sum($this->request->only('inventory')['inventory']);
                    }
                    !$price = $this->request->only('price') or $params['min_price'] = min($price['price']);
                    $params['merchant_id'] = Product::getMchId($params['product_id']);
                    $result = $this->model->allowField(true)->create($params);
                    if($params['has_prop']){
                        $common = new Common;
                        $propData = $this->common->packPropData(['group_id'=>$result->id]);
                        GroupProductProp::insertAll($propData) or $result = false;
                    }
                    if ($result) {
                        $this->model->commit();
                        $this->success();
                    } else {
                        $this->model->rollback();
                        $this->error();
                    }
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('prop_list',collection(Prop::getListLv())->toArray());
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $row['product'] =model('shop.product')->find($row['product_id']);
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
                $result = true;
                $this->model->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $init_has_prop = $this->request->post('init_has_prop');
                    $where['group_id'] = $row['id'];

                    //原先设置有属性，当前保存没属性
                    if($init_has_prop==1 && $params['has_prop']==0){
                        GroupProductProp::where($where)->delete() or $result = false;
                    }
                    $params['min_price'] = $params['has_prop']?min($this->request->only('price')['price']):$params['price'];
                    if($params['has_prop'] == 1){
                        $params['inventory'] = array_sum($this->request->only('inventory')['inventory']);
                    }
                    $params['merchant_id'] = Product::getMchId($row['product_id']);
                    $row->allowField(true)->save($params) !== false or $result=false;
                    if($params['has_prop']){

                        $propData = $this->common->packPropData($where);
                        $ids = array_flip(GroupProductProp::where($where)->column('id'));
                        foreach ($propData as $v){
                            $where['props'] = $v['props'];
                            if($id = GroupProductProp::where($where)->value('id')){
                                GroupProductProp::where('id',$id)->update($v)!==false or $result = false;
                                unset($ids[$id]);
                            }else{
                                GroupProductProp::create($v) or $result = false;
                            }
                        }
                        if(!empty($ids)){
                            GroupProductProp::whereIn('id',array_flip($ids))->delete() or $result = false;
                        }

                    }

                    if ($result) {
                        $this->model->commit();
                        $this->success();
                    } else {
                        $this->model->rollback();
                        $this->error();
                    }
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->assign('prop_list',collection(Prop::getListLv(['*'],['merchant_id'=>$row['merchant_id']]))->toArray());
        list($product_prop_list,$prop_ids) = GroupProductProp::product_props($row['id']);
        $this->assign('prop_ids',$prop_ids);
        $this->assign('product_prop_list',$product_prop_list);
        return $this->view->fetch();
    }
}

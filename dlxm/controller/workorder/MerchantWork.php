<?php

namespace app\admin\controller\workorder;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\technician\Technician;

/**
 * 电话工单管理
 *
 * @icon fa fa-circle-o
 */
class MerchantWork extends Backend
{
    
    /**
     * TechnicianWork模型对象
     * @var \app\admin\model\workorder\MerchantWork
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\workorder\TechnicianWork;
        $this->view->assign("completeTypeList", $this->model->getCompleteTypeList());
        $this->view->assign("workorderTypeList", $this->model->getWorkorderTypeList());
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
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['admin','user','ledger'])
                    ->where('technician_work.technician_id', '>', 0)
                    ->where('launch_type',3)
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['admin','user','ledger'])
                    ->where($where)
                    ->where('launch_type',3)
                    ->where('technician_work.technician_id', '>', 0)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','merchant_id','user_id','ledger_id','technician_id','complete_type','workorder_type','device_name','province','city','district','address','remark','workorder_price','workorder_update_price','now_workorder_price','user_name','user_phone','launch_type','launch_time','technician_handle_status','handle_status','merchant_phone','quote_time','quote_image','revoke_person','revoke_time','revoke_reason','technician_type','technician_name','technician_phone','receipt_time','repair_time','price_revision_time','complete_time','bill_of_materials','','mechanism','customer_type']);
                $row->visible(['admin']);
                $row->getRelation('admin')->visible(['nickname']);
                $row->visible(['user']);
                $row->getRelation('user')->visible(['nickname']);
                $row->visible(['ledger']);
                $row->getRelation('ledger')->visible(['device_name']);
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
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $area = explode('/',$params['city']);
                    count($area) == 3 or $this->error('请选择完整的地址');
                    list($params['province'],$params['city'],$params['district']) = $area;
                    $technician = Technician::field('name,phone')->where('id',$params['technician_id'])->find();
                    $params['technician_name'] = $technician['name'];
                    $params['technician_phone'] = $technician['phone'];
                    $params['launch_time'] = time();
                    $params['launch_type'] = 3;
                    $params['launch_role'] = 1;
                    $params['quote_time'] = time();
                    $params['merchant_id'] = $this->auth->id;
                    if($params['handle_status'] == 5){
                        $params['receipt_time'] = time();
                    }elseif($params['handle_status'] == 8){
                        $params['complete_time'] = time();
                        $params['complete_type'] = 3;
                    }
                    $result = $this->model->allowField(true)->save($params);

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
                    Db::commit();
                    $this->success();
                } else {
                    Db::rollback();
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('tel',db('Merchant')->where('id',$this->auth->id)->value('tel'));
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
                    $area = explode('/',$params['city']);
                    count($area) == 3 or $this->error('请选择完整的地址');
                    list($params['province'],$params['city'],$params['district']) = $area;
                    $technician = Technician::field('name,phone')->where('id',$params['technician_id'])->find();
                    $params['technician_name'] = $technician['name'];
                    $params['technician_phone'] = $technician['phone'];
                    $params['launch_time'] = time();
                    $params['launch_type'] = 3;
                    $params['launch_role'] = 1;
                    $params['quote_time'] = time();
                    $params['merchant_id'] = $this->auth->id;
                    if($params['handle_status'] == 5){
                        $params['receipt_time'] = time();
                    }elseif($params['handle_status'] == 8){
                        $params['complete_time'] = time();
                        $params['complete_type'] = 3;
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

    public function detail($ids){
        $row = $this->model->get($ids);

        $this->view->assign('row', $row);
        if($row['technician_handle_status'] == 1){
            return $this->view->fetch('waitTechnicalRes');
        }else if($row['technician_handle_status'] == 2){
            return $this->view->fetch('technicalRes');
        }else if($row['technician_handle_status'] == 4){
            return $this->view->fetch('complete');
        }else if($row['technician_handle_status'] == 3){
            return $this->view->fetch('userRefuseOffer');
        }
    }
}

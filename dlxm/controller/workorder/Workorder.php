<?php

namespace app\admin\controller\workorder;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\technician\Technician;
use app\admin\model\Information;
use app\admin\model\member\User as UserModel;
use app\admin\model\member\UserMoneyLog;
use app\admin\model\WorkorderLog;
use app\admin\model\ledger\Journal;

/**
 * 工单管理
 *
 * @icon fa fa-circle-o
 */
class Workorder extends Backend
{

    /**
     * Workorder模型对象
     * @var \app\admin\model\workorder\Workorder
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\workorder\Workorder;
        $this->view->assign("workorderTypeList", $this->model->getWorkorderTypeList());
        $this->view->assign("completeTypeList", $this->model->getCompleteTypeList());
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

            //判端发起时间是否超过24小时
            $time = time() - 60*60*24;
            $this->model->merchantNoRes($time, 1);
            //判断技术接单时间是否超过24小时
            $this->model->merchantNoRes($time, 2);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if($this->auth->merchant_id == 1){
                $where = '';
            }
            $total = $this->model
                ->with(['merchant'])
                ->where('launch_type','neq',3)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['merchant'])
                ->where('launch_type','neq',3)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row->visible(['id','admin_id','ledger_id','device_id','workorder_type','device_name','city','address','remark','workorder_price','workorder_update_price','now_workorder_price','user_name','user_phone','launch_type','launch_role','refund_status','launch_time','handle_status','complete_type','bill_of_materials']);
                $row->visible(['merchant']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);
            //dump($result);
            return json($result);
        }
        //dump("aaa");
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
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $area = explode('/',$params['city']);
                    list($params['province'],$params['city'],$params['district']) = $area;
                    //发行类型
                    $params['launch_type'] = 1;
                    //处理情况状态
                    $params['handle_status'] = 1;
                    //完成状态
                    $params['complete_type'] = 1;

                    $result = $this->model->allowField(true)->save($params);
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
        return $this->view->fetch();
    }

    /**
     * 商家响应
     */
    public function untreated($ids){
        $row = $this->model->get($ids);

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
                        $row->validateFailException(true)->validate($validate);
                    }

                    //报价时间
                    $params['quote_time'] = time();
                    //处理状态
                    $params['handle_status'] = 2;
                    //修改的工单价格
                    $params['workorder_update_price'] = $params['workorder_price'];
                    unset($params['workorder_price']);

                    $result = $row->allowField(true)->save($params);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 分配技术员
     */
    public function distribution($ids){
        $row = $this->model->get($ids);

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $radio = $this->request->post("radio");

            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                	if(empty($params['technician_id_a']) && empty($params['technician_id_b'])){
                		throw new Exception('必填人员');
	                }
                	if($radio == 'aa'){
						$params['technician_id'] = $params['technician_id_a'];
	                }else if($radio == 'bb'){
		                $params['technician_id'] = $params['technician_id_b'];
	                }
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    //获取技术员的名字与号码
                    $technician_arr = Technician::find($params['technician_id']);
                    $params['technician_name'] = $technician_arr['name'];
                    $params['technician_phone'] = $technician_arr['phone'];
                    //处理状态
                    $params['handle_status'] = 4;
                    //技术员处理状态
                    $params['technician_handle_status'] = 1;
                    //技术员类型
                    if($this->auth->id == 1){
                        $params['technician_type'] = 1;
                    }else{
                        $params['technician_type'] = 2;
                    }
                    $arr[] = array(
                        'user_id'   => Technician::where('id',$params['technician_id'])->value('user_id'),
                        'work_id'   => $row['id'],
                        'type'  => 1,
                        'content'   => '您收到新的派单，请及时处理',
                        'cate'  => 1,
                        'create_time'   => time()
                    );
                    $arr[] = array(
                        'user_id'   => $row['user_id'],
                        'work_id'   => $row['id'],
                        'type'  => 0,
                        'content'   => '您的工单有新的反馈，请注意查看',
                        'cate'  => 1,
                        'create_time'   => time()
                    );

                    Information::insertAll($arr);
                    $result = $row->allowField(true)->save($params);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $merchant_id = Db::name('admin')->where(['id'=>$this->auth->id])->value('merchant_id');
        $this->view->assign('merchant_id',$merchant_id);
        $this->view->assign('row', $row);
        $this->assign('workorder_update_price',$row['workorder_update_price'] ?$row['workorder_price']:null);
        return $this->view->fetch();
    }

    public function detail($ids){
        $row = $this->model->get($ids);
        $row['bill_of_materials'] = Journal::where('work_id',$row['id'])->value('bill_of_materials');
        $this->view->assign('row', $row);

        if($row['handle_status'] == 4){
            return $this->view->fetch('waitTechnicalRes');
        }else if($row['handle_status'] == 5){
            return $this->view->fetch('technicalRes');
        }else if($row['handle_status'] == 8 || $row['handle_status'] == 7){
            return $this->view->fetch('complete');
        }else if($row['handle_status'] == 2){
            return $this->view->fetch('merchantResDetail');
        }else{
            return $this->view->fetch('merchantResDetail');
        }
    }

    public function refund($ids){
        $row = $this->model->get($ids);
        if($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if($params['refund_money'] <= 0){
                    $this->error('退款金额必须大于0！');
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params['refund_status'] = 1;
                    $params['refund_time'] = time();

                    $before_money = UserModel::where('id',$row['user_id'])->value('money');
                    $after_money = $before_money + $params['refund_money'];
                    UserModel::plusMoney($row['user_id'],$params['refund_money']);
                    UserMoneyLog::insertData($row['user_id'],1,7,$params['refund_money'],$before_money,$after_money);
                    WorkorderLog::insertData($row['user_id'],$row['id'],1,3,$params['refund_money'],$before_money,$after_money);
                    $result = $row->allowField(true)->save($params);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }else{

            $this->view->assign('row', $row);
            return $this->view->fetch('userRefuseOffer');
        }
    }

    //日志详情
    public function log($ids){
        $row = Db::name('ledger_journal')->where(['work_id'=>$ids])->find();

        $this->assign('row',$row);
        return $this->view->fetch();
    }



}

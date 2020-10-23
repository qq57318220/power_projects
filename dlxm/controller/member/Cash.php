<?php

namespace app\admin\controller\member;

use app\admin\model\member\UserMoneyLog;
use app\common\controller\Backend;
use Think\Db;
use app\admin\model\member\User;

/**
 * 零钱提现管理
 *
 * @icon fa fa-circle-o
 */
class Cash extends Backend
{
    
    /**
     * Cash模型对象
     * @var \app\admin\model\member\Cash
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\member\Cash;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
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
                ->with('user')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 提现审核
     */
    public function check($ids){
        $row = $this->model->where('id',$ids)->find();
        $row->status == 1 or $this->error('已审核');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            in_array($params['status'],[2,4]) or die();
            Db::startTrans();
            $params = array_merge($params,['checker_admin_id'=>$this->auth->id,'checker_admin_name'=>$this->auth->username,'checktime'=>date('Y-m-d H:i:s')]);
            $bool = $row->save($params);
            if($bool && $params['status'] == 4){
               $user = User::where('id',$row->user_id)->find(); //退还零钱
                $user->money += $row->money;
                $user->save() or $bool = false;
                UserMoneyLog::insertData($user->id,1,3,$row->money,$user->money - $row->money,$user->money) or $bool = false;;
            }
            if($bool){
                Db::commit();
                $this->success('审核成功');
            }else{
                Db::rollback();
                $this->error('审核失败');
            }

        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 完成付款
     */
    public function pay($ids){
        $row = $this->model->where('id',$ids)->find();
        $row->status == 2 or $this->error('已完成付款');
        if ($this->request->isPost()) {
            $params = ['payer_admin_id'=>$this->auth->id,'payer_admin_name'=>$this->auth->username,'paytime'=>date('Y-m-d H:i:s'),'status'=>3];
            $row->save($params)?$this->success('操作成功'):$this->error('操作失败');
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}

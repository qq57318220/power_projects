<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class UpbagOrders extends Backend
{
    
    /**
     * UpbagOrders模型对象
     * @var \app\admin\model\UpbagOrders
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\UpbagOrders;
        $this->view->assign("orderTypeList", $this->model->getOrderTypeList());
        $this->view->assign("isLockList", $this->model->getIsLockList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
        $this->view->assign("shippingMentList", $this->model->getShippingMentList());
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
                    ->with(['shoppaid','upbagorder','merchant','user'])
                    ->where($where)
                    ->where('order_type',3)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['shoppaid','upbagorder','merchant','user'])
                    ->where($where)
                    ->where('order_type',3)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','order_sn','buyer_id','tuan_id']);
                $row->visible(['shoppaid']);
				$row->getRelation('shoppaid')->visible(['pay_amount','payment_name','pay_status']);
				$row->visible(['upbagorder']);
				$row->getRelation('upbagorder')->visible(['grade','create_time']);
				$row->visible(['merchant']);
				$row->getRelation('merchant')->visible(['name']);
				$row->visible(['user']);
				$row->getRelation('user')->visible(['nickname','avatar']);

            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}

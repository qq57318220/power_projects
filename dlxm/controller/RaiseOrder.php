<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 团购订单管理
 *
 * @icon fa fa-circle-o
 */
class RaiseOrder extends Backend
{
    
    /**
     * RaiseOrder模型对象
     * @var \app\admin\model\RaiseOrder
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\RaiseOrder;
        $this->view->assign("statusList", $this->model->getStatusList());
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
                    ->with(['merchant','user','raise'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['merchant','user','raise'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','status','total','pay_total','pay_num','last_time','raise_id','updatetime','createtime']);
                $row->visible(['merchant']);
				$row->getRelation('merchant')->visible(['name']);
				$row->visible(['user']);
				$row->getRelation('user')->visible(['nickname','avatar']);
				$row->visible(['raise']);
				$row->getRelation('raise')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function detail($id)
    {
        $row = $this->model->with('user')->where('raise_order.id',$id)->find();
        $list = \app\admin\model\RaiseOrderPaid::with(['user'])->where('raise_order_id',$id)->where('pay_status',2)->select();
        $this->view->assign('list',$list);
        $this->view->assign('row',$row);
        return $this->view->fetch();
    }
}

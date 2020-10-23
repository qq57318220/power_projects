<?php

namespace app\admin\controller\shop;

use app\common\controller\Backend;
use app\admin\model\shop\Tuan;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class TuanOrder extends Backend
{
    
    /**
     * TuanOrder模型对象
     * @var \app\admin\model\TuanOrder
     */
    protected $model = null;
    protected $searchFields = 'id,order_sn';

    protected $mch_id = 'merchant_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\TuanOrder;
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
                    ->with(['shoptuan','user'])
                    ->where($where)
                    ->where(['order_type'=>2])
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['shoptuan','user','merchant'])
                    ->where($where)
                    ->where(['order_type'=>2])
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','order_sn','tuan_id','pay_status','payment_name','pay_amount','merchant_id']);

                $row->visible(['shoptuan']);
				$row->getRelation('shoptuan')->visible(['status','group_size','join_num','pay_num','auto_switch','last_time']);

				$row->visible(['user']);
				$row->getRelation('user')->visible(['nickname','avatar']);

                $row->visible(['merchant']);
                $row->getRelation('merchant')->visible(['name']);
            }
            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 提前成团
     */
    public function before_tuan($id){
       $info =  Tuan::where('id',$id)->find();
       $info->status == 1 or $this->error('不符合成团条件');
       $info->status = 2;
       return $info->save()?$this->success('操作成功'):$this->error('操作失败');
    }


}

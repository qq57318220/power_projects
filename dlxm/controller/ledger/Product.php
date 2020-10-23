<?php

namespace app\admin\controller\ledger;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Product extends Backend
{
    
    /**
     * Product模型对象
     * @var \app\admin\model\ledger\Product
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ledger\Product;
        $this->view->assign("refundMarkList", $this->model->getRefundMarkList());
        $this->view->assign("isAsList", $this->model->getIsAsList());
        $this->view->assign("isCommentList", $this->model->getIsCommentList());
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
                    ->with(['shoporder'])
                    ->where($where)
                    ->where('shoporder.pay_status', 2)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['shoporder'])
                    ->where($where)
                    ->where('shoporder.pay_status', 2)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','merchant_id','order_id','name','props_name','ledger_input_status','product']);
                $row->visible(['shoporder']);
				$row->getRelation('shoporder')->visible(['order_sn']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

}

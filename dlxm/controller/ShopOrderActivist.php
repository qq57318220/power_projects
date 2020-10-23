<?php

namespace app\admin\controller;

use app\admin\model\ShopOrderLog;
use app\admin\model\ShopOrderProduct;
use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 维权管理
 *
 * @icon fa fa-circle-o
 */
class ShopOrderActivist extends Backend
{
    
    /**
     * ShopOrderActivist模型对象
     * @var \app\admin\model\ShopOrderActivist
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ShopOrderActivist;
//        halt($this->model->getWqStatusList());
        $this->view->assign("wqStatusList", $this->model->getWqStatusList());
        $this->view->assign("wqTypeList", $this->model->getWqTypeList());
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
                    ->with(['user','merchant','shoporderproduct'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','merchant','shoporderproduct'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','wq_status','wq_type','wq_reason','apply_refund','number','createtime']);
                $row->visible(['user']);
				$row->getRelation('user')->visible(['nickname','avatar']);
				$row->visible(['merchant']);
				$row->getRelation('merchant')->visible(['name']);
				$row->visible(['shoporderproduct']);
				$row->getRelation('shoporderproduct')->visible(['name','props_name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    //通过
    public function pass()
    {

    }
    //驳回
    public function reject($ids)
    {
        Db::startTrans();
        try{
            $result[] = $this->model->where('id','=',$ids)->update(['as_status'=>5]);
            //更新wgc_shop_order_product的return_number
            $res = $this->model->where('id','=',$ids)->find()->toArray();
            $orderProductModel = new ShopOrderProduct();
            $result[] = $orderProductModel->where('id','=',$res['order_product_id'])->dec('return_number',$res['number'])->update();
            $info = $this->model->where('id','=',$ids)->find();
            $admin = $this->auth->getUserInfo();
            $result[] = ShopOrderLog::insertLog($info['order_id'],8,'驳回维权申请',$admin['id'],$admin['username']);
            if(checkRes($result)){
                Db::commit();
                $this->success('操作成功');
            }else{
                Db::rollback();
                $this->error('操作失败');
            }
        }catch (Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }

    }
}

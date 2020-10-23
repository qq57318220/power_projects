<?php

namespace app\admin\controller;

use app\admin\model\member\UserMoneyLog;
use app\admin\model\shop\Product;
use app\admin\model\shop\product\Prop;
use app\admin\model\ShopOrderProduct;
use app\admin\model\User;
use app\api\model\AfterSaleAddress;
use app\api\model\shop\OrderProduct;
use app\api\model\shop\Paid;
use app\common\controller\Backend;
use app\common\library\Wechat;
use think\Db;
use think\Exception;
use app\admin\model\member\User as UserModel;
use app\admin\model\ShopOrderLog;
use app\admin\model\member\UserScoreLog;

/**
 * 售后管理
 *
 * @icon fa fa-circle-o
 */
class AfterSale extends Backend
{
    
    /**
     * AfterSale模型对象
     * @var \app\admin\model\AfterSale
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\AfterSale;
        $this->view->assign("asStatusList", $this->model->getAsStatusList());
        $this->view->assign("asTypeList", $this->model->getAsTypeList());
        $this->view->assign("refundStatusList", $this->model->getRefundStatusList());
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
            $has_data = $this->model->count();
            if($has_data){
                $total = $this->model
                    ->with(['user','shoporderproduct','address'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

                $list = $this->model
                    ->with(['user','shoporderproduct','address','merchant'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

                foreach ($list as $row) {
                    $row->getRelation('user')->visible(['nickname','avatar']);
                    $row->getRelation('shoporderproduct')->visible(['name','props_name']);
                    $row->getRelation('address')->visible(['province','city','district','detail_address']);
                }
                $list = collection($list)->toArray();
            }else{
                $total = 0;
                $list = [];
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    //审核通过 as_type = 1 or 3 的售后单
    public function examine($ids){
        $info = $this->model->where('id','=',$ids)->find();
        if($this->request->isAjax()){
            Db::startTrans();
            try{
                $order_product_model = new OrderProduct();
                $order_product_info = $order_product_model->where('id','=',$info['order_product_id'])->find();
                $refund_score = floor($order_product_info->score*$info['number']/$order_product_info->number);//本次需要退的积分数
                $refund_gold = floor($order_product_info->total_gold*$info['number']/$order_product_info->number);//本次需要退的购物币
                if(in_array($info['as_type'],[1,3])){
                    if( ($order_product_info['return_number_agree'] + $info['number']) == $order_product_info['number'] ){
                        $other_order_product_list = $order_product_model
                            ->where('order_id','=',$info['order_id'])
                            ->where('id','<>',$info['order_product_id'])
                            ->where('`return_number_agree` < `number`')
                            ->select();
                        $other_order_product_list = collection($other_order_product_list)->toArray();
                        if(!$other_order_product_list){
                            $order_model = new \app\admin\model\ShopOrder();
                            $result[] = $order_model->where('id','=',$info['order_id'])->update(['status'=>6]);
                        }
                        $result[] = $order_product_model
                            ->where('id','=',$info['order_product_id'])
                            ->inc('return_number_agree',$info['number'])
                            ->update(['refund_mark'=>2,'refund_score'=>$order_product_info['refund_score']+$refund_score]);
                    }else{
                        $result[] = $order_product_model
                            ->where('id','=',$info['order_product_id'])
                            ->inc('return_number_agree',$info['number'])
                            ->update(['refund_mark'=>1,'refund_score'=>$order_product_info['refund_score']+$refund_score]);
                    }
                }

                if($info['as_type'] == 1){
                    if($order_product_info['props']){
                        $product_prop_model = new Prop();
                        $result[] = $product_prop_model
                            ->where('product_id','=',$order_product_info['product_id'])
                            ->where('props','=',$order_product_info['props'])
                            ->inc('inventory',$info['number'])->update();
                    }else{
                        $product_model = new Product();
                        $result[] = $product_model
                            ->where('id','=',$order_product_info['product_id'])
                            ->inc('inventory',$info['number'])->update();
                    }
                }

                $update = [];
                if(in_array($info['as_type'],[1,3])){
                    $update['as_status'] = 3;
                    $update['refund_score'] = $refund_score;//退回的积分
                    $update['refund_gold'] = $refund_gold;//退回的购物币

                }elseif($info['as_type'] == 2){
                    $update['as_status'] = 2;
                }
                $update['refund_total'] = $this->request->param('refund_total');
                $result[] = $this->model->where('id','in',$ids)->update($update);
                $admin = $this->auth->getUserInfo();
                $result[] = ShopOrderLog::insertLog($info['order_id'],7,'审核通过，同意退款',$admin['id'],$admin['username']);
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
        $this->view->assign("row", $info);
        return $this->view->fetch();
    }

    //审核通过as_type=2的售后单
    public function examine_2($ids){
        $update['as_status'] = 2;
        $result = $this->model->where('id','in',$ids)->update($update);
        if($result){
            Db::commit();
            $this->success('操作成功');
        }else{
            Db::rollback();
            $this->error('操作失败');
        }
    }

    //驳回
    public function reject($ids){
        Db::startTrans();
        try{
            $result[] = $this->model->where('id','=',$ids)->update(['as_status'=>5]);
            //更新wgc_shop_order_product的return_number
            $res = $this->model->where('id','=',$ids)->find()->toArray();
            $orderProductModel = new ShopOrderProduct();
            $result[] = $orderProductModel->where('id','=',$res['order_product_id'])->dec('return_number',$res['number'])->update();
            $info = $this->model->where('id','=',$ids)->find();
            $admin = $this->auth->getUserInfo();
            $result[] = ShopOrderLog::insertLog($info['order_id'],8,'驳回售后申请',$admin['id'],$admin['username']);
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

    //退款
    public function refund($ids){
        if($this->request->isAjax()){
            $row = $this->model->where('id',$ids)->find();
            $order_model = new \app\admin\model\ShopOrder();
            $order_info = $order_model->where('id',$row['order_id'])->find();
            if(empty($order_info['transaction_id'])){
                $order_info['out_trade_no'] = Paid::where('id',$order_info['paid_id'])->value('out_trade_no');
            }
            $refund_res = true;
            if($refund_res){
                Db::startTrans();
                $after_sale_model = new \app\admin\model\AfterSale();
                $update_data['refund_status'] = 2;
                $update_data['refund_total'] = $row['refund_total'];
                $update_data['as_status'] = 4;
                $update_data['refundtime'] = time();
                $result[] = $after_sale_model->where('id','=',$ids)->update($update_data);

                $after_sale_info = $after_sale_model->where('id','=',$ids)->find();
                $order_product_model = new OrderProduct();
                $result[] = $order_product_model->where('id','=',$after_sale_info['order_product_id'])->update(['refund_total'=>$update_data['refund_total']])!==false;
                $user = UserModel::where('id',$row->user_id)->find();
                //退积分
                if($after_sale_info['refund_score'] > 0){
                    $result[] = UserModel::plusScore($user->id,$after_sale_info['refund_score']);
                    $result[] = UserScoreLog::insertData($user->id,1,$after_sale_info['refund_score'],$user->score,$user->score+$after_sale_info['refund_score'],$order_info['order_sn']);
                }
                //退购物币
                if($after_sale_info['refund_gold'] > 0){
                    $result[] = UserModel::plusGold($user->id,$after_sale_info['refund_gold']);
                    $result[] = \app\admin\model\UserGoldLog::insertData($user->id,1,$after_sale_info['refund_gold'],$user->gold+$after_sale_info['refund_gold'],'退货退款，返还购物币');
                }
                if(checkRes($result)){
                    $bool = true;
                    if(in_array($order_info['payment_id'],[1,2])){
                        $bool = Wechat::refund($order_info['out_trade_no'],$row['refund_sn'],$order_info['pay_amount'],$row['refund_total']);
                    }elseif($order_info['payment_id'] == 3){
                        //退款到零钱
                        $bool = UserModel::plusMoney($row->user_id,$row['refund_total']);
                        UserMoneyLog::insertData($row->user_id,1,5,$row['refund_total'],$user->money,$user->money+$row['refund_total'],$order_info['order_sn']) or $bool = false;
                    }elseif($order_info['payment_id'] == 7){
                        //退款到余额
                        $bool = UserModel::plusAmount($row->user_id,$row['refund_total']);
                        \app\admin\model\UserAmountLog::insertData($row->user_id,1,$row['refund_total'],'退货') or $bool = false;
                    }
                    $bool?Db::commit():Db::rollback();
                    $info = $this->model->where('id','=',$ids)->find();
                    $admin = $this->auth->getUserInfo();
                    $result[] = ShopOrderLog::insertLog($info['order_id'],10,'完成售后',$admin['id'],$admin['username']);
                    $bool?$this->success('操作成功'):$this->error('操作失败');
                }else{
                    Db::rollback();
                    $this->error('操作失败');
                }
            }else{
                $this->error('操作失败');
            }
        }
    }

    //发货
    public function send_goods($ids){
        $as_address_model = new AfterSaleAddress();
        if($this->request->isAjax()){
            Db::startTrans();
            $post = $this->request->param('row/a');
            $data['shipping_company_id'] = $post['shipping_company_id'];
            $data['shipping_company_name'] = Db::table('wgc_shipping_company')->where('id','=',$post['shipping_company_id'])->value('name');
            $data['shipping_code'] = $post['shipping_code'];
            $data['consignee'] = $post['consignee'];
            $data['phone'] = $post['phone'];
            $data['province'] = $post['province'];
            $data['city'] = $post['city'];
            $data['district'] = $post['district'];
            $data['detail_address'] = $post['detail_address'];
            if($post['id']){
                $result[] = $as_address_model->where('id','=',$post['id'])->update($data);
            }else{
                $result[] = $as_address_model->insert($data);
            }
            $result[] = $this->model->where('id','=',$ids)->update(['as_status'=>4]);
            if(checkRes($result)){
                Db::commit();
                $this->success('操作成功');
            }else{
                Db::rollback();
                $this->error('操作失败');
            }
        }
        $as_info = $this->model->get($ids);
        $row = $as_address_model->where('as_id','=',$as_info['id'])->find();
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //设为已完成
    public function set_finish($ids){
        $result = $this->model->where('id','in',$ids)->update(['as_status'=>4]);
        if($result){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
}

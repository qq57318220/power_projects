<?php

namespace app\admin\controller;

use app\admin\model\member\UserMoneyLog;
use app\admin\model\ShopOrderLog;
use app\api\model\shop\OrderModel;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\api\model\shop\OrderProduct;
use app\api\model\shop\OrderShipping;
use app\api\model\shop\Reward;
use app\admin\model\AfterSale;
use app\admin\model\member\User as UserModel;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class ShopOrder extends Backend
{
    
    /**
     * ShopOrder模型对象
     * @var \app\admin\model\ShopOrder
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ShopOrder;
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
                    ->with(['user','paid','merchant'])
                    ->where($where)
                    ->where('is_lock','=',1)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','paid','merchant'])
                    ->where($where)
                    ->where('is_lock','=',1)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','order_sn','tuan_id','shipping_ment','buyer_id','order_type','status','dowm_reason','payment_name','pay_status','coupon_total','order_amount','freight_amount','pay_amount','reward_amount','total_gold','gold_money','createtime']);
                $row->visible(['user']);
                $row->visible(['paid']);
                $row->visible(['merchant']);
				$row->getRelation('user')->visible(['nickname','avatar']);
				$row->getRelation('merchant')->visible(['name','logo']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 发货
     */
    public function send_goods($ids){
        $row = Db::table('wgc_shop_order_shipping')->where('order_id','in',$ids)->find();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                Db::startTrans();
                try{
                    $data = $params;
                    $data['shipping_company_name'] = Db::table('wgc_shipping_company')->where('id','=',$params['shipping_company_id'])->value('name');
                    $res = Db::table('wgc_shop_order_shipping')->where('order_id','in',$ids)->update($data);
                    $result[] = ($res === false) ? false : true;
                    $res_order = Db::table('wgc_shop_order')->where('id','in',$ids)->update(['status'=>3,'send_time'=>time()]);
                    $result[] = ($res_order === false) ? false : true;
                    $admin = $this->auth->getUserInfo();
                    $result[] = ShopOrderLog::insertLog($ids,1,['物流公司'=>$data['shipping_company_name'],'物流单号'=>$data['shipping_code']],$admin['id'],$admin['username']);
                    $order = OrderModel::with('orderProduct')->where('id',$ids)->find()->toArray();
                    //发通知
                    $result[] = \app\admin\model\UserNotification::pushNoti('1',$order['buyer_id'],'您的订单已发货',$order['order_product'][0]['image'],$order['order_product'][0]['name'],'/ShowOrderDetail',$ids);
                     if (checkRes($result)) {
                        Db::commit();
                        $this->success('发货成功');
                    } else {
                        Db::rollback();
                        $this->error('发货失败');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    $this->error($e->getMessage());
                }
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    //设为待完成
    public function set_wait_finish($ids){
        Db::startTrans();
        try{
            $result[] = Db::table('wgc_shop_order')->where('id','in',$ids)->update(['status'=>4,'set_wait_finish_time'=>time()]);
            $admin = $this->auth->getUserInfo();
            $result[] = ShopOrderLog::insertLog($ids,2,[],$admin['id'],$admin['username']);
            if( checkRes($result) ){
                Db::commit();
                $this->success('更新成功');
            }else{
                Db::rollback();
                $this->error('更新失败');
            }
        }catch (Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    //设为已完成
    public function set_finish($ids){
        $admin = $this->auth->getUserInfo();
        $res = $this->model->set_finish($ids,$admin);
        if( $res['code'] ){
            $this->success($res['msg']);
        }else{
            $this->error($res['msg']);
        }
    }


    /**
     * 订单详情
     *@ApiSummary (胡正林)
     */
    public function order_detail($id){
        $row = $this->model->with('users,paid')->where('shop_order.id',$id)->find();
        $products = OrderProduct::where('order_id',$row->id)->select();
        $shipping = OrderShipping::where('order_id',$row->id)->find();
        $comments = \app\admin\model\ShopOrderComment::getCommentsByOrderId($row->id);
        //计算下平台收入
        $platformIncome = 0;
        $supply_price = 0;
        foreach($products as $k=>$v){
            $platformIncome +=  $v['price'] - $v['cost_price'] - $v['reward_money'];
            $supply_price += $v['supply_price'];
        }

        $this->assign([
            'platformIncome'=>$platformIncome,
            'supply_price'=>$supply_price,
        ]);
        $this->view->assign("row", $row);
        $this->view->assign("products", $products);
        $this->view->assign("shipping", $shipping);
        $this->view->assign("comments", $comments);
        $this->view->assign("logs", ShopOrderLog::getLogs($row->id));
        return $this->view->fetch();
    }


    /**
     * 返佣记录
     *@ApiSummary (胡正林)
     */
    public function reward_list($id){
        $this->view->assign("odInfo",$this->model->where('id',$id)->find());
        $this->view->assign("list", Reward::getListByOrderID($id));
        return $this->view->fetch();
    }

    /**
 * 修改价格
 *@ApiSummary (胡正林)
 */
    public function edit_payamount($ids){
        Db::startTrans();
        $odb = new \app\admin\model\ShopOrder();
        $row = $odb->where('id',$ids)->find();
        if($this->request->isPost()){
            $params = $this->request->post("row/a");
            if($row->pay_amount == $params['new_pay_amount']){
                $this->success('价格未修改');
            }
            $params['new_pay_amount'] > 0 or $this->error('输入的价格有误');
            $logCon = ['修改前'=>$row->pay_amount,'修改后'=>$params['new_pay_amount']];
            $row->pay_amount = $params['new_pay_amount'];
            $bool = $row->save();
            $admin = $this->auth->getUserInfo();
            ShopOrderLog::insertLog($ids,5,$logCon,$admin['id'],$admin['username']) or $bool = false;
            if($bool){
                Db::commit();
                $this->success('修改成功');
            }else{
                Db::rollback();
                $this->error('修改失败');
            }

        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 修改收货地址
     *@ApiSummary (胡正林)
     */
    public function edit_address($ids){
        Db::startTrans();
        $row = $this->model->where('id',$ids)->find() or $this->error('订单不存在');
        $shipping = OrderShipping::where('order_id',$row->id)->find();
        if($this->request->isPost()){
            $params = $this->request->post("row/a");
            $logCon = ['修改前'=>$shipping->consignee.','.$shipping->phone.','.$shipping->province.$shipping->city.$shipping->district.$shipping->detail_address];
            $bool = $shipping->where('id',$shipping->id)->update($params) !== false;
            $admin = $this->auth->getUserInfo();
            ShopOrderLog::insertLog($ids,6,$logCon,$admin['id'],$admin['username']) or $bool = false;
            if($bool){
                Db::commit();
                $this->success('修改成功');
            }else{
                Db::rollback();
                $this->error('修改失败');
            }

        }

        $this->view->assign("row", $row);
        $this->view->assign("shipping", $shipping);
        return $this->view->fetch();
    }

    /**
     * 主动发起退货
     *@ApiSummary (胡正林)
     */
    public function active_return($id){
        Db::startTrans();
        $row = OrderProduct::with('orderModel')->where('id',$id)->find() or $this->error('操作失败');
        if($this->request->isPost()){
            $params = $this->request->post("row/a");
            $params['number'] >= 1 && $params['number'] <= ($row->number - $row->return_number) or $this->error('退货数量有误');
            $params['refund_total'] >= 0 && $params['refund_total'] <= $row->pay_amount or $this->error('退款金额有误');
            $data = [
                'order_id'  =>  $row->order_id,
                'user_id'   =>  $row->orderModel->buyer_id,
                'order_product_id'  =>  $row->id,
                'as_status' =>  3,
                'as_type'   =>  3,
                'refund_status' =>  1,
                'refund_sn' =>  create_order_sn(),
                'shipping_ment' =>  3,
                'createtime' =>  time(),
            ];
            $params = array_merge($params,$data);
            $bool = true;
            $row->return_number +=  $params['number'];
            $row->return_number_agree +=  $params['number'];
            if($row->return_number_agree == $row->number){
                $row->refund_mark = 2;
                //更改订单状态
                $cou = OrderProduct::where('order_id',$row->order_id)->where('refund_mark','<>',2)->where('id','<>',$row->id)->count();
                if($cou == 0){
                    $row->orderModel->status = 6;
                    $row->orderModel->save() or $bool = false;
                }
            }else{
                $row->refund_mark = 1;
            }
            $params['refund_score'] = floor($row->score*$params['number']/$row->number);//本次需要退的积分数
            $params['refund_gold'] = floor($row->total_gold*$params['number']/$row->number);//本次需要退的购物币
            $row->refund_score +=  $params['refund_score'];
            $row->refund_gold +=  $params['refund_gold'];
            $as = AfterSale::create($params) or $bool = false;
            $row->save() or $bool = false;
            $admin = $this->auth->getUserInfo();
            $k = $row->name;
            if($row->props_name){
                $k .= ' - '.$row->props_name;
            }
            $v = '退款数量='.$params['number'].'，退款金额='.$params['refund_total'].'，退回积分='.$params['refund_score'].'，退回购物币='.$params['refund_gold'];
            ShopOrderLog::insertLog($row->order_id,11,[$k=>$v],$admin['id'],$admin['username']) or $bool = false;
            if($bool){
                Db::commit();
                $this->success('操作成功');
            }else{
                Db::rollback();
                $this->error('操作失败');
            }

        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }



}

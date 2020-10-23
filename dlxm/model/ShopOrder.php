<?php

namespace app\admin\model;

use app\admin\model\member\User as UserModel;
use app\admin\model\member\UserScoreLog;
use app\admin\model\member\UserMoneyLog;
use think\Db;
use think\Exception;
use think\Model;


class ShopOrder extends Model
{
    // 表名
    protected $name = 'shop_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'order_type_text',
        'is_lock_text',
        'status_text',
        'pay_status_text',
        'pay_time_text',
        'last_pay_time_text',
        'shipping_ment_text',
        'merchant_id'
    ];
    
    public function getOrderTypeList()
    {
        return ['1' => __('Order_type 1'), '2' => __('Order_type 2')];
    }

    public function getIsLockList()
    {
        return ['1' => __('Is_lock 1'), '2' => __('Is_lock 2')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5'), '6' => __('Status 6')];
    }

    public function getPayStatusList()
    {
        return ['1' => __('Pay_status 1'), '2' => __('Pay_status 2')];
    }

    public function getShippingMentList()
    {
        return ['1' => __('Shipping_ment 1'), '2' => __('Shipping_ment 2')];
    }

    public function getMerchantIdAttr($value, $data)
    {
        return  Db::name('merchant')->where('id',$data['merchant_id'])->value('name');
    }
    public function getOrderTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['order_type']) ? $data['order_type'] : '');
        $list = $this->getOrderTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsLockTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_lock']) ? $data['is_lock'] : '');
        $list = $this->getIsLockList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $list = $this->getPayStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLastPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['last_pay_time']) ? $data['last_pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getShippingMentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['shipping_ment']) ? $data['shipping_ment'] : '');
        $list = $this->getShippingMentList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLastPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\member\User', 'buyer_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function paid()
    {
        return $this->belongsTo('app\admin\model\ShopPaid', 'paid_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function merchant()
    {
        return $this->belongsTo('app\admin\model\Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function users(){
        return $this->hasOne('app\admin\model\member\User','id','buyer_id');
    }

    public function set_finish($ids,$admin=[]){

        //订单状态为待完成，才能操作
        $order = self::where('id',$ids)->find();
        $order->status == 4 or $this->error('非法操作');
        Db::startTrans();
        try{
            $order_info = Db::table('wgc_shop_order')->where('id','=',$ids)->find();
            $result[] = Db::table('wgc_shop_order')->where('id','=',$ids)->update(['status'=>5,'finish_time'=>time()]);

            //分销奖励要入用户的零钱
            $reward_list = Db::table('wgc_shop_reward')->where('order_id','=',$ids)->where('status','=',1)->select();
            if($reward_list){
                foreach($reward_list as $k=>$v){
                    $user = UserModel::where('id',$v['promoter_uid'])->find();
                    $result[] = Db::table('wgc_user')->where('id','=',$v['promoter_uid'])->inc('money',$v['reward_total'])->update();
                    $result[] = Db::table('wgc_shop_reward')->where('id','=',$v['id'])->update(['status'=>2]);
                    $result[] = UserMoneyLog::insertData($user->id,1,1,$v['reward_total'],$user->money,$user->money+$v['reward_total']);
                }
            }

            //店铺奖励要入用户的零钱
            $reward_team_list = Db::table('wgc_shop_reward_team')->where('order_id','=',$ids)->where('status','=',1)->select();
            if($reward_team_list){
                foreach($reward_team_list as $k=>$v){
                    $user = UserModel::where('id',$v['uid'])->find();
                    $result[] = Db::table('wgc_user')->where('id','=',$v['uid'])->inc('money',$v['reward_total'])->update();
                    $result[] = Db::table('wgc_shop_reward_team')->where('id','=',$v['id'])->update(['status'=>2]);
                    $result[] = UserMoneyLog::insertData($user->id,1,1,$v['reward_total'],$user->money,$user->money+$v['reward_total']);
                }
            }

            //用户增加消费总额
            $result[] = Db::table('wgc_user')
                ->where('id','=',$order_info['buyer_id'])
                ->inc('total_amount',$order_info['pay_amount'])
                ->update()!==false;

            //商品增加销量
            //获取商品列表
            $order_product_list = Db::table('wgc_shop_order_product')->where('order_id','=',$ids)->select();
            if($order_product_list){
                foreach($order_product_list as $k=>$v){
                    $result[] = Db::table('wgc_product')
                        ->where('id','=',$v['product_id'])
                        ->inc(['vsalesnum','rsalesnum'],$v['number'])
                        ->update();
                }
            }

            //订单日志
            if(!$admin){
                $result[] = ShopOrderLog::insertLog($ids,3,[],0,'计划任务自动改变');
            }else{
                $result[] = ShopOrderLog::insertLog($ids,3,[],$admin['id'],$admin['username']);
            }

            //奖励积分
            $rewardScoreAmount = $order->order_amount - $order->refund_amount;
            $buyer = UserModel::with('level')->where('id',$order->buyer_id)->find();
            if($rewardScoreAmount >= 1 && $buyer->level->consume_score>0){
                $score = floor($rewardScoreAmount) * $buyer->level->consume_score*$buyer->level->multiple;//奖励的积分数
                $result[] = UserModel::plusScore($buyer->id,$score);
                $result[] = UserModel::plusTotalScore($buyer->id,$score);
                $result[] = UserScoreLog::insertData($buyer->id,1,$score,$buyer->score,$buyer->score+$score,$order->order_sn,'订单完成');
            }
            $result[] = UserModel::up_user_level($buyer->id);//提升会员等级

            //商户零钱入账
            if($order_info['supply_amount'] > 0){
                $result[] = Merchant::where('id',$order_info['merchant_id'])->setInc('money',$order_info['supply_amount']);
            }
            if( checkRes( $result ) ){
                Db::commit();
                return ['code'=>1,'msg'=>'操作成功'];
            }else{
                Db::rollback();
                return ['code'=>0,'msg'=>'操作失败'];
            }
        }catch (Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>$e->getMessage()];
        }
    }

}

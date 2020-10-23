<?php

namespace app\admin\model\shop;

use app\admin\model\member\User as UserModel;
use app\admin\model\member\UserMoneyLog;
use think\Model;
use think\Db;
use app\api\model\shop\OrderModel;
use app\common\library\Wechat;
use app\admin\model\Merchant;
use app\api\model\shop\Paid;

class Tuan extends Model
{
    // 表名
    protected $name = 'shop_tuan';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
    ];


    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function order(){
        return $this->hasMany('\app\api\model\shop\OrderModel','tuan_id','id')->where('order_type',2);
    }

    //插入团购信息
    public static function insertData($info,$user_id){
        $now = time();
        $data = [
            'status'    =>  0,
            'user_id'   => $user_id,
            'group_id'   => $info['group_id'],
            'merchant_id'   => $info['merchant_id'],
            'product_id'   => $info['product_id'],
            'group_size'   => $info['group_size'],
            'join_num'   => 1,
            'pay_num'   => 0,
            'auto_switch'   => $info['auto_switch'],
            'last_time'   => $now+$info['hour']*3600,
            'updatetime'   => $now,
            'createtime'   => $now,
        ];
        return $data;
    }

    //获取参团信息
    public static function tuan_detail($tuan_id){
        $data = Tuan::field(['id','user_id','group_id','group_size','pay_num','status','join_num','auto_switch','last_time'])->where('id',$tuan_id)->find()->toArray();
        $data['user_list'] = OrderModel::tuan_user_list($tuan_id);
        return $data;
    }

    //查询订单列表
    public static function order_list($buyer_id,$status=null)
    {
        $where = ['o.order_type'=>2,'o.buyer_id'=>$buyer_id];
        if(!is_null($status)){
            if($status == 0){
                $where['o.pay_status'] = 1;
            }else{
                $where['o.pay_status'] = 2;
                $where['t.status'] = $status;
            }
        }

        $field = ['m.name merchant_name','m.logo','o.merchant_id','o.id order_id','o.buyer_id','o.order_sn','o.status','o.pay_status','o.order_amount','o.freight_amount','o.pay_amount','o.createtime','o.last_pay_time','o.tuan_id','t.status tuan_status','t.user_id','t.group_size','t.pay_num','p.name product_name','p.props_name','p.image','p.price','p.number'];
        return self::alias('t')->field($field)->join('shop_order o','o.tuan_id=t.id')->where($where)
            ->join('shop_order_product p','p.order_id=o.id')->join("merchant m",'o.merchant_id=m.id')->order('o.createtime','desc')->select();
    }


    //查询订单详情
    public static function order_detail($buyer_id,$order_id){
        $where = ['o.order_type'=>2,'o.buyer_id'=>$buyer_id,'order_id'=>$order_id];
        $field = ['m.name merchant_name','m.logo','o.merchant_id','o.id','o.pay_status','o.order_sn','o.status','o.order_amount','o.freight_amount','o.pay_amount','o.createtime','o.tuan_id','o.last_pay_time','t.status tuan_status','t.group_size',
            't.last_time','t.join_num','t.pay_num','t.auto_switch','p.name product_name','p.props_name','p.image','p.price','p.number'];
        return self::alias('t')->field($field)->join('shop_order o','o.tuan_id=t.id')->where($where)
            ->join('shop_order_product p','p.order_id=o.id')->join("merchant m",'o.merchant_id=m.id')->find();
    }

    //超时团
    public static function timeout_tuan(){
        $now = time();
        return self::with(['order'=>function($query){
            $query->where('status','in',[1,2])->order('status','asc');
        }])->where('last_time','<',$now)->where('status',1)->select();
    }

    //团购订单取消退款
    public static function timeout_tuan_cancel($tuan){
        //判断一下是否自动成团
        if($tuan->auto_switch == 1){
            $tuan->status = 2;
            $tuan->save();
        }else{
            //把该团订单取消，已支付订单退款处理
            $flag = true;
            foreach ($tuan->order as $ov){
                $status = $ov->status;
                $ov->startTrans();
                $ov->status = 6;
                $ov->dowm_reason = '超时未成团';
                $bool = $ov->save();
                if($bool && $status == 2){
                    $paid = Paid::where('id',$ov->paid_id)->find();
                    if($paid->payment_id == 1 || $paid->payment_id == 2){
                        Wechat::refund($paid->out_trade_no,$paid->out_trade_no.'_',$paid->pay_amount) or $bool = false;
                    }else{
                        //退回零钱
                        $money = UserModel::where('id',$paid->buyer_id)->value('money');
                        UserModel::plusMoney($paid->buyer_id,$paid->pay_amount) or $bool = false;
                        UserMoneyLog::insertData($paid->buyer_id,1,5,$paid->pay_amount,$money,$money+$paid->pay_amount,$ov->order_sn) or $bool = false;

                    }

                }
                if($bool){
                    $ov->commit();
                }else{
                    $ov->rollback();
                    $flag = false;
                }
            }
            //所有的订单处理成功，团购状态也更新
            if($flag){
                $tuan->status = 3;//拼团失败
                $tuan->save();
            }
        }

    }

    public function user(){
        return $this->hasOne('app\admin\model\member\User','id','user_id');
    }

    //随机获取参团列表
    public static function tuan_list($group_id){
        $field = ['id tuan_id','group_id','user_id','group_size','join_num','last_time','pay_num'];
        $now = time();
        $result = self::field($field)->where('status',1)->where('group_id',$group_id)->with(['user'=>function($query){
            $query->field(['id','nickname','avatar']);
        }])->where('last_time','>',$now)->orderRaw('rand()')->limit(0,3)->select();
        return collection($result)->toArray();
    }




}

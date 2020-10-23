<?php

namespace app\admin\model;

use think\Exception;
use think\Model;
use think\Db;
use  app\admin\model\Upbag;


class UpbagOrder extends Model
{

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'upbag_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    /**
     * 获取商品详情
     * @param string $group_id 大礼包ID
     */
    public static function product_detail($id){
        $field = ['u.*','p.category_ids','p.name product_name','p.product_sn','p.image','p.product_remark','p.images','p.weight','p.line_price',
            'p.cost_price','p.pro_reward','p.video_file','p.shipping_switch','p.tags','p.vsalesnum','p.rsalesnum','p.content','p.is_as'
            ,'p.shipping_rule_id','r.name rule_name','r.type_list rule_type','r.first_num','r.continue_num','r.first_price','r.continue_price'];
        $info = Db::name('upbag')->alias('u')->field($field)->join('product p',"p.id=u.product_id and p.status='online' and p.post_status='2' and p.deletetime IS NULL")
            ->join('shipping_rule r',"r.id=p.shipping_rule_id")->where('u.id',$id)->find();
        unset($info['has_prop_text']);
        return $info;
    }
    //查询订单列表
    public static function order_list($buyer_id,$status=null)
    {
        $where = ['o.order_type'=>2,'o.buyer_id'=>$buyer_id];
        is_null($status) or $where['t.status'] = $status;
        $field = ['m.name merchant_name','m.logo','o.merchant_id','o.id','o.order_sn','o.status','o.pay_amount','o.createtime','o.tuan_id','t.status upbag_status','p.name product_name','p.props_name','p.image','p.price','p.number'];
        return self::alias('t')->field($field)->join('shop_order o','o.tuan_id=t.id')->where($where)
            ->join('shop_order_product p','p.order_id=o.id')->join("merchant m",'o.merchant_id=m.id')->order('o.createtime','desc')->select();
    }
    //查询订单详情
    public static function order_detail($buyer_id,$order_id){
        $where = ['o.order_type'=>2,'o.buyer_id'=>$buyer_id,'order_id'=>$order_id];
        $field = ['m.name merchant_name','m.logo','o.merchant_id','o.id','o.order_sn','o.status','o.pay_amount','o.createtime','o.tuan_id','t.status upbag_status','t.grade',
            'p.name product_name','p.props_name','p.image','p.price','p.number'];
        return self::alias('t')->field($field)->join('shop_order o','o.tuan_id=t.id')->where($where)
            ->join('shop_order_product p','p.order_id=o.id')->join("merchant m",'o.merchant_id=m.id')->find();
    }
    /**
     * 创建大礼包订单
     * @param $order_id 商城订单id
     * @param $upbag_id 大礼包id
     * @param $price 价格
     * @return mixed
     * @throws \think\exception\DbException
     *
     */
    public function create_upbag_order($user_id,$upbag_id,$price)
    {
       $upbag = Upbag::get($upbag_id);

       Db::startTrans();
           $order = [
               'upbag_id' => $upbag_id,
               'user_id' => $user_id,
               'grade' => $upbag['grade'],
               'team_grade' => $upbag['team_grade'],
               'create_time' => time(),
               'price' => $price,
               'status' => 0
            ];
           $user_grade =  User::get($user_id)['grade'];
           if($user_grade>$upbag['grade']){
              return $this->error = '购买等级需要大于当前等级';
           }
           $upbagorder = new UpbagOrder();
           $r =  $upbagorder::insertGetId($order);
       if($r){
           Db::commit();
           return $r;
       }else{
           Db::rollback();
           return false;
       }
    }

    /**
     * 支付回调逻辑
     * @param  $upbag_order_id 大礼包订单Id
     * @return  bool
     * @throws  \think\exception\DbException
     */
    public static function paid($upbag_order_id)
    {
        $upbag_order =  self::where('id',$upbag_order_id)->find();
        $bool = true;
        try{
            $user_grade =  User::get($upbag_order['user_id']);
            //等级判断
            $param = [];
            if($user_grade['grade'] < $upbag_order['grade']){
                $param['grade'] = $upbag_order['grade'];
            }
            if($user_grade['team_grade'] < $upbag_order['team_grade']){
                $param['team_grade'] = $upbag_order['team_grade'];
            }
            //订单状态修改
            $upbag_order->paid_time  = datetime(time(),'Y-m-d H:i:s');
            $upbag_order->status = 1;
            $upbag_order->save() or $bool = false;
            //用户级别变更
            if(!empty($param)){
                User::where('id',$upbag_order['user_id'])->update($param) or $bool = false;
            }
            return $bool;

        }catch (Exception $e){
            return false;
        }
    }
    public function order()
    {
        return $this->hasOne('ShopOrder', 'tuan_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function upbag()
    {
        return $this->belongsTo('Upbag', 'upbag_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }
}

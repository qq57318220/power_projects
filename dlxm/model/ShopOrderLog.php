<?php

namespace app\admin\model;

use think\Model;


class ShopOrderLog extends Model
{
    // 表名
    protected $name = 'shop_order_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
    ];

    public function getTypeList()
    {
        return ['1' => '发货', '2' => '确认收货', '3' =>'完成订单', '4' =>'交易关闭','5'=>'修改价格','6'=>'修改地址',"7"=>"同意退款",
            "8"=>"驳回退款","9"=>"完成售后","10"=>"退款","11"=>"主动发起退货","12"=>'发起维权'];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    //获取订单日志记录
    public static function getLogs($order_id){
        return self::where('order_id',$order_id)->order('createtime','desc')->select();
    }

    /**
     * 插入订单日志
     * @Param $order_id integer 订单ID
     * @Param $type integer 操作类型:1=发货,2=确认收货,3=完成订单,4=交易关闭
     * @Param $content array 日志内容键值:关联数组
     * @Param $admin_id integer 操作管理员ID
     * @Param $username string 操作管理员账号
     */
    public static function insertLog($order_id,$type,$content,$admin_id,$username){
        $data = ['order_id'=>$order_id,'type'=>$type,'content'=>$content,'admin_id'=>$admin_id,'username'=>$username,'createtime'=>time()];
        if(!empty($content) && is_array($content)){
            $con = '';
            foreach ($content as $k=>$v){
                $con .= '['.$k.'：'.$v.']，';
            }
            $data['content'] = mb_substr($con,0,mb_strlen($con)-1);
        }
        return self::insertGetId($data);
    }




}

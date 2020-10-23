<?php

namespace app\admin\model\member;

use think\Model;


class UserMoneyLog extends Model
{
    // 表名
    protected $name = 'user_money_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    /*****
     * @param $mark 符号:1=加+,2=减-
     * @param $type 变更类型:1=佣金收入,2=发起提现,3=提现失败,4=付款,5=退款
     * @param $money 变更金额
     * @param $before 变更前余额
     * @param $after 变更后余额
     * @param $memo 备注
     *
     */
   public static function insertData($user_id,$mark,$type,$money,$before,$after,$memo=''){
        $data = [
            'user_id'      =>  $user_id,
            'mark'      =>  $mark,
            'type'      =>  $type,
            'money'     =>  $money,
            'before'    =>  $before,
            'after'     =>  $after,
            'memo'      =>  $memo,
            'createtime'=>  time(),
        ];
        return self::insertGetId($data);
   }


}

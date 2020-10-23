<?php

namespace app\admin\model;

use think\Model;


class UserAmountLog extends Model
{

    // 表名
    protected $name = 'user_amount_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'mark_text'
    ];
    

    
    public function getMarkList()
    {
        return ['1' => __('Mark 1'), '2' => __('Mark 2')];
    }


    public function getMarkTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['mark']) ? $data['mark'] : '');
        $list = $this->getMarkList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /*****
     * @param $mark 符号:1=加+,2=减-
     * @param $gold 变更余额
     * @param $memo 备注
     *
     */
    public static function insertData($user_id,$mark,$amount,$memo=''){
        $data = [
            'user_id'       =>  $user_id,
            'mark'          =>  $mark,
            'amount'     =>  $amount,
            'memo'      =>  $memo,
            'createtime'=>  time(),
        ];
        return self::insertGetId($data);
    }


}

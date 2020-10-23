<?php

namespace app\admin\model;

use think\Model;


class UserAmount extends Model
{

    // 表名
    protected $name = 'user_amount';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_status_text'
    ];
    

    
    public function getPayStatusList()
    {
        return ['1' => __('Pay_status 1'), '2' => __('Pay_status 2')];
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $list = $this->getPayStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

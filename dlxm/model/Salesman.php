<?php

namespace app\admin\model;

use think\Model;


class Salesman extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'salesman';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_disable_text'
    ];
    

    
    public function getIsDisableList()
    {
        return ['0' => __('Is_disable 0'), '1' => __('Is_disable 1')];
    }


    public function getIsDisableTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_disable']) ? $data['is_disable'] : '');
        $list = $this->getIsDisableList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function merchant()
    {
        return $this->belongsTo('Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

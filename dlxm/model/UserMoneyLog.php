<?php

namespace app\admin\model;

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

    // 追加属性
    protected $append = [
        'type_text',
        'mark_text'
    ];
    

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2'), '3' => __('Type 3'), '4' => __('Type 4'), '5' => __('Type 5'), '6' => __('Type 6'), '7' => __('Type 7'), '8' => __('Type 8')];
    }

    public function getMarkList()
    {
        return ['1' => __('Mark 1'), '2' => __('Mark 2')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getMarkTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['mark']) ? $data['mark'] : '');
        $list = $this->getMarkList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

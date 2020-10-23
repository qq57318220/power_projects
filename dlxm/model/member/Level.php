<?php

namespace app\admin\model\member;

use think\Model;


class Level extends Model
{
    // 表名
    protected $name = 'level';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_list_text'
    ];
    

    
    public function getTypeListList()
    {
        return ['1' => __('Type_list 1'),'2' => __('Type_list 2')];
    }


    public function getTypeListTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type_list']) ? $data['type_list'] : '');
        $list = $this->getTypeListList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

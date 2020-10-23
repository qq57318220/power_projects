<?php

namespace app\admin\model\market;

use think\Model;
use traits\model\SoftDelete;

class Coupon extends Model
{

    use SoftDelete;
    // 表名
    protected $name = 'coupon';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'type_list_text',
        'time_type_text'
    ];
    

    
    public function getTypeListList()
    {
        return ['1' => __('Type_list 1'), '2' => __('Type_list 2')];
    }

    public function getTimeTypeList()
    {
        return ['1' => __('Time_type 1'), '2' => __('Time_type 2')];
    }


    public function getTypeListTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type_list']) ? $data['type_list'] : '');
        $list = $this->getTypeListList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTimeTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['time_type']) ? $data['time_type'] : '');
        $list = $this->getTimeTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

<?php

namespace app\admin\model\general;

use think\Model;


class ShippingRule extends Model
{
    // 表名
    protected $name = 'shipping_rule';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_list_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getTypeListList()
    {
        return ['1' => __('Type_list 1'), '2' => __('Type_list 2')];
    }


    public function getTypeListTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type_list']) ? $data['type_list'] : '');
        $list = $this->getTypeListList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }


}

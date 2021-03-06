<?php

namespace app\admin\model;

use think\Model;


class Raise extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'raise';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'has_prop_text',
        'status_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getHasPropList()
    {
        return ['0' => __('Has_prop 0'), '1' => __('Has_prop 1')];
    }

    public function getStatusList()
    {
        return ['online' => __('Status online'), 'offline' => __('Status offline')];
    }


    public function getHasPropTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['has_prop']) ? $data['has_prop'] : '');
        $list = $this->getHasPropList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function merchant()
    {
        return $this->belongsTo('Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

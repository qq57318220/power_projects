<?php

namespace app\admin\model;

use think\Model;


class ShopOrderActivist extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'shop_order_activist';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'wq_status_text',
        'wq_type_text'
    ];
    

    
    public function getWqStatusList()
    {
        return ['1' => __('Wq_status 1'), '2' => __('Wq_status 2'), '3' => __('Wq_status 3')];
    }

    public function getWqTypeList()
    {
        return ['1' => __('Wq_type 1'), '2' => __('Wq_type 2')];
    }


    public function getWqStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['wq_status']) ? $data['wq_status'] : '');
        $list = $this->getWqStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getWqTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['wq_type']) ? $data['wq_type'] : '');
        $list = $this->getWqTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function merchant()
    {
        return $this->belongsTo('Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function shoporderproduct()
    {
        return $this->belongsTo('ShopOrderProduct', 'order_product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

<?php

namespace app\admin\model;

use think\Model;


class AfterSale extends Model
{
    // 表名
    protected $name = 'shop_order_as';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'as_status_text',
        'as_type_text',
        'refund_status_text',
        'shipping_ment_text'
    ];
    

    
    public function getAsStatusList()
    {
        return ['1' => __('As_status 1'), '2' => __('As_status 2'), '3' => __('As_status 3'), '4' => __('As_status 4'), '5' => __('As_status 5'), '6' => __('As_status 6')];
    }

    public function getAsTypeList()
    {
        return ['1' => __('As_type 1'), '2' => __('As_type 2'), '3' => __('As_type 3')];
    }

    public function getRefundStatusList()
    {
        return ['0' => __('Refund_status 0'), '1' => __('Refund_status 1'), '2' => __('Refund_status 2')];
    }

    public function getShippingMentList()
    {
        return ['1' => __('Shipping_ment 1'), '2' => __('Shipping_ment 2'), '3' => __('Shipping_ment 3')];
    }


    public function getAsStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['as_status']) ? $data['as_status'] : '');
        $list = $this->getAsStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAsTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['as_type']) ? $data['as_type'] : '');
        $list = $this->getAsTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getRefundStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['refund_status']) ? $data['refund_status'] : '');
        $list = $this->getRefundStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getShippingMentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['shipping_ment']) ? $data['shipping_ment'] : '');
        $list = $this->getShippingMentList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function shoporderproduct()
    {
        return $this->belongsTo('ShopOrderProduct', 'order_product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function address()
    {
        return $this->hasOne('AfterSaleAddress', 'as_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }
}

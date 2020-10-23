<?php

namespace app\admin\model;

use think\Model;


class TuanOrder extends Model
{
    // 表名
    protected $name = 'shop_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'order_type_text',
        'is_lock_text',
        'status_text',
        'pay_status_text',
        'pay_time_text',
        'last_pay_time_text',
        'shipping_ment_text'
    ];



    public function getOrderTypeList()
    {
        return ['1' => __('Order_type 1'), '2' => __('Order_type 2')];
    }

    public function getIsLockList()
    {
        return ['1' => __('Is_lock 1'), '2' => __('Is_lock 2')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5'), '6' => __('Status 6')];
    }

    public function getPayStatusList()
    {
        return ['1' => __('Pay_status 1'), '2' => __('Pay_status 2')];
    }

    public function getShippingMentList()
    {
        return ['1' => __('Shipping_ment 1'), '2' => __('Shipping_ment 2')];
    }


    public function getOrderTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['order_type']) ? $data['order_type'] : '');
        $list = $this->getOrderTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsLockTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_lock']) ? $data['is_lock'] : '');
        $list = $this->getIsLockList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $list = $this->getPayStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLastPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['last_pay_time']) ? $data['last_pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getShippingMentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['shipping_ment']) ? $data['shipping_ment'] : '');
        $list = $this->getShippingMentList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLastPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function shoptuan()
    {
        return $this->belongsTo('ShopTuan', 'tuan_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('User', 'buyer_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }

}

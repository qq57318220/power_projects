<?php

namespace app\admin\model;

use think\Db;
use think\Model;


class UpbagOrders extends Model
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
        'shipping_ment_text',
        'send_time_text',
        'finish_time_text',
        'set_wait_finish_time_text',
        'last_pay_time_text',
        'grade_text'
    ];
    

    
    public function getOrderTypeList()
    {
        return ['1' => __('Order_type 1'), '2' => __('Order_type 2'), '3' => __('Order_type 3')];
    }

    public function getIsLockList()
    {
        return ['1' => __('Is_lock 1'), '2' => __('Is_lock 2')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5'), '6' => __('Status 6')];
    }

    public function getGradeList()
    {
        $list = Db::name('user_grade')->field('name,grade')->select();
        foreach ($list as $key =>$val){
            $list[$val['grade']] = $val['name'];
        }
//        halt($list);
        return $list;
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
    public function getGradeTextAttr($value, $data)
    {

        $value = $value ? $value : (isset($data['grade']) ? $data['grade'] : '');
//           halt($data['grade']);
        $list = $this->getGradeList();
//        halt($list);
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


    public function getShippingMentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['shipping_ment']) ? $data['shipping_ment'] : '');
        $list = $this->getShippingMentList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSendTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['send_time']) ? $data['send_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getFinishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finish_time']) ? $data['finish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getSetWaitFinishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['set_wait_finish_time']) ? $data['set_wait_finish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLastPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['last_pay_time']) ? $data['last_pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setSendTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setFinishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setSetWaitFinishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLastPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function shoppaid()
    {
        return $this->belongsTo('ShopPaid', 'paid_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function upbagorder()
    {
        return $this->belongsTo('UpbagOrder', 'tuan_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function merchant()
    {
        return $this->belongsTo('Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function upbag()
    {
        return $this->belongsTo('Upbag', 'upbag_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function user()
    {
        return $this->belongsTo('User', 'buyer_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

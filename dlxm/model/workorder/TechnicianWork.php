<?php

namespace app\admin\model\workorder;

use think\Model;


class TechnicianWork extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'workorder';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'complete_type_text',
        'workorder_type_text',
        'launch_time_text',
        'quote_time_text',
        'revoke_time_text',
        'receipt_time_text',
        'repair_time_text',
        'price_revision_time_text',
        'complete_time_text',
        'refund_time_text'
    ];
    

    
    public function getCompleteTypeList()
    {
        return ['1' => __('未完成'), '2' => __('撤销'), '3' => __('完成')];
    }

    public function getWorkorderTypeList()
    {
        return ['1' => __('诊断工单'), '2' => __('维保工单'), '3' => __('维修工单')];
    }


    public function getCompleteTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['complete_type']) ? $data['complete_type'] : '');
        $list = $this->getCompleteTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getWorkorderTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['workorder_type']) ? $data['workorder_type'] : '');
        $list = $this->getWorkorderTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getLaunchTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['launch_time']) ? $data['launch_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getQuoteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['quote_time']) ? $data['quote_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRevokeTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['revoke_time']) ? $data['revoke_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getReceiptTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['receipt_time']) ? $data['receipt_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRepairTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['repair_time']) ? $data['repair_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPriceRevisionTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['price_revision_time']) ? $data['price_revision_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCompleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['complete_time']) ? $data['complete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRefundTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['refund_time']) ? $data['refund_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setLaunchTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setQuoteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRevokeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setReceiptTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRepairTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPriceRevisionTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCompleteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRefundTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'merchant_id', 'merchant_id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function ledger()
    {
        return $this->belongsTo('app\admin\model\ledger\Ledger', 'ledger_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

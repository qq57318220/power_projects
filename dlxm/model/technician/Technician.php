<?php

namespace app\admin\model\technician;

use app\admin\model\workorder\Workorder;
use think\Model;


class Technician extends Model
{

    

    

    // 表名
    protected $name = 'technician';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'apply_time_text',
        'status_text',
        'create_time_text',
    ];

    public function getStatusList()
    {
        return ['1' => __('未审核'), '2' => __('审核通过'), '0' => __('审核驳回')];
    }


    public function getApplyTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['apply_time']) ? $data['apply_time'] : '');
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


    public function getCompleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['complete_time']) ? $data['complete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setApplyTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function user(){
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

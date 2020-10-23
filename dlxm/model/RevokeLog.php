<?php

namespace app\admin\model;

use think\Model;


class RevokeLog extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'revoke_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'receipt_time_text',
        'repair_time_text',
        'revoke_time_text',
        'create_time_text'
    ];
    

    



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


    public function getRevokeTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['revoke_time']) ? $data['revoke_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setReceiptTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRepairTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRevokeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}

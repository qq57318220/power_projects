<?php

namespace app\admin\model\examine;

use think\Model;


class Content extends Model
{

    

    

    // 表名
    protected $name = 'examine_content';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text'
    ];
    

    



    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function examineplate()
    {
        return $this->belongsTo('app\admin\model\examine\Plate', 'examine_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

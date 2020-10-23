<?php

namespace app\admin\model\merchants;

use think\Model;


class User extends Model
{

    

    

    // 表名
    protected $name = 'merchant_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function merchantdata()
    {
        return $this->belongsTo('app\admin\model\merchant\Data', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

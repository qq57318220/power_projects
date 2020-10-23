<?php

namespace app\admin\model;

use think\Model;


class UserDeep extends Model
{

    // 表名
    protected $name = 'user_deep';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public static function getAll(){
        return collection(self::select())->toArray();
    }
    







}

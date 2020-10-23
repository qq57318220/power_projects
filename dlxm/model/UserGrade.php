<?php

namespace app\admin\model;

use think\Model;


class UserGrade extends Model
{

    // 表名
    protected $name = 'user_grade';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function ratio(){
        return $this->hasMany('app\admin\model\UserGradeRatio','grade','grade');
    }

    public static function getGradeRatio(){
        return self::with(['ratio'=>function($query){
            $query->order('deep','asc');
        }])->order('grade','asc')->select();
    }


    public static function getAll(){
        return collection(self::select())->toArray();
    }





}

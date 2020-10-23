<?php

namespace app\admin\model;

use think\Model;


class UserGradeRatio extends Model
{

    // 表名
    protected $name = 'user_grade_ratio';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    public function userGrade(){
        return $this->hasOne('UserGrade','grade','grade');
    }

    public function userDeep(){
        return $this->hasOne('UserDeep','deep','deep');
    }

    //根据身份级别添加比例设置
    public static function addByGrade($grade){
        $deeps = UserDeep::getAll();
        $data = [];
        foreach ($deeps as $v){
            $data[] = ['grade'=>$grade,'deep'=>$v['deep'],'ratio'=>0,'updatetime'=>time()];
        }
        return (new self)->saveAll($data);
    }

    //根据身份级别删除比例设置
    public static function delByGrade($grade){
       return self::where('grade',$grade)->delete();
    }


    //根据深度添加比例设置
    public static function addByDeep($deep){
        $grades = UserGrade::getAll();
        $data = [];
        foreach ($grades as $v){
            $data[] = ['grade'=>$v['grade'],'deep'=>$deep,'ratio'=>0,'updatetime'=>time()];
        }
        return (new self)->saveAll($data);
    }

    //根据深度删除比例设置
    public static function delByDeep($deep){
        return self::where('deep',$deep)->delete();
    }





}

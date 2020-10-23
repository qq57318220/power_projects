<?php

namespace app\admin\model\shop;

use think\Model;


class Prop extends Model
{
    // 表名
    protected $name = 'prop';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function propval(){
        return $this->hasMany('Prop','pid','id');
    }

    //查询列表数据
    public static function getList($where=[]){
        return self::where($where)->select();
    }

    //查询列表数据并分级
    public static function getListLv($field=['*'],$where=[]){
        $where['pid'] = 0;
        return self::where($where)->field($field)->with('propval')->order('id','asc')->select();
    }


    public function merchant(){
        return $this->belongsTo('app\admin\model\Merchant','merchant_id','id');
    }






}

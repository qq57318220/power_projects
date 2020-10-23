<?php

namespace app\admin\model;

use think\Model;


class GoldRule extends Model
{

    // 表名
    protected $table = 'wgc_gold_rule';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    //通过充值金额获取赠送购物币规则
    public static function getRule($total){
        return self::where('total','<=',$total)->order('total','desc')->find();
    }
    







}

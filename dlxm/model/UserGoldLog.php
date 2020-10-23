<?php

namespace app\admin\model;

use think\Model;


class UserGoldLog extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'user_gold_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'mark_text'
    ];
    

    
    public function getMarkList()
    {
        return ['1' => __('Mark 1'), '2' => __('Mark 2')];
    }


    public function getMarkTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['mark']) ? $data['mark'] : '');
        $list = $this->getMarkList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    /*****
     * @param $mark 符号:1=加+,2=减-
     * @param $gold 变更购物币
     * @param $after 变更后积分
     * @param $memo 备注
     *
     */
    public static function insertData($user_id,$mark,$gold,$after,$memo=''){
        $data = [
            'user_id'      =>  $user_id,
            'mark'      =>  $mark,
            'gold'     =>  $gold,
            'after'     =>  $after,
            'memo'      =>  $memo,
            'createtime'=>  time(),
        ];
        return self::insertGetId($data);
    }

}

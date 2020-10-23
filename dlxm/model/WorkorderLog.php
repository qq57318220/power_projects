<?php

namespace app\admin\model;

use think\Model;


class WorkorderLog extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'workorder_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'mark_text',
        'create_time_text'
    ];
    

    
    public function getMarkList()
    {
        return ['1' => __('+'), '2' => __('-')];
    }

     /*****
      * @param $mark 符号:1=加+,2=减-
      * @param $type 变更类型:1=JSAPI 2=h5Pay 3=零钱
      * @param $money 变更金额
      * @param $before 变更前余额
      * @param $after 变更后余额
      *
      */
    public static function insertData($user_id,$work_id,$mark,$type,$money,$before,$after){
         $data = [
             'user_id'      =>  $user_id,
             'work_id'  => $work_id,
             'mark'      =>  $mark,
             'type'      =>  $type,
             'money'     =>  $money,
             'before_money'    =>  $before,
             'after_money'     =>  $after,
             'create_time'=>  time(),
         ];
         return self::insertGetId($data);
    }


    public function getMarkTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['mark']) ? $data['mark'] : '');
        $list = $this->getMarkList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}

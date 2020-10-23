<?php

namespace app\admin\model;

use think\Env;
use think\Model;


class Notification extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'notification';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'user_type_text',
      //  'create_time_text'
    ];
    

    
    public function getTypeList()
    {
        return ['0' => __('Type 0')];
    }

    public function getUserTypeList()
    {
        return ['1' => __('User_type 1'), '2' => __('User_type 2')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getUserTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['user_type']) ? $data['user_type'] : '');
        $list = $this->getUserTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


  /*  public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }*/

    protected function setCreateTimeAttr($value)
    {
		//return 111111;
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}

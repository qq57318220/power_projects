<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Merchant extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'merchant';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'sex_text',
        'status_text'
    ];
    
    //获取商家列表
    public static function getList($where = []){
        return self::where($where)->select();
    }
    
    public function getSexList()
    {
        return ['1' => __('Sex 1'), '2' => __('Sex 2'), '0' => __('Sex 0')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

<?php

namespace app\admin\model;

use think\Db;
use think\Model;


class Upbag extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'upbag';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'has_prop_text',
        'status_text',
        'grade_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getHasPropList()
    {
        return ['0' => __('Has_prop 0'), '1' => __('Has_prop 1')];
    }


    public function getStatusList()
    {
        return ['online' => __('Status online'), 'offline' => __('Status offline')];
    }

    public function getGradeTextAttr($value, $data)
    {

        return  Db::name('user_grade')->where('grade',$data['grade'])->value('name');
    }

    public function getHasPropTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['has_prop']) ? $data['has_prop'] : '');
        $list = $this->getHasPropList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }
}

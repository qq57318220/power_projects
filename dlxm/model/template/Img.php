<?php

namespace app\admin\model\template;

use think\Model;


class Img extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'template_con';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'is_product_text',
        'has_delete_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getIsProductList()
    {
        return ['1' => __('Is_product 1'), '0' => __('Is_product 0')];
    }

    public function getHasDeleteList()
    {
        return ['1' => __('Has_delete 1'), '0' => __('Has_delete 0')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsProductTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_product']) ? $data['is_product'] : '');
        $list = $this->getIsProductList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getHasDeleteTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['has_delete']) ? $data['has_delete'] : '');
        $list = $this->getHasDeleteList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

<?php

namespace app\admin\model\cbc;

use think\Model;
use traits\model\SoftDelete;

class Merchant extends Model
{

    use SoftDelete;

    //数据库
    protected $connection = 'database_cbc';
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
        'collection_mode_text'
    ];
    

    
    public function getCollectionModeList()
    {
        return ['1' => __('Collection_mode 1'), '2' => __('Collection_mode 2')];
    }


    public function getCollectionModeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['collection_mode']) ? $data['collection_mode'] : '');
        $list = $this->getCollectionModeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

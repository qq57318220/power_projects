<?php

namespace app\admin\model\cbc;

use think\Model;
use traits\model\SoftDelete;

class MerchantCategory extends Model
{

    use SoftDelete;

    //数据库
    protected $connection = 'database_cbc';
    // 表名
    protected $name = 'merchant_category';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];

}

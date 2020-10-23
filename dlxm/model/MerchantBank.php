<?php

namespace app\admin\model;

use think\Model;


class MerchantBank extends Model
{

    // 表名
    protected $name = 'merchant_bank';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'bank_text'
    ];

    public function getBankTextAttr($value, $data)
    {
       return  $data['bank']."(".$data['bank_account'].")";
    }
    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }

    







}

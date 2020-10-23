<?php

namespace app\admin\model\ledger;

use think\Model;


class Product extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'shop_order_product';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'refund_mark_text',
        'is_as_text',
        'is_comment_text'
    ];
    

    
    public function getRefundMarkList()
    {
        return ['0' => __('Refund_mark 0'), '1' => __('Refund_mark 1'), '2' => __('Refund_mark 2')];
    }

    public function getIsAsList()
    {
        return ['1' => __('Is_as 1'), '0' => __('Is_as 0')];
    }

    public function getIsCommentList()
    {
        return ['0' => __('Is_comment 0'), '1' => __('Is_comment 1')];
    }


    public function getRefundMarkTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['refund_mark']) ? $data['refund_mark'] : '');
        $list = $this->getRefundMarkList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsAsTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_as']) ? $data['is_as'] : '');
        $list = $this->getIsAsList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsCommentTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_comment']) ? $data['is_comment'] : '');
        $list = $this->getIsCommentList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function shoporder()
    {
        return $this->belongsTo('app\admin\model\ShopOrder', 'order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

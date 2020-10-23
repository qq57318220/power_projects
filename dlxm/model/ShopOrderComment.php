<?php

namespace app\admin\model;

use think\Model;


class ShopOrderComment extends Model
{


    // 表名
    protected $name = 'shop_order_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    /**
     * 通过订单查找评论
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCommentsByOrderId($order_id)
    {
      $list =   \app\admin\model\ShopOrderComment::with(['user','product'])->where('shop_order_comment.order_id',$order_id)->select();
      //多图处理
        $lists = [];
        foreach ($list as $key => $value){
            $lists[$key] = $value;
            if(!empty($value['images'])){
                $lists[$key]['images']  = explode(',',$value['images']);
            }

        }
      return $lists;
    }

    /**
     * 通过产品id和筛选类型来查询评论
     * @param $product_id
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCommentsByProductId($product_id,$type=5)
    {
        switch ($type){
            case 0:
                //好评
                $filter['star'] = ['>',3];
                break;
            case 1:
                //中评
                $filter['star'] =  ['=','3'];
                break;
            case 2:
                //差评
                $filter['star'] = ['<',3];
                break;
            case 3:
                //有图
                $filter['images'] = ['<>','null'];
                break;
            default:
                $filter = [];
                break;
        }
        $list =   \app\admin\model\ShopOrderComment::with(['user'])->where($filter)->where('shop_order_comment.product_id',$product_id)->select();
        //多图处理
        $lists = [];
        foreach ($list as $key => $value){
            $lists[$key] = $value;
            if(!empty($value['images'])){
                $lists[$key]['images']  = explode(',',$value['images']);
            }
        }
        return $lists;
    }
    



    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function merchant()
    {
        return $this->belongsTo('Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function product()
    {
        return $this->belongsTo('Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function shoporderproduct()
    {
        return $this->belongsTo('ShopOrderProduct', 'order_product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

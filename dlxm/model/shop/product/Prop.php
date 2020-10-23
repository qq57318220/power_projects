<?php

namespace app\admin\model\shop\product;

use app\admin\model\shop\Product;
use think\Model;
use app\admin\controller\Common;

class Prop extends Model
{
    // 表名
    protected $name = 'product_prop';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    //查询列表数据
    public static function getList($field=['*'],$where=[]){
        return self::where($where)->field($field)->select();
    }

    //正则表达式查找出所有属性ID
    public static function find_val_ids($propsString){
        preg_match_all("/:([0-9]*?);/",$propsString.';',$res);
        return $res[1];
    }

    //获取产品已选择属性
    public static function product_props($product_id){
        $product_prop_list = collection(self::where('product_id',$product_id)->order('props','asc')->select())->toArray();
        $prop_ids = self::find_val_ids(implode(';',array_column($product_prop_list,'props')));
       $vals = collection(model('shop.prop')->whereIn('id',$prop_ids)->select())->toArray();
       foreach ($product_prop_list as &$v){
           $names = [];
           foreach ($vals as $item){
               if(in_array($item['id'],self::find_val_ids($v['props']))){
                   $names[] = $item['name'];
               }
           }
           $v['names']  = implode('-',$names);
       }
        return [$product_prop_list,$prop_ids];
    }

    /**
 * 更新产品关联属性的值
 * @ApiSummary (胡正林)
 */
    public static function upd_product_props($reg){
        return Common::upd_product_props($reg,new self,new Product,'product_id');
    }







}

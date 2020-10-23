<?php

namespace app\admin\model;

use think\Model;
use app\admin\controller\Common;
use app\admin\model\Upbag;
use app\admin\model\shop\Prop;


class UpbagProductProp extends Model
{
    // 表名
    protected $name = 'upbag_product_prop';

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
    public static function product_props($upbag_id){
        $product_prop_list = collection(self::where('upbag_id',$upbag_id)->order('props','asc')->select())->toArray();
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
        return Common::upd_product_props($reg,new self,new Upbag(),'upbag_id');
    }

    /**
     * 获取大礼包产品属性
     */
    public static function packProps($upbag_id){
        $propsString = implode(';',self::where('upbag_id',$upbag_id)->column('props'));
        preg_match_all("/([0-9]*?):/",$propsString.';',$res);//父级
        $valIds = self::find_val_ids($propsString);//所有子集
        return collection(Prop::field('id,name')->whereIn('id',array_unique($res[1]))->with(['propval'=>function($query) use ($valIds){
            $query->whereIn('id',$valIds);
        }])->select())->toArray();
    }

    /**
     * 获取大礼包产品已选属性信息
     */
    public static function getPropsInfo($upbag_id,$props){
        $info = self::where('upbag_id',$upbag_id)->where('props',$props)->find()->toArray();
        $res = explode(';',$info['props']);
        $ids = [];
        foreach ($res as $v){
            $ids = array_merge($ids,explode(':',$v));
        }
        $proplist = collection(Prop::whereIn('id',$ids)->order('pid','asc')->select())->toArray();
        $info['props_name'] = self::propsName($proplist,0);
        return $info;
    }

    /**
     * 大礼包产品规格值对名称
     */
    public static function propsName($proplist,$pid){
        static $names = "";
        foreach ($proplist as $v){
            if($v['pid'] == 0 && $pid == 0){
                $names .= $v['name'].':'.self::propsName($proplist,$v['id']).'，';
            }else{
                if($pid == $v['pid']){
                    return $v['name'];
                }

            }
        }
        return rtrim($names,'，');;
    }



}

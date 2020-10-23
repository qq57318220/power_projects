<?php

namespace app\admin\controller;

use think\Controller;

/**
 * 公共控制器
 * @internal
 */
class Common extends Controller
{

    /**
     * 产品属性数据封装
     *@ApiSummary (胡正林)
     */
    public function packPropData($extend=[]){
        $prop = $this->request->only(['props','line_price','price','supply_price','cost_price','weight','inventory','score','use_gold']);
        count($prop) > 0 or $this->error('请选择属性');
        foreach ($prop['props'] as $k=>$v){
            $prop['line_price'][$k] != null && $prop['line_price'][$k] >= 0 or $this->error('划线价有误');
            $prop['price'][$k] != null && $prop['price'][$k] >= 0 or $this->error('售卖价有误');
            $prop['cost_price'][$k] != null && $prop['cost_price'][$k] >= 0 or $this->error('成本价有误');
            $prop['inventory'][$k] != null && $prop['inventory'][$k] >= 0 or $this->error('库存有误');
            $temp = array_merge($extend,['props'=>$v,'line_price'=>$prop['line_price'][$k],
                'price'=>$prop['price'][$k],'cost_price'=>$prop['cost_price'][$k],'weight'=>$prop['weight'][$k]?:0,'inventory'=>$prop['inventory'][$k]]);
            if(array_key_exists('supply_price',$prop)){
                $prop['supply_price'][$k] != null && $prop['supply_price'][$k] >= 0 or $this->error('供货价有误');
                $temp['supply_price'] = $prop['supply_price'][$k];
            }
            if(isset($prop['score'])){
                $prop['score'][$k] != null && $prop['score'][$k] >= 0 or $this->error('积分有误');
                $temp['score'] = $prop['score'][$k];
            }

            if(array_key_exists('use_gold',$prop)){
                $prop['use_gold'][$k] != null && $prop['use_gold'][$k] >= 0 or $this->error('最大购物币有误');
                $temp['use_gold'] = $prop['use_gold'][$k];
            }
            $propData[] = $temp;
        }
        return $propData;
    }

    /**
     * 更新产品关联属性的值
     * @ApiSummary (胡正林)
     */
    public static function upd_product_props($reg,$propsObj,$moduleObj,$moduleId){
        $list = $propsObj->where('props','like',"%$reg%")->select();
        $bool = true;
        if(empty($list)){
            return $bool;
        }

        foreach ($list as $v){
            $price = $propsObj->where($moduleId,$v->$moduleId)->where('id','<>',$v->id)->column('price');
            $obj =  $moduleObj->withTrashed()->find($v->$moduleId);

            if(count($price)< 1){
                $obj->has_prop = 0;
                $obj->min_price = $obj->price;
            }else{
                $obj->min_price = min($price);
            }
            $obj->save()!==false or $bool = false;
            $v->delete() or $bool=false;

        }
        return $bool;
    }

}

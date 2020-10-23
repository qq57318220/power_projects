<?php

namespace app\admin\model\template;

use think\Model;


class ConModel extends Model
{
    // 表名
    protected $name = 'template_con';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function page(){
        return $this->hasOne('app\admin\model\template\Page','id','template_page_id');
    }

    //查询模板的所有内容
    public static function getList($id){
        $field = 'c.id,c.position,c.title,c.image,c.is_product,c.product_ids,p.uri,c.link_id';
        return collection(self::alias('c')->field($field)->join('wgc_template_page p','c.template_page_id=p.id','LEFT')
            ->where('c.template_id',$id)->order(['c.position'=>'asc','c.weigh'=>'desc'])->select())->toArray();
    }
    

    



}

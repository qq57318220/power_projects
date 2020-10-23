<?php

namespace app\admin\model\shop;

use think\Model;
use traits\model\SoftDelete;


class Product extends Model
{

    use SoftDelete;
    // 表名
    protected $name = 'product';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'post_status_text',
        'tags_text',
        'has_prop_text'
    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getStatusList()
    {
        return ['online' => __('Status online'), 'offline' => __('Status offline')];
    }

    public function getPostStatusList()
    {
        return ['0' => __('Post_status 0'),'1' => __('Post_status 1'),'2' => __('Post_status 2'),'3' => __('Post_status 3')];
    }

    public function getTagsList()
    {
//        return ['new' => __('Tags new'), 'hot' => __('Tags hot'), 'rush' => __('Tags rush'), 'guss' => __('Tags guss'), 'score' => __('Tags score'), 'recommended' => __('Tags recommended')];
        return ['hot' => __('Tags hot'),'recommended' => __('Tags recommended')];
    }

    public function getHasPropList()
    {
        return ['0' => __('Has_prop 0'), '1' => __('Has_prop 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPostStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['post_status']) ? $data['post_status'] : '');
        $list = $this->getPostStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getTagsTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['tags']) ? $data['tags'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getTagsList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getHasPropTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['has_prop']) ? $data['has_prop'] : '');
        $list = $this->getHasPropList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setTagsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    public static function getMchId($id){
        return self::where('id',$id)->value('merchant_id');
    }

    public function props()
    {
        return $this->hasMany('app\admin\model\shop\product\Prop','product_id');
    }

    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }

}

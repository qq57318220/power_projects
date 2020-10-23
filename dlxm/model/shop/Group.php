<?php

namespace app\admin\model\shop;

use think\Model;
use think\Db;


class Group extends Model
{
    // 表名
    protected $name = 'group';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'has_prop_text'
    ];

    public function getStatusList()
    {
        return ['online' => __('Status online'), 'offline' => __('Status offline')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getAutoSwitchList()
    {
        return ['0' => __('Gender 0'), '1' => __('Gender 1')];
    }

    public function getAutoSwitchTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['gender']) ? $data['gender'] : '');
        $list = $this->getAutoSwitchList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getHasPropList()
    {
        return ['0' => __('Has_prop 0'), '1' => __('Has_prop 1')];
    }

    public function getHasPropTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['has_prop']) ? $data['has_prop'] : '');
        $list = $this->getHasPropList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function product(){
        return $this->hasOne('app\admin\model\shop\Product','id','product_id');
    }

    public function merchant(){
        return $this->hasOne('app\admin\model\Merchant','id','merchant_id');
    }

    /**
     * 团购商品查询
     * @ApiSummary (胡正林)
     * @param string $category_id 产品分类ID
     * @param string $name 产品名称
     * @param array $field 查询字段
     * @param string $extend 产品表扩展条件
     * @param array $where 团购表条件
     * @param array $order 排序
     * @param integer $page_now 页码
     * @param integer $list_rows 每页条数
     */
    public static function product_list($field,$category_id=0,$name=null,$extend='',$where=[],$order=['g.weigh'=>'desc'],$limit=0){
        $pWhere = " and p.post_status='2' and p.deletetime IS NULL";
        $category_id == 0 or $pWhere .= ' and FIND_IN_SET('.$category_id.',p.category_ids)';
       !$name or $pWhere .=' and p.name like "%'.$name.'%"';
        $pWhere .= $extend;
        $where['g.status']  ='online';
        $obj = self::alias('g')->field($field)->join('product p','p.id=g.product_id'.$pWhere)->where($where)
        ->order($order);
        $page = request()->only(['page_now','list_rows']);
        if(!empty($page['list_rows']) && intval($page['list_rows']) > 0){
            return $obj->paginate($page['list_rows'],true,['page'=>@$page['page_now']]);
        }else{
            return $obj->limit($limit)->select();
        }
    }

    /**
     * 团购商品详情
     * @ApiSummary (胡正林)
     * @param string $group_id 团购ID
     */
    public static function product_detail($group_id){
        $field = ['g.*','g.id group_id','p.category_ids','p.name product_name','p.product_sn','p.image','p.product_remark','p.images','p.weight','p.line_price',
            'p.cost_price','p.pro_reward','p.video_file','p.shipping_switch','p.tags','p.vsalesnum','p.rsalesnum','p.content','p.is_as'
            ,'p.shipping_rule_id','r.name rule_name','r.type_list rule_type','r.first_num','r.continue_num','r.first_price','r.continue_price'];
       $info = self::alias('g')->field($field)->join('product p',"p.id=g.product_id and p.status='online' and p.post_status='2' and p.deletetime IS NULL")
            ->join('shipping_rule r',"r.id=p.shipping_rule_id")->where('g.id',$group_id)->find();
       unset($info['has_prop_text']);
       return $info;
    }








}

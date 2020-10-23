<?php

namespace app\admin\model;

use think\Model;


class UserNotification extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'user_notification';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'user_type_text',
        'create_time_text'
    ];
    

    
    public function getTypeList()
    {
        return ['0' => __('Type 0'), '1' => __('Type 1')];
    }

    public function getUserTypeList()
    {
        return ['1' => __('User_type 1'), '2' => __('User_type 2')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getUserTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['user_type']) ? $data['user_type'] : '');
        $list = $this->getUserTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }
    /**
     * 推送通知
     * @param int $type 通知类型:0=系统通知,1=订单通知
     * @param int $user_type 用户类型:1=用户,2=骑手
     * @param int $user_id 用户id
     * @param string $title 通知标题
     * @param string $image 通知封面
     * @param string $content  通知内容
     * @param string $page_uri 前端页面url
     * @param int $order_id  订单id
     * @return bool
     */
    public function pushNotification($type=0,$user_type,$user_id,$title='订单通知',$image,$content,$page_uri='OrderDetail',$order_id)
    {
        $r = self::create([
            'merchant_id'=>1,
            'type'=>1,
            'user_type'=>$user_type,
            'user_id'=>$user_id,
            'title'=>$title,
            'image'=>$image,
            'content'=>$content,
            'page_uri'=>$page_uri,
            'page_id'=>$order_id,
            'is_read'=>0,
            'create_time'=>time()
        ]);
        return $r?true:false;
    }
    /**
     * 推送通知
     * @param int $type 通知类型:0=系统通知,1=订单通知
     * @param int $user_id 用户id
     * @param string $title 通知标题
     * @param string $image 通知封面
     * @param string $content  通知内容
     * @param string $page_uri 前端页面url
     * @param int $order_id  订单id
     * @return bool
     */
    public static function pushNoti($type=0,$user_id,$title='订单通知',$image,$content,$page_uri='OrderDetail',$order_id)
    {
        $r = self::create([
            'merchant_id'=>1,
            'type'=>1,
            'user_id'=>$user_id,
            'title'=>$title,
            'image'=>$image,
            'content'=>$content,
            'page_uri'=>$page_uri,
            'page_id'=>$order_id,
            'is_read'=>0,
            'create_time'=>time()
        ]);
        return $r?true:false;
    }
}

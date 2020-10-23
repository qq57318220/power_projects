<?php

namespace app\admin\model\question;

use think\Db;
use think\Model;


class ExpertReply extends Model
{

    

    

    // 表名
    protected $name = 'question_expert_reply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
        'type_name'
    ];




    public function getTypeNameAttr($value, $data)
    {
        if($data['type'] == 1){
            return Db::name('user')->where('id',$data['type_id'])->field('id,nickname,avatar')->find();
        }else{
            return Db::name('admin')->where('id',$data['type_id'])->field('id,nickname,avatar')->find();
        }
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

    public function getStatusList(){
        return ['0' => '待审核','1' => '审核通过', '2' => '审核驳回'];
    }

    public function getUserList(){
        return ['1' => '用户', '2' => '管理员'];
    }

    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

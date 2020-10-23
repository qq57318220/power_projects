<?php

namespace app\admin\model\service;

use think\Exception;
use think\Model;
use think\Db;


class ServiceRecord extends Model
{


    // 表名
    protected $name = 'service_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'sender_identity_text',
        'message_type_text',
        'status_text',
        'createtime_text',
        'sender_name'
    ];
    

    
    public function getSenderIdentityList()
    {
        return ['0' => __('Sender_identity 0'), '1' => __('Sender_identity 1')];
    }

    public function getMessageTypeList()
    {
        return ['0' => __('Message_type 0'), '1' => __('Message_type 1'), '2' => __('Message_type 2'), '3' => __('Message_type 3')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getCreatetimeTextAttr($value,$data){
        return datetime($data['createtime'],"Y-m-d H:i:s");
    }

    public function getSenderIdentityTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sender_identity']) ? $data['sender_identity'] : '');
        $list = $this->getSenderIdentityList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getMessageTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['message_type']) ? $data['message_type'] : '');
        $list = $this->getMessageTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    public function getSenderNameAttr($value, $data)
    {
        if($data['sender_identity']==0){
            return Db::name('admin')->where('id',$data['sender_id'])->field('id,nickname,avatar')->find();
        }else{
            return Db::name('user')->where('id',$data['sender_id'])->field('id,nickname,avatar')->find();
        }
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * @param $session 会话信息
     * @param $type 用户类型0=客服,1=普通用户
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getRecordList($session,$page=1,$type=1,$maxid=null)
    {
        $filter['session_id'] = ['=',$session['id']];
        $filter['status'] = ['=',0];
        if($type == 0){
            $filter['sender_identity'] = ['=',0];
        }else{
            $filter['sender_id'] = ['=',$session['user_id']];
        }
//        Db::startTrans();
//         try{
            $records = new ServiceRecord();
//            halt($filter);
//            halt($records->where($filter)->select());
            //更新当前会话的消息的状态
            $result =  $records->where($filter)->update(['status'=>1]);

            if(empty($maxid)){

                $records = self::where('session_id',$session['id'])->order('createtime desc')->page($page,20)->select();
            }else{

                $records = self::where('session_id',$session['id'])->where('id','>',$maxid)->order('createtime desc')->page($page,20)->select();
            }



//         }catch (Exception $e){
//             Db::rollback();
//             return false;
//         }
        return  $records;

    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'sender_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'csr_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function merchant(){
//        return $this->belongsTo('app\admin\model\merchant\Merchant','merchant_id','id','LEFT')->setEagerlyType(0);
        return $this->belongsTo('app\admin\model\merchant\User','merchant_id','id','LEFT')->setEagerlyType(0);
    }
}

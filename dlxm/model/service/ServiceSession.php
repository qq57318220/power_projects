<?php

namespace app\admin\model\service;

use think\Db;
use think\Model;
use traits\model\SoftDelete;

class ServiceSession extends Model
{

    use SoftDelete;


    // 表名
    protected $name = 'service_session';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'message_text',
        'csr_name',
        'no_read_num'
    ];
//
//    public static function getSessionList($user_id)
//    {
//       $list =  self::alias('s')
//                    ->join('service_record r','r.session_id = s.id','LEFT')
//                    ->field('s.id,s.csr_id,Max(r.createtime) as create_time,message')
//                    ->select();
//
//       return $list;
//    }
    public function getMessageTextAttr($value,$data)
    {
        return Db::name('service_record')->where('session_id',$data['id'])->order('createtime desc')->value('message');

    }
    public function getNoReadNumAttr($value,$data)
    {
        return Db::name('service_record')->where('session_id',$data['id'])->where('sender_id',$data['csr_id'])->count();

    }

    public function getCsrNameAttr($value,$data)
    {
        return Db::name('admin')->where('id',$data['csr_id'])->value('nickname');
    }

    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
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

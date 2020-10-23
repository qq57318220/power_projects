<?php

namespace app\admin\model\ledger;

use think\Model;


class Journal extends Model
{

    

    

    // 表名
    protected $name = 'ledger_journal';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'create_time_text'
    ];


    public static function getJournalList($id, $type){
        $data = Journal::where(['device_id' => $id, 'type' => $type])
            ->field('id,create_time,type,work_id')
            ->select();

        if($data){
            return $data;
        }
        return FALSE;
    }

    public static function getJournalInfo($id){
        $data = Journal::alias('j')
            ->join('ledger l', 'l.id = j.device_id')
            ->where('j.id', $id)
            ->field('j.*,l.device_contacts,l.device_phone,l.install_address')
            ->find();

        if($data){
            return $data;
        }
        return FALSE;
    }

    
    public function getTypeList()
    {
        return ['1' => __('维保'), '2' => __('维修'), '3' => __('诊断')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
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


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function ledger()
    {
        return $this->belongsTo('app\admin\model\ledger\Ledger', 'device_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

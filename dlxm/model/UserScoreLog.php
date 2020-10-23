<?php

namespace app\admin\model;

use think\Model;


class UserScoreLog extends Model
{

    // 表名
    protected $name = 'user_score_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'mark_text'
    ];
    

    
    public function getMarkList()
    {
        return ['1' => __('Mark 1'), '2' => __('Mark 2')];
    }


    public function getMarkTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['mark']) ? $data['mark'] : '');
        $list = $this->getMarkList();
        return isset($list[$value]) ? $list[$value] : '';
    }


	public function user(){
		return $this->belongsTo('app\admin\model\member\User','user_id','id' );
	}




}

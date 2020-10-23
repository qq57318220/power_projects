<?php

namespace app\admin\model;

use app\admin\model\technician\Technician;
use think\Db;
use think\Model;


class Information extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'information';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text'
    ];






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
	 * @param int $cate 1-工单 2-订单 3-台账
	 * @param int $type 0-用户 1-技术员
	 */
    public static function orderNotice($cate=1,$type=1,$user_id,$work_id,$merchant_id){
    	switch ($cate){
		    case '1':
		    	$content = '您收到新的派单，请及时处理';
		    	break;
		    case '2':
		    	$content = '顾客已下单，请留意订单状态';
			    break;
		    case '3':
		    	$content = '已提交新的台账，请及时处理';
			    break;
	    }
	    $arr = array(
		    'user_id'   => $user_id,
		    'merchant_id'   => $merchant_id,
		    'work_id'   => $work_id,
		    'type'  => $type,
		    'cate'  => $cate,
		    'content'   => $content,
		    'create_time'   => time()
	    );
	    Information::insert($arr);
    }

	/**
	 * @param $type 1=工单 2=订单 3=台账 4=所有
	 * @param $is_read 0=未读 1=已读 2=所有
	 */
    public static function allList($type=4,$is_read=2,$merchant_id = null){
    	$where = '1=1 ';
    	if($merchant_id){
            $where .= " and merchant_id={$merchant_id}";
        };
		if($type != 4){
			$where .= ' and cate='.$type;
		}
		if($is_read != 2){
			$where .= ' and is_read='.$is_read;
		}
		$List = self::where($where)->order('create_time','desc')->select();
		return $List;
    }


}

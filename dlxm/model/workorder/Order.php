<?php

namespace app\admin\model\workorder;

use app\admin\model\User;
use think\Exception;
use think\Model;
use think\Db;

class Order extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'workorder_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_time_text',
        'create_time_text'
    ];
    

    public static function moneyPay($id){
        Db::startTrans();
        try{
            $order_row = Order::where('id', $id)->find();
            $user_row = User::where('id', $order_row['user_id'])->find();
            $surplus_money = $user_row['money'] - $order_row['price'];
            $user_result = User::where('id', $order_row['user_id'])->update(['money' => $surplus_money]);
            if($user_result){
                Workorder::where('id', $order_row['work_id'])->update(['handle_status' => 3]);
                $user_result = Order::where('id', $id)->update(['pay_status' => 1, 'pay_type' => 3, 'pay_time' => time()]);
                if($user_result){
                    Db::commit();
                    return TRUE;
                }
            }
        }catch (Exception $e){
            Db::rollback();
            return FALSE;
        }
    }



    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'merchant_id', 'merchant_id', [], 'LEFT')->setEagerlyType(0);
    }


    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function workorder()
    {
        return $this->belongsTo('app\admin\model\workorder\Workorder', 'work_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

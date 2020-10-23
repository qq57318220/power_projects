<?php

namespace app\admin\model\workorder;

use app\admin\model\ledger\Ledger;
use app\admin\model\technician\Technician;
use app\admin\model\User;
use app\admin\model\Merchant;
use think\Exception;
use think\Model;
use think\Db;

class Workorder extends Model
{





    // 表名
    protected $name = 'workorder';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'workorder_type_text',
        'launch_time_text',
        'quote_time_text',
        'revoke_time_text',
        'receipt_time_text',
        'repair_time_text',
        'complete_time_text'
    ];

    //商户未响应
    public static function merchantNoRes($time, $type){
        Db::startTrans();
        try{
            if($type == 1){
                $handle_status = 1;
                $update_handle_status = 7;
            }else if($type == 2){
                $handle_status = 3;
                $update_handle_status = 9;
            }
            $data = Workorder::where('launch_time', '<', $time)
                ->where('handle_status', $handle_status)
                ->select();
            $data = collection($data)->toArray();
            foreach($data as $key => &$val){
                Workorder::where('id', $val['id'])->update(['handle_status' => $update_handle_status]);
                $val['admin_id'] = 1;
                $val['launch_time'] = time();
                unset($val['id']);
                unset($val['workorder_type_text']);
                unset($val['launch_time_text']);
                unset($val['revoke_time_text']);
                unset($val['quote_time_text']);
                unset($val['receipt_time_text']);
                unset($val['repair_time_text']);
                unset($val['complete_time_text']);
            }
            //print_r($data);exit;
            $result = Workorder::insertAll($data);
            if($result){
                Db::commit();
                return TRUE;
            }
        }catch (Exception $e){
            Db::rollback();
            return FALSE;
        }

    }

    //创建台账类型工单
    public static function createLedgerTypeWork($params, $transferred_order_id = 0){
        $merchant_id = Ledger::where('id', $params['ledger_id'])->value('merchant_id');
        $area = explode('/',$params['city']);
        list($params['province'],$params['city'],$params['district']) = $area;

        $merchant_phone = Merchant::where('id',$merchant_id)->value('tel');
        if($transferred_order_id > 0){
            $params['complete_type'] = 1;
        }
        $data = [
            'merchant_id' => $merchant_id,
            'user_id' => $params['user_id'],
            'ledger_id' => $params['ledger_id'],
            'technician_id' => 0,
            'complete_type' => $params['complete_type'],
            'workorder_type' => $params['work_type'],
            'device_name' => $params['device_name'],
            'province' => $params['province'],
            'city' => $params['city'],
            'district' => $params['district'],
            'address' => $params['address'],
            'remark' => $params['remark'],
            'workorder_price' => $params['workorder_price'],
            'user_name' => $params['user_name'],
            'user_phone' => $params['user_phone'],
            'launch_type' => 1,
            'launch_role' => 1,
            'launch_time' => time(),
            'merchant_phone'    => $merchant_phone,
            'handle_status' => 1
        ];

        if($params['technician_id'] > 0){
            $technician = Technician::field('name,phone')->where('id',$params['technician_id'])->find();
            $data['technician_name'] = $technician['name'];
            $data['technician_phone'] = $technician['phone'];
        }
        if($transferred_order_id == 1){
            //Workorder::where('id', $params['id'])->update(['transferred_order_status' => 1]);
            $data['transferred_order_id'] = $params['id'];
            $data['transferred_order_status'] = 1;
            $data['workorder_type'] = '3';
        }
        $result = Workorder::create($data);

        return $result;
    }

    //创建商家类型工单
    public static function createMerchantTypeWork($params, $transferred_order_id = 0){
        $area = explode('/',$params['city']);
        list($params['province'],$params['city'],$params['district']) = $area;
        if($params['merchant_id'] > 0){
            $merchant_phone = Merchant::where('id',$params['merchant_id'])->value('tel');
        }else{
            $merchant_phone = '';
        }
        if($transferred_order_id > 0){
            $params['complete_type'] = 1;
        }
        $data = [
            'merchant_id' => $params['merchant_id'],
            'user_id' => $params['user_id'],
            'ledger_id' => 0,
            'technician_id' => 0,
            'complete_type' => $params['complete_type'],
            'workorder_type' => $params['work_type'],
            'device_name' => $params['device_name'],
            'province' => $params['province'],
            'city' => $params['city'],
            'district' => $params['district'],
            'address' => $params['address'],
            'remark' => $params['remark'],
            'workorder_price' => $params['workorder_price'],
            'user_name' => $params['user_name'],
            'user_phone' => $params['user_phone'],
            'launch_type' => 2,
            'launch_role' => 1,
            'launch_time' => time(),
            'merchant_phone'    => $merchant_phone,
            'handle_status' => 1
        ];
        if($params['technician_id'] > 0){
            $technician = Technician::field('name,phone')->where('id',$params['technician_id'])->find();
            $data['technician_name'] = $technician['name'];
            $data['technician_phone'] = $technician['phone'];
        }
        if($transferred_order_id == 1){
            //Workorder::where('id', $params['id'])->update(['transferred_order_status' => 1]);
            $data['transferred_order_id'] = $params['id'];
            $data['transferred_order_status'] = 1;
            $data['workorder_type'] = '3';
        }
        $result = Workorder::create($data);

        return $result;
    }

    //创建技术员类型工单
    public static function createTechnicianTypeWork($params, $transferred_order_id = 0){
        $area = explode('/',$params['city']);
        list($params['province'],$params['city'],$params['district']) = $area;
        if($transferred_order_id > 0){
            $params['complete_type'] = 1;
        }
        $data = [
            'merchant_id' => 0,
            'user_id' => $params['user_id'],
            'ledger_id' => 0,
            'technician_id' => $params['technician_id'],
            'complete_type' => $params['complete_type'],
            'workorder_type' => $params['work_type'],
            'device_name' => $params['device_name'],
            'province' => $params['province'],
            'city' => $params['city'],
            'district' => $params['district'],
            'address' => $params['address'],
            'remark' => $params['remark'],
            'workorder_price' => $params['workorder_price'],
            'user_name' => $params['user_name'],
            'user_phone' => $params['user_phone'],
            'launch_type' => 2,
            'launch_role' => 2,
            'launch_time' => time(),
            'handle_status' => 4,
            'merchant_phone'    => '',
            'technician_handle_status'  => 1
        ];
        if($params['technician_id'] > 0){
            $technician = Technician::field('name,phone')->where('id',$params['technician_id'])->find();
            $data['technician_name'] = $technician['name'];
            $data['technician_phone'] = $technician['phone'];
        }
        if($transferred_order_id == 1){
            //Workorder::where('id', $params['id'])->update(['transferred_order_status' => 1]);
            $data['transferred_order_id'] = $params['id'];
            $data['transferred_order_status'] = 1;
            $data['workorder_type'] = '3';
        }
        $result = Workorder::create($data);

        return $result;
    }

	//获取完成类型工单
	public static function getCompleteTypeWork($status, $user_id){
		$data = Workorder::where('wgc_workorder.user_id', $user_id)
             ->where('complete_type', $status)
             ->where('launch_type','neq',3)
             ->join('workorder_order b','wgc_workorder.id = b.work_id','left')
             ->field('b.id as pay_id,wgc_workorder.id,wgc_workorder.handle_status,wgc_workorder.workorder_type,wgc_workorder.device_name,wgc_workorder.revoke_time,wgc_workorder.revoke_reason,wgc_workorder.receipt_time,wgc_workorder.complete_time,wgc_workorder.technician_handle_status,wgc_workorder.launch_role,wgc_workorder.launch_type,wgc_workorder.repair_time')
             ->group('wgc_workorder.id')
             ->select();

		if($data){
			return $data;
		}else{
			return [];
		}
		return FALSE;
	}

    //获取工单详情
    public static function getWorkOrderDetail($id, $handle_status){
        if($handle_status == 1){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_price';
        }else if($handle_status == 2){
            $field = 'id,merchant_id,workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image';
        }else if($handle_status == 3){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,launch_time';
        }else if($handle_status == 4){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,launch_time';
        }else if($handle_status == 5){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,launch_time,technician_name,technician_phone,receipt_time,repair_time';
        }else if($handle_status == 6){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,revoke_time,revoke_reason';
        }else if($handle_status == 7){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,launch_time,technician_name,technician_phone,receipt_time,repair_time,revoke_time,revoke_reason';
        }else if($handle_status == 8){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,launch_time,technician_name,technician_phone,receipt_time,repair_time,now_workorder_price,complete_time,bill_of_materials';
        }
        $data = Workorder::where('id', $id)
            ->field($field)
            ->find();

        if($data){
            return $data;
        }
        return FALSE;
    }

    //转单
    public static function setTransferredOrder($id, $launch_type){
        $data = Workorder::where('id', $id)->find();
        $data['city'] = $data['province'].'/'.$data['city'].'/'.$data['district'];
        $data['work_type'] = $data['workorder_type'];
        if($launch_type == 1){
            $result = self::createLedgerTypeWork($data, $transferred_order_id = 1);
        }else if($launch_type == 2){
            if($data['launch_role'] == 1){
                $result = self::createMerchantTypeWork($data, $transferred_order_id = 1);
            }else if($data['launch_role'] == 2){
                $result = self::createTechnicianTypeWork($data, $transferred_order_id = 1);
            }
        }

        return $result;
    }

    //撤销用户工单
    public static function revokeUserWorkOrder($param){
        //$work_row = Workorder::where('id', $param['id'])->find();
        $nick_name = User::where('id', $param['user_id'])->value('nickname');
        $result = Workorder::where('id', $param['id'])->update([
            'complete_type' => 2,
            'handle_status' => 7,
            'revoke_person' => $nick_name,
            'revoke_time'   => time(),
            'revoke_reason' => $param['revoke_reason']
        ]);

        if($result){
            return TRUE;
        }
        return FALSE;
    }

    //获取技术员完成类型工单
    public static function getTechnicianCompleteTypeWork($status, $user_id){
        $tid = Technician::where(['user_id' => $user_id, 'status' => 2])->value('id');
        if($status == 1){
            $where['complete_type'] = $status;
            $where['technician_handle_status'] = array('neq',1);
        }else if($status == 3){
            $where['complete_type'] = $status;
            // $where['technician_handle_status'] = 4;
        }
        $data = Workorder::where('technician_id', $tid)
            ->where($where)
            ->field('id,technician_handle_status,workorder_type,device_name,receipt_time,repair_time,revoke_reason,revoke_time,complete_time,launch_type,launch_role,handle_status')
            ->order('id desc')
            ->select();

        if($data){
            return $data;
        }
        return FALSE;
    }

    //获取技术员工单详情
    public static function getTechnicianWorkOrderDetail($id, $technician_handle_status){
        if($technician_handle_status == 1){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price';
        }else if($technician_handle_status == 2){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,launch_time,receipt_time,repair_time';
        }else if($technician_handle_status == 3){
            $field = 'workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,launch_time,receipt_time,repair_time,revoke_time';
        }
        $data = Workorder::where('id', $id)
            ->field($field)
            ->find();

        if($data){
            return $data;
        }
        return FALSE;
    }

    //获取技术员端首页列表
    public static function getTechnicianIndexList($id, $status){
        if($status == 1){
            $field = 'id,workorder_type,device_name,province,city,district,address,technician_handle_status,launch_type,launch_role';
        }else if($status == 2){
            $field = 'id,workorder_type,device_name,repair_time,technician_handle_status,launch_type,launch_role';
        }

        $data = Workorder::where('technician_id', $id)
            ->where('launch_type','neq',3)
            ->where('complete_type',1)
            ->where('technician_handle_status',$status)
            ->field($field)
            ->select();

        if($data){
            return $data;
        }
        return FALSE;
    }

    //获取技术员首页信息
    public static function getTechnicianIndexInfo($id, $status){
        if($status == 1){
            $field = 'id,merchant_id,ledger_id,workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,launch_role';
        }else if($status == 2){
            $field = 'id,merchant_id,ledger_id,workorder_type,device_name,user_name,user_phone,province,city,district,address,remark,workorder_update_price,merchant_phone,quote_image,repair_time,launch_role';
        }

        $data = Workorder::where('id', $id)
            ->field($field)
            ->find();

        if($data){
            return $data;
        }
        return FALSE;
    }

    //撤销技术员工单
    public static function revokeTechnicianWorkOrder($param){
        //$work_row = Workorder::where('id', $param['id'])->find();
        $nick_name = User::where('id', $param['user_id'])->value('nickname');
        $result = Workorder::where('id', $param['id'])->update([
            'technician_handle_status' => $param['status'],
            'revoke_person' => $nick_name,
            'revoke_time'   => time(),
            'revoke_reason' => $param['revoke_reason']
        ]);
        if($result){
            return TRUE;
        }
        return FALSE;
    }

    public static function revokeWorkOrder($id, $type){

    }

    public static function technicianNoRes($time){

    }


    public static function getWorkorderTypeList()
    {
        return ['1' => __('诊断工单'), '2' => __('维保工单'), '3' => __('维修工单')];
    }

    public function getCompleteTypeList()
    {
        return ['1' => __('未完成'), '2' => __('撤销'), '3' => __('完成')];
    }


    public function getWorkorderTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['workorder_type']) ? $data['workorder_type'] : '');
        $list = $this->getWorkorderTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getLaunchTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['launch_time']) ? $data['launch_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getQuoteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['quote_time']) ? $data['quote_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRevokeTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['revoke_time']) ? $data['revoke_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getReceiptTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['receipt_time']) ? $data['receipt_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getRepairTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['repair_time']) ? $data['repair_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCompleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['complete_time']) ? $data['complete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setLaunchTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setQuoteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRevokeTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setReceiptTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setRepairTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCompleteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'merchant_id', 'merchant_id', [], 'LEFT')->setEagerlyType(0);
    }

    public function merchant()
    {
        return $this->belongsTo('app\admin\model\Merchant', 'merchant_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

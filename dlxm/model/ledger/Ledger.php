<?php

namespace app\admin\model\ledger;

use think\Loader;
use think\Model;


class Ledger extends Model
{

    

    

    // 表名
    protected $name = 'ledger';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'factory_time_text',
        'commission_time_text',
        'maintenance_time_text',
        'create_time_text'
    ];
    

    public static function getLedgers($type, $user_id){
        $device_ids = Authorize::where(['type' => $type, 'user_id' => $user_id])->column('device_id');
        $data = Ledger::alias('l')
            ->join('merchant m', 'm.id = l.merchant_id')
            ->join('shop_order o', 'o.order_sn = l.order')
            ->where('l.id', 'in', $device_ids)
            ->field('l.id,l.device_name,l.device_number,l.ledger_image,l.specification,l.install_address,m.name,o.pay_time,l.order')
            ->select();

        if($data){
            return $data;
        }
        return FALSE;
    }

    public static function getLedgerInfo($id){
        $data = Ledger::alias('l')
            ->join('shop_order o', 'o.order_sn = l.order')
            ->where('l.id', $id)
            ->field("l.*,FROM_UNIXTIME(o.pay_time,'%Y-%m-%d') as pay_time")
            ->find();

        if($data){
            return $data;
        }
        return FALSE;
    }

    public static function getWarningInfo($id){
        $data = Ledger::where('id', $id)
            ->where('id', $id)
            ->field('warning_identification,warning_product_name,warning_images,warning_remark')
            ->find();

        if($data){
            return $data;
        }
        return FALSE;
    }

    
    public static function createData($params){
        $test = Ledger::create($params);
        var_dump($test);
    }


    public function getFactoryTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['factory_time']) ? $data['factory_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCommissionTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['commission_time']) ? $data['commission_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getMaintenanceTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['maintenance_time']) ? $data['maintenance_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFactoryTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCommissionTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setMaintenanceTimeAttr($value)
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


    public function powercustomer()
    {
        return $this->belongsTo('app\admin\model\power\Customer', 'customer_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function powermechanism()
    {
        return $this->belongsTo('app\admin\model\power\Mechanism', 'mechanism_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function powerproduct()
    {
        return $this->belongsTo('app\admin\model\power\Product', 'product_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 生成小程序二维码
     * 参考文档  https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.getUnlimited.html#HTTPS%20%E8%B0%83%E7%94%A8
     *
     */
    public function urlToQRcode($url='',$scene='')
    {
//        $url = $this->request->post('page');//页面路径
//        $scene = $this->request->post('scene');//附带的参数
//        $scene = "1_1";

        if ($url == '' || $scene == '') {
            $this->error('参数错误');
        }
        Loader::import('wxpay.Wxpay');
        $Mwxpay = new \Wxpay();

        $appid = 'wxe2d73eb6ac2b9a33';  // 小程序ID 生产环境
        $secret = '7086f380c6c24f363101e0980d7d4aa5';  // 小程序私钥 生产环境
        $wxurl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
        $res = file_get_contents($wxurl);
        $res = json_decode($res, true);
        $access_token = $res['access_token'];
        $postData = json_encode([
            'scene' => $scene,
            'page'  => $url,
            'width' => '430'
        ], JSON_UNESCAPED_SLASHES);
        $postUrl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
        );
        $jpg = $Mwxpay->post($postUrl, $postData,$headers);
        $filename = time() . rand(1000, 9999) . ".jpg";//要生成的图片名字
        //保存到服务器
        $path = ROOT_PATH."public" . DS . "qrcode" . DS . $filename;
        $file = fopen(ROOT_PATH."public" . DS . "qrcode" . DS . $filename, "w");//打开文件准备写入
        fwrite($file, $jpg);//写入
        fclose($file);//关闭
        $thepath = str_replace('\\', "/", $path);//存储好的二维码路径

        return '/qrcode/'.$filename;

    }

}

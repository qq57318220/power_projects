<?php

namespace app\admin\command;

use app\admin\model\ShopOrder;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoOrderStatus extends Command
{
    protected function configure()
    {
        $this
            ->setName('AutoOrderStatus')
            ->setDescription('Auto change order status 3_to_4 And 4_to_5');
    }

    protected function execute(Input $input, Output $output)
    {
        $now_time = time();
        $order_model = new ShopOrder();

        //3_to_4
        $order_status_3_to_4_day = Config::get('site.order_status_3_to_4_day');
        $where_send['status'] = 3;
        $where_send['send_time'] = ['<', $now_time - $order_status_3_to_4_day * 24 * 60 * 60];
        $list_send = collection($order_model->where($where_send)->select())->toArray();
        if( is_array($list_send) && count($list_send) ){
            $update = [];
            foreach($list_send as $k=>$v){
                $update[] = ['id' => $v['id'],'status'=>4,'set_wait_finish_time'=>$now_time];
            }
            $order_model->saveAll($update);
        }

        //4_to_5
        $order_status_4_to_5_day = Config::get('site.order_status_4_to_5_day');
        $where['status'] = 4;
        $where['set_wait_finish_time'] = ['<', $now_time - $order_status_4_to_5_day * 24 * 60 * 60];
        $list = collection($order_model->where($where)->select())->toArray();
        $shop_order_model = new ShopOrder();
        if( is_array($list) && count($list) ){
            $update = [];
            foreach($list as $k=>$v){
//                $update[] = ['id' => $v['id'],'status'=>5,'finish_time'=>$now_time];
                $shop_order_model->set_finish($v['id']);
            }
            $order_model->saveAll($update);
        }
    }
}
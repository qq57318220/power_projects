<?php

namespace app\admin\controller;

use app\admin\model\shop\Product;
use app\admin\model\User;
use app\api\model\shop\Order;
use app\api\model\shop\OrderShipping;
use app\common\controller\Backend;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{
    /**
 * 查看
 */
    public function index()
    {
//        halt($this->request->get('merchant_id'));
        $filter =[];
        if($this->auth->merchant_id == 0){
            $filter['merchant_id'] = input('merchant_id')?:1;
        }else{
            $filter['merchant_id'] = $this->auth->merchant_id;
        }
        $this->assign('merchant_id',$filter['merchant_id']);
        
        $mWhere = $this->auth->merchant_id ==0?[]:['id'=>$this->auth->merchant_id];
        $list = \app\admin\model\Merchant::where($mWhere)->select();
        $this->view->assign('merchantList',$list);
        $day_limit = 7;
        if ($this->request->isAjax()) {
            $day_limit = $this->request->param('day');
        }
        $order_model = new Order();
        $user_model = new User();
        $product_model = new Product();
        $as_order_model = new \app\admin\model\AfterSale();
        $order_shipping_model = new OrderShipping();
        $top['today_trade_count'] = $order_model
            ->where($filter)
            ->where('status','in','2,3,4,5')
            ->whereTime('pay_time', 'today')
            ->sum('pay_amount');//今日交易额

        $top['yesterday_trade_count'] = $order_model
            ->where($filter)
            ->where('status','in','2,3,4,5')
            ->whereTime('pay_time', 'yesterday')
            ->sum('pay_amount');//昨日交易额

        $top['total_order'] = $order_model->where($filter)->count();//总订单数
        $top['total_user'] = $user_model->count();//总会员数
        $top['total_tgy'] = $user_model->where(function ($query) {
            $query->where('grade','>', 1)->whereor('team_grade','>', 1);
        })->count();//推广员数
        $top['total_product'] = $product_model->where($filter)->count();//总商品数

        $this->assign('top', $top);

        $seventtime = \fast\Date::unixtime('day', -$day_limit);
        if ($this->request->isAjax()) {
            $search_begin_time = $this->request->param('search_begin_time');
            $search_end_time = $this->request->param('search_end_time');
            if( $search_begin_time && $search_end_time){
                $day_limit = (strtotime($search_end_time) - strtotime($search_begin_time)) / 86400;
                $seventtime = strtotime( $search_begin_time );
            }
        }
        $second = [];
        $four = [];
        for ($i = 0; $i <= $day_limit; $i++)
        {
            $day = date("m-d", $seventtime + ($i * 86400));
            $unix_time_begin = strtotime(date("Y-m-d 00:00:00", $seventtime + ($i * 86400)));
            $unix_time_end = strtotime(date("Y-m-d 00:00:00", $seventtime + ($i + 1) * 86400));
            $second['left']['paylist'][$day] = $order_model
                ->where($filter)
                ->where('status','=',5)
                ->whereTime('finish_time',[$unix_time_begin,$unix_time_end])->count();

            $second['left']['createlist'][$day] = $order_model
                ->where($filter)
                ->whereTime('createtime',[$unix_time_begin,$unix_time_end])->count();

            $four['right_up'][$day] = $user_model
                ->whereTime('jointime',[$unix_time_begin,$unix_time_end])->count();

            $four['right_down'][$day] = $order_model
                ->where($filter)
                ->where('status','=',5)
                ->whereTime('finish_time',[$unix_time_begin,$unix_time_end])->sum('pay_amount');
        }
//        dump($filter);
//	    halt($second);

        $begin_time = strtotime(date("Y-m-d 00:00:00", $seventtime));
        $end_time = strtotime(date("Y-m-d 00:00:00", $seventtime+ ($day_limit * 86400)));
        $second['right']['today_user'] = $user_model->whereTime('jointime','today')->count();//今日注册
        $second['right']['total_as_order'] = $as_order_model->where($filter)->whereTime('createtime',[$begin_time,$end_time])->where('as_status','<','4')->count();//待处理售后订单
        $second['right']['today_order'] = $order_model->where($filter)->whereTime('createtime','today')->count();//今日订单
        $second['right']['wait_deliver'] = $order_model->where($filter)->whereTime('createtime',[$begin_time,$end_time])->where('status','=',2)->where('is_lock','=',1)->count();//未发货订单

        $begin_time_seven = strtotime(date("Y-m-d 00:00:00", \fast\Date::unixtime('day', -7)));
        $end_time_seven = strtotime(date("Y-m-d 00:00:00"));
        $second['right']['seven_new_order'] = $order_model->where($filter)->whereTime('createtime',[$begin_time_seven,$end_time_seven])->count();//七日新增订单
        $second['right']['seven_new_user'] = $user_model->whereTime('jointime',[$begin_time_seven,$end_time_seven])->count();//七日新增会员

        $this->assign('second',$second);

        $color = ['bg-blue', 'bg-aqua-gradient', 'bg-purple-gradient', 'bg-green-gradient'];
        $this->assign('color',$color);
        $third = $order_shipping_model
            ->where($filter)
            ->field('s.city,count(s.id) as count,sum(o.pay_amount) pay_count')
            ->alias('s')
            ->join("wgc_shop_order o",'o.id=s.order_id')
            ->where("o.pay_status=2")
            ->whereNotNull('s.city')->group('s.city')->order('count','desc')->limit(4)->select();
        $this->assign('third',$third);

        $four['left'][] = ['value'=>$user_model->where(function ($query) {
            $query->where('grade','>', 1)->whereor('team_grade','>', 1);
        })->whereTime('jointime',[$begin_time,$end_time])->count(),'name'=>'推广员'];
        $four['left'][] = ['value'=>$user_model->where(function ($query) {
            $query->where('grade','>', 1)->whereor('team_grade','>', 1);
        })->whereTime('jointime',[$begin_time,$end_time])->count(),'name'=>'普通会员'];

        $this->assign('four',$four);

        $order_left[] = ['value'=>$order_model->where($filter)->whereTime('createtime',[$begin_time,$end_time])->count(),'name'=>'总订单数'];
        $order_left[] = ['value'=>$order_model->where($filter)->whereTime('finish_time',[$begin_time,$end_time])->where('status','=',5)->count(),'name'=>'已完成订单数'];

        $this->assign('order_left',$order_left);

        if ($this->request->isAjax()) {
            $this->success('调用成功','',[
                'second'=>[
                    'left'=>
                        [
                            'column' => array_keys($second['left']['paylist']),
                            'paydata' => array_values($second['left']['paylist']),
                            'createdata' => array_values($second['left']['createlist'])
                        ],
                    'right'=>$second['right']
                ],
                'order_left'=>$order_left,
                'four_right_up'=>[
                    'column' => array_keys($four['right_up']),
                    'data' => array_values($four['right_up'])
                ],
                'four_right_down' => [
                    'column' => array_keys($four['right_down']),
                    'data' => array_values($four['right_down'])
                ],
                'four'=>$four,
                'top'=>$top
            ]);
        }else{
            return $this->view->fetch();
        }
    }

    //选择具体两个日期
    public function ajax_time(){

    }

}

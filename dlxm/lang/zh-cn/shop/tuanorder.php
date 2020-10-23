<?php

return [
    'Id'                     => 'ID',
    'Order_sn'               => '订单编号',
    'Out_trade_no'           => '商户订单号,用于发起支付',
    'Transaction_id'         => '支付交易号,支付平台返回',
    'Buyer_id'               => '买家ID',
    'Order_type'             => '订单类型',
    'Order_type 1'           => '普通单',
    'Order_type 2'           => '团购单',
    'Tuan_id'                => '团购ID',
    'Is_lock'                => '是否被锁',
    'Is_lock 1'              => '未锁',
    'Is_lock 2'              => '已锁',
    'Status'                 => '订单状态',
    'Status 1'               => '待付款',
    'Status 2'               => '待发货',
    'Status 3'               => '待收货',
    'Status 4'               => '待完成',
    'Status 5'               => '已完成',
    'Status 6'               => '交易关闭',
    'Dowm_reason'            => '订单取消原因',
    'Pay_status'             => '付款状态',
    'Pay_status 1'           => '未付款',
    'Pay_status 2'           => '已付款',
    'Pay_time'               => '付款时间',
    'Last_pay_time'          => '最后付款时间,如果超过这个时间还没有付款,订单失效',
    'Payment_id'             => '支付方式ID',
    'Payment_name'           => '支付方式名称',
    'User_coupon_id'         => '用户优惠券ID',
    'Coupon_total'           => '优惠券抵扣金额',
    'Shipping_ment'          => '配送方式',
    'Shipping_ment 1'        => '配送上门',
    'Shipping_ment 2'        => '到店自提',
    'Order_amount'           => '订单总额',
    'Freight_amount'         => '邮费总额',
    'Pay_amount'             => '付款总额',
    'Refund_amount'          => '已退款总金额',
    'Reward_amount'          => '推广费用总额',
    'Buyer_message'          => '买家留言',
    'Createtime'             => '创建时间',
    'Shoptuan.status'        => '拼团状态',
    'Shoptuan.status 0'      => '待付款',
    'Shoptuan.status 1'      => '待成团',
    'Shoptuan.status 2'      => '已成团',
    'Shoptuan.status 3'      => '拼团失败',
    'Shoptuan.group_size'    => '成团人数',
    'Shoptuan.join_num'      => '已加入人数',
    'Shoptuan.pay_num'       => '已付款人数',
    'Shoptuan.auto_switch'   => '模拟成团',
    'Shoptuan.auto_switch 1' => '是',
    'Shoptuan.auto_switch 0' => '否',
    'Shoptuan.last_time'     => '最晚成团时间',
    'User.nickname'          => '昵称',
    'User.avatar'            => '头像'
];

<?php

namespace app\admin\command;

use app\api\controller\Group;
use app\api\controller\Raise;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoTuanOrder extends Command
{
    protected function configure()
    {
        $this
            ->setName('AutoTuanOrder')
            ->setDescription('团订单的自动化处理');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(Group::auto_cancel_order());//系统自动取消未支付团购订单
        $output->writeln(Group::timeout_tuan());//超时未成团处理
        $output->writeln(Raise::timeout());//超时未众筹成功退款处理
    }
}
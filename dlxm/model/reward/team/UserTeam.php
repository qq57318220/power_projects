<?php

namespace app\admin\model\reward\team;

use think\Model;


class UserTeam extends Model
{

    // 表名
    protected $name = 'user_team';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}

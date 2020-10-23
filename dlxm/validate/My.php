<?php
namespace app\admin\validate;
use think\Validate;
class My extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'username' => 'require|max:50|unique:admin',
        'email'    => 'require|email|unique:admin,email',
    ];

    /**
     * 提示消息
     */
    protected $message = [
	 	'username.unique'=>'你没有资格用admin名字'
    ];

    /**
     * 字段描述
     */
    protected $field = [
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['username', 'email', 'nickname', 'password'],
        'edit' => ['username', 'email', 'nickname'],
    ];

}

<?php
namespace app\admin\validate;
use think\Validate;
class Mytest extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require',
        'intro'    => 'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
	 	'name.require'=>'空空的不行'
		//'name.require'=>'空空的不行'
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
        'add'  => ['name', 'intro'],
        'edit' => ['name', 'intro'],
    ];

}

<?php

namespace app\admin\controller\cbc;

use app\cbc\model\Admin;
use app\cbc\model\AuthGroup;
use app\cbc\model\AuthGroupAccess;
use app\cbc\model\Config;
use app\common\controller\Backend;
use fast\Random;
use think\Db;
use think\Env;
use think\Exception;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Merchant extends Backend
{
    
    /**
     * Merchant模型对象
     * @var \app\admin\model\cbc\Merchant
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cbc\Merchant;
        $this->view->assign("collectionModeList", $this->model->getCollectionModeList());
    }

    public function getSearchList(){
        $list = $this->model->select();
        return $list;
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $result = [];
                Db::connect('database_cbc')->startTrans();
                try {
                    //添加后台账号
                    $admin_info['username'] = $params['username'];
                    $admin_info['nickname'] = $params['username'];
                    $admin_info['salt'] = Random::alnum();
                    $admin_info['password'] = md5(md5($params['password']) . $admin_info['salt']);
                    $admin_info['avatar'] = '/assets_cbc/img/avatar.png'; //设置新管理员默认头像。
                    $admin_info['createtime'] = time();
                    $admin_info['updatetime'] = time();
                    $admin_model = new Admin();
                    $new_admin_id = $admin_model->insertGetId($admin_info);
                    if ($new_admin_id === false){
                        $result[] = false;
                    }

                    //添加商户信息
                    $merchant_model = new \app\admin\model\cbc\Merchant();
                    $merchant['admin_id'] = $new_admin_id;
                    $merchant['name'] = $params['name'];
                    $has_exist = $merchant_model::withTrashed()->where('name','=',$params['name'])->find();
                    if($has_exist){
                        Db::connect('database_cbc')->rollback();
                        $this->error('商户名已经存在');
                    }
                    $merchant['phone'] = $params['phone'];
                    $merchant['lng'] = $params['lng'];
                    $merchant['lat'] = $params['lat'];
                    $merchant['category_id'] = $params['category_id'];
                    $merchant['collection_mode'] = $params['collection_mode'];
                    $merchant['createtime'] = time();
                    $merchant['updatetime'] = time();
                    $new_merchant_id = $merchant_model->insertGetId($merchant);
                    if ($new_merchant_id === false){
                        $result[] = false;
                    }

                    //更新后台账号的merchant_id
                    $admin_model->where('id','=',$new_admin_id)->update(['merchant_id'=>$new_merchant_id]);

                    //添加一个默认的超级管理员用户组
                    $auth_group['merchant_id'] = $new_merchant_id;
                    $auth_group['pid'] = 0;
                    $auth_group['name'] = '超级管理员';
                    $auth_group['rules'] = '*';
                    $auth_group['createtime'] = time();
                    $auth_group['updatetime'] = time();
                    $auth_group['status'] = 'normal';
                    $auth_group_model = new AuthGroup();
                    $new_group_id = $auth_group_model->insertGetId($auth_group);
                    if ($new_group_id === false){
                        $result[] = false;
                    }

                    //给后台账号赋予超级管理员权限
                    $auth_group_access['uid'] = $new_admin_id;
                    $auth_group_access['group_id'] = $new_group_id;
                    $auth_group_access_model = new AuthGroupAccess();
                    $new_auth_group_access = $auth_group_access_model->insert($auth_group_access);
                    if ($new_auth_group_access === false){
                        $result[] = false;
                    }

                    $result[] = copy(
                        APP_PATH . DS . 'extra' . DS . 'site_cbc.php',
                        APP_PATH . 'extra' . DS . 'site_cbc_'.$new_merchant_id.'.php'
                    );

                    $configModel = new Config();
                    $result[] = $configModel->saveAll([
                        ['merchant_id'=>$new_merchant_id,'name'=>'name','group'=>'basic','title'=>'Site name','tip'=>'请填写站点名称','type'=>'string','value'=>'城市商圈后台','content'=>'','rule'=>'require','extend'=>''],
                        ['merchant_id'=>$new_merchant_id,'name'=>'configgroup','group'=>'dictionary','title'=>'Config group','tip'=>'','type'=>'array','value'=>'{"basic":"Basic"}','content'=>'','rule'=>'','extend'=>''],
                        ['merchant_id'=>$new_merchant_id,'name'=>'cdnurl','group'=>'basic','title'=>'Cdn url','tip'=>'如果静态资源使用第三方云储存请配置该值','type'=>'string','value'=>'','content'=>'','rule'=>'','extend'=>''],
                        ['merchant_id'=>$new_merchant_id,'name'=>'fixedpage','group'=>'basic','title'=>'Fixed page','tip'=>'请尽量输入左侧菜单栏存在的链接','type'=>'string','value'=>'1.0.1','content'=>'','rule'=>'required','extend'=>''],
                        ['merchant_id'=>$new_merchant_id,'name'=>'version','group'=>'basic','title'=>'Version','tip'=>'如果静态资源有变动请重新配置该值','type'=>'string','value'=>'dashboard','content'=>'','rule'=>'required','extend'=>''],
                    ]);

                    if (checkRes($result)) {
                        Db::connect('database_cbc')->commit();
                        $this->success();
                    } else {
                        Db::connect('database_cbc')->rollback();
                        $this->error();
                    }
                } catch (Exception $e) {
                    Db::connect('database_cbc')->rollback();
                    $this->error($e->getMessage() . $e->getLine());
                }
            }
        }
        return $this->view->fetch();
    }
}

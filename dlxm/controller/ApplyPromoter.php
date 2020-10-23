<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 用户申请推广员身份记录管理
 *
 * @icon fa fa-circle-o
 */
class ApplyPromoter extends Backend
{
    
    /**
     * ApplyPromoter模型对象
     * @var \app\admin\model\ApplyPromoter
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ApplyPromoter;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    //通过审核
    public function examine($id){
        Db::startTrans();
        try{
            $apply_info = $this->model->where('id','=',$id)->find();
            if(!$apply_info){
                $this->error('数据不存在');
            }
            $result[] = $this->model->where('id','=',$id)->update(['status'=>1]);

            $user_model = new User();
            $result[] = $user_model->where('id','=',$apply_info['user_id'])->update(['ident'=>2]);
            if(checkRes($result)){
                Db::commit();
                $this->success('审核通过');
            }else{
                Db::rollback();
                $this->error('审核失败');
            }
        }catch (Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    public function reject($id){
        $apply_info = $this->model->where('id','=',$id)->find();
        if(!$apply_info){
            $this->error('数据不存在');
        }
        $result = $this->model->where('id','=',$id)->update(['status'=>2]);
        if($result){
            $this->success('驳回成功');
        }else{
            $this->error('驳回失败');
        }
    }
}

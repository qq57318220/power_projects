<?php

namespace app\admin\controller\member;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员等级
 *
 * @icon fa fa-circle-o
 */
class Level extends Backend
{
    
    /**
     * Level模型对象
     * @var \app\admin\model\member\Level
     */
    protected $model = null;
    protected $noNeedRight=['selectlist'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\member\Level;
        $this->view->assign("typeListList", $this->model->getTypeListList());
    }

    public function selectlist(){
        return json($this->model->order('level','asc')->select());
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $hasLevel = $this->model->where('level','=',$params['level'])->find();
                if($hasLevel){
                    return $this->error($params['level'].'等级的卡已经存在');
                }
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result) {
                    $this->success();
                } else {
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $assign['level'] = $this->model->max('level') + 1;
        $assign['min_score'] = $this->model->max('min_score') + 1;
        $assign['min_amount'] = $this->model->max('min_amount') + 0.01;
        $this->assign($assign);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $hasLevel = $this->model->where('level','=',$params['level'])->where('id','<>',$ids)->find();
            if($hasLevel){
                return $this->error($params['level'].'等级的卡已经存在');
            }
            if ($params) {
                $params = $this->preExcludeFields($params);

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $params['updatetime'] = time();
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result) {
                    $this->success();
                } else {
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $up_info = $this->model->where('level','=',$row->level - 1)->find();
        if($up_info){
            $assign['up_min_amount'] = $up_info->min_amount;
            $assign['up_min_score'] = $up_info->min_score;
        }else{
            $assign['up_min_amount'] = '';
            $assign['up_min_score'] = '';
        }
        $down_info = $this->model->where('level','=',$row->level + 1)->find();
        if($down_info){
            $assign['down_min_amount'] = $down_info->min_amount;
            $assign['down_min_score'] = $down_info->min_score;
        }else{
            $assign['down_min_amount'] = '';
            $assign['down_min_score'] = '';
        }
        $this->view->assign($assign);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if(in_array(1,explode(',',$ids))){
            $this->error('默认等级不可删除');
        }
        //这个等级下面有用户就不能删除
        $has_user = \app\admin\model\User::where('level_id','in',$ids)->select();
        if($has_user){
            $this->error('等级下面有用户，不可删除');
        }
        parent::del($ids);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}

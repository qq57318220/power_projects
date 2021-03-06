<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\UserGradeRatio;
use app\admin\model\User;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class UserGrade extends Backend
{
    
    /**
     * UserGrade模型对象
     * @var \app\admin\model\UserGrade
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\UserGrade;

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
                    $params['grade'] = $this->model->order('grade','desc')->value('grade')+1;
                    $result = $this->model->allowField(true)->save($params);
                    //佣金比例表封装
                    UserGradeRatio::addByGrade($params['grade']) or $result=false;
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
                    Db::commit();
                    $this->success();
                } else {
                    Db::rollback();
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $ids != 1 or $this->error('默认级别不能删');
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $info = $this->model->where($pk , $ids)->find() or $this->error('记录不存在');
            $last_grade = $this->model->order('grade','desc')->value('grade');
            $last_grade == $info['grade'] or $this->error('必须先删除末尾级别');
            User::where('grade',$info['grade'])->count() == 0 or $this->error('请先把该级别的会员设置到其它级别');
            $bool = 0;
            Db::startTrans();
            try {
                $bool = $info->delete();
                UserGradeRatio::delByGrade($info['grade']) or $bool=false;//把对应的佣金比例设置也删除
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($bool) {
                Db::commit();
                $this->success();
            } else {
                Db::rollback();
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}

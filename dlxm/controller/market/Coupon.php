<?php

namespace app\admin\controller\market;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 优惠券
 *
 * @icon fa fa-circle-o
 */
class Coupon extends Backend
{
    
    /**
     * Coupon模型对象
     * @var \app\admin\model\market\Coupon
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\market\Coupon;
        $this->view->assign("typeListList", $this->model->getTypeListList());
        $this->view->assign("timeTypeList", $this->model->getTimeTypeList());
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
                    $params['type_list'] == 2 || $params['threshold'] > $params['con'] or $this->error('使用门槛金额必须大于减免金额');
                    if($params['can_num']<=0){
                        $this->error('用户可领取的次数必须大于0');
                    }
                    if($params['time_type'] == 1){
                        $startTime = strtotime($params['start_time']);
                        $endTime = strtotime($params['end_time'])+86399;
                        $endTime > $startTime or $this->error('日期有误');
                        $params['start_time'] = date('Y-m-d',$startTime);
                        $params['end_time'] = date('Y-m-d H:i:s',$endTime);
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
        return $this->view->fetch();
    }


    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

}

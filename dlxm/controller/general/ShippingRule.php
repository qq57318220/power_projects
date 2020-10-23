<?php

namespace app\admin\controller\general;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use \app\admin\library\traits\BackendMch;

/**
 * 运费规则
 *
 * @icon fa fa-circle-o
 */
class ShippingRule extends Backend
{
    
    /**
     * ShippingRule模型对象
     * @var \app\admin\model\general\ShippingRule
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\general\ShippingRule;
        $this->view->assign("typeListList", $this->model->getTypeListList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                   $check =  Db::name('product')->whereNull('deletetime')->where('shipping_rule_id',$v['id'])->find();
                   if($check){
                       $this->error('该运费规则有绑定的产品，请删除产品后再删除运费规则');
                   }
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}

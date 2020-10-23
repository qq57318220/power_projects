<?php

namespace app\admin\controller\template;

use app\common\controller\Backend;
use fast\Tree;
use app\admin\model\shop\Product;
use think\Db;
use think\Exception;

/**
 * 产品属性名称
 *
 * @icon fa fa-circle-o
 */
class Page extends Backend
{
    
    /**
     * Prop模型对象
     * @var \app\admin\model\shop\Prop
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\template\Page;
        //$pid_list = $this->model->where('pid',0)->order('id')->select();

        list($where, $sort, $order, $offset, $limit) = $this->buildparams();

        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            //->limit($offset, $limit)
            ->select();
        $list = collection($list)->toArray();
        $tree = Tree::instance();
        $tree->init(collection($list, 'pid'));
        $this->list = $tree->getTreeList($tree->getTreeArray(0), 'name');

        $this->assign('pid_list',$this->list);
    }


    /**
     * 查看
     * @ApiSummary (胡正林)
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }


            $result = array("total" =>  $total = count($this->list), "rows" =>  $this->list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 删除
     * @ApiSummary (胡正林)
     */
    public function del($ids = "")
    {

        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $obj = $this->model->where($pk, 'in', $ids)->find() or $this->error('出错了~');
           $result = true;

            $this->model->startTrans();

            try {
                //判断是否是父级
                if($obj->pid == 0){
                    $this->model->where('pid',$obj->id)->delete() or $result=false;
                    $reg = $obj->id.':';
                }else{
                    $reg = $obj->pid.':'.$obj->id;
                }
                \app\admin\model\shop\product\Prop::upd_product_props($reg) or $result=false;//更新产品关联属性
                \app\admin\model\shop\GroupProductProp::upd_product_props($reg) or $result=false;//更新团购关联属性
                $obj->delete() or $result = false;
                $this->model->commit();
            } catch (PDOException $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result) {
                $this->success();
            } else {
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

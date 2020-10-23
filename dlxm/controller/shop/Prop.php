<?php

namespace app\admin\controller\shop;

use app\common\controller\Backend;
use fast\Tree;
use app\admin\model\shop\Product;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 产品属性名称
 *
 * @icon fa fa-circle-o
 */
class Prop extends Backend
{
    
    /**
     * Prop模型对象
     * @var \app\admin\model\shop\Prop
     */
    protected $model = null;
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shop\Prop;
        $pid_list = $this->model->where('pid',0)->order('id')->select();
        $this->assign('pid_list',$pid_list);
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
//            $total = $this->model
//                ->where($where)
//                ->order($sort, $order)
//                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                //->limit($offset, $limit)
                ->with('merchant')
                ->select();
//            dump($where);
            $list = collection($list)->toArray();
//	        halt($list);

	        $tree = Tree::instance();
            $tree->init(collection($list, 'pid'));
            $this->list = $tree->getTreeList($tree->getTreeArray(0), 'name');
            $result = array("total" =>  $total = count($this->list), "rows" =>  $this->list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->with('merchant')->where('id',$ids)->find();
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
                    $result = $row->allowField(true)->save($params) !== false;
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
        $this->view->assign("row", $row);
        $pid_list = $this->model->where('pid',0)->where('merchant_id',$row->merchant_id)->order('id')->select();
        $this->assign('pid_list',$pid_list);
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
            $result = [];

            $this->model->startTrans();

            try {
                //判断是否是父级
                if($obj->pid == 0){
                    $res = $this->model->where('pid',$obj->id)->delete();
                    $result[] = ($res !== false) ? true : false;
                    $reg = $obj->id.':';
                }else{
                    $reg = $obj->pid.':'.$obj->id;
                }
                $result[] = \app\admin\model\shop\product\Prop::upd_product_props($reg);//更新产品关联属性
                $result[] = \app\admin\model\shop\GroupProductProp::upd_product_props($reg);//更新团购关联属性
                $result[] = $obj->delete();
            } catch (PDOException $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if (checkRes($result)) {
                $this->model->commit();
                $this->success();
            } else {
                $this->model->rollback();
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}

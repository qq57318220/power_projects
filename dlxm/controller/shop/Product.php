<?php

namespace app\admin\controller\shop;

use think\Db;
use app\common\controller\Backend;
use app\admin\controller\Category;
use app\admin\model\shop\Prop;
use app\admin\model\shop\product\Prop as ProductProp;
use app\admin\controller\Common;
use think\Exception;
use think\exception\PDOException;

/**
 * 产品管理
 *
 * @icon fa fa-circle-o
 */
class Product extends Backend
{

    /**
     * Product模型对象
     * @var \app\admin\model\shop\Product
     */
    protected $model = null;
    protected $common = null;
    protected $noNeedLogin = ['get_info'];
    protected $noNeedRight = ['get_info'];
    protected $mch_id = 'merchant_id';
    use \app\admin\library\traits\BackendMch;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\shop\Product;
        $this->common = new \app\admin\controller\Common;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("postStatusList", $this->model->getPostStatusList());
        $this->view->assign("tagsList", $this->model->getTagsList());
        $this->view->assign("hasPropList", $this->model->getHasPropList());
    }


    /**
     * 查看
     */
    public function index()
    {

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->with('merchant')
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     * @ApiSummary (胡正林)
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

                $result = true;
                $this->model->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    if($params['has_prop']){
                        $only = $this->request->only(['price','score']);
                        $params['min_price'] = min($only['price']);
                        $params['min_price_score'] = $only['score'][array_search($params['min_price'],$only['price'])];

                    }else{
                        $params['min_price'] = $params['price'];
                        $params['min_price_score'] = $params['score'];
                    }
                    $params['online_sn'] = null;
                    in_array($params['post_status'],[0,1]) or $this->error('发布状态有误');
                    $params['post_status'] == 0 || $params['status'] == 'online' or $this->error('上架状态才能提交发布审核');
                    $id = $this->model->insertGetId($params) or $result = false;

                    if($params['has_prop']){
                        $propData = $this->common->packPropData(['product_id'=>$id]);
                        ProductProp::insertAll($propData) or $result = false;
                    }

                    $this->model->commit();
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
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
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('prop_list',Prop::getListLv());
        return $this->view->fetch();
    }

    /**
     * 编辑
     * @ApiSummary (胡正林)
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
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = true;
                $this->model->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $init_has_prop = $this->request->post('init_has_prop');
                    $where['product_id'] = $row['id'];

                    //原先设置有属性，当前保存没属性
                    if($init_has_prop==1 && $params['has_prop']==0){
                        ProductProp::where($where)->delete() or $result = false;
                    }
                    if($params['has_prop']){
                        $only = $this->request->only(['price','score']);
                        $params['min_price'] = min($only['price']);
                        $params['min_price_score'] = $only['score'][array_search($params['min_price'],$only['price'])];

                    }else{
                        $params['min_price'] = $params['price'];
                        $params['min_price_score'] = $params['score'];
                    }
                    array_key_exists('post_status',$params) or $this->error('请重新选择发布状态');
                    in_array($params['post_status'],[0,1]) or $this->error('发布状态有误');
                    $params['post_status'] == 0 || $params['status'] == 'online' or $this->error('上架状态才能提交发布审核');
                    $params['online_sn'] = null;
                    $row->allowField(true)->save($params) !== false or $result=false;
                    if($params['has_prop']){
                        $propData = $this->common->packPropData($where);
                        $ids = array_flip(ProductProp::where($where)->column('id'));
                        foreach ($propData as $v){
                            $where['props'] = $v['props'];
                            if($id = ProductProp::where($where)->value('id')){
                                ProductProp::where('id',$id)->update($v)!==false or $result = false;
                                unset($ids[$id]);
                            }else{
                                ProductProp::create($v) or $result = false;
                            }
                        }
                        if(!empty($ids)){
                            ProductProp::whereIn('id',array_flip($ids))->delete() or $result = false;
                        }

                    }

                    !$result or $this->model->commit();
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
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
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
//        halt($row);
        $this->view->assign("row", $row);
        $this->assign('prop_list',collection(Prop::getListLv(['*'],['merchant_id'=>$row['merchant_id']]))->toArray());
        list($product_prop_list,$prop_ids) = ProductProp::product_props($row['id']);
         $this->assign('prop_ids',$prop_ids);
        $this->assign('product_prop_list',$product_prop_list);
        return $this->view->fetch();
    }

    /**
     * 发布审核
     * @ApiSummary (胡正林)
     */
    public function ck($ids = null)
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
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = true;
                $this->model->startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $init_has_prop = $this->request->post('init_has_prop');
                    $where['product_id'] = $row['id'];

                    //原先设置有属性，当前保存没属性
                    if($init_has_prop==1 && $params['has_prop']==0){
                        ProductProp::where($where)->delete() or $result = false;
                    }
                    if($params['has_prop']){
                        $only = $this->request->only(['price','score']);
                        $params['min_price'] = min($only['price']);
                        $params['min_price_score'] = $only['score'][array_search($params['min_price'],$only['price'])];

                    }else{
                        $params['min_price'] = $params['price'];
                        $params['min_price_score'] = $params['score'];
                    }
                    in_array($params['post_status'],[2,3]) or $this->error('发布状态有误');
                    if($params['post_status'] == 2){
                        $params['return_reason'] = null;
                        $params['online_sn'] = randNumber();
                    }

                    $row->allowField(true)->save($params) !== false or $result=false;
                    if($params['has_prop']){
                        $propData = $this->common->packPropData($where);
                        $ids = array_flip(ProductProp::where($where)->column('id'));
                        foreach ($propData as $v){
                            $where['props'] = $v['props'];
                            if($id = ProductProp::where($where)->value('id')){
                                ProductProp::where('id',$id)->update($v)!==false or $result = false;
                                unset($ids[$id]);
                            }else{
                                ProductProp::create($v) or $result = false;
                            }
                        }
                        if(!empty($ids)){
                            ProductProp::whereIn('id',array_flip($ids))->delete() or $result = false;
                        }

                    }

                    !$result or $this->model->commit();
                } catch (ValidateException $e) {
                    $this->model->rollback();
                    $this->error($e->getMessage());
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
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->assign('prop_list',collection(Prop::getListLv(['*'],['merchant_id'=>$row['merchant_id']]))->toArray());
        list($product_prop_list,$prop_ids) = ProductProp::product_props($row['id']);
        $this->assign('prop_ids',$prop_ids);
        $this->assign('product_prop_list',$product_prop_list);
        return $this->view->fetch();
    }

    /**
     * 产品序列号校验
     * @ApiSummary (胡正林)
     */
    public function check_productsn_unique(){
        $params = $this->request->post("row/a");
        if(!empty($params['product_sn'])){
            $where = ['product_sn'=>$params['product_sn']];
            !$id = $this->request->get('id') or $where['id']=['<>',$id];
            $this->model->where($where)->count() == 0 or  $this->error(__('产品编号已存在', ''));
        }
        $this->success();
    }

    public function get_info(){
        $product_id = $this->request->param('id');
        $product_info = model('shop.product')->where('id','=',$product_id)->find();
        return json($product_info);
    }

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
                    $count += $v->delete();
                    \app\api\model\Collect::where('product_id',$v->id)->delete();
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

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}

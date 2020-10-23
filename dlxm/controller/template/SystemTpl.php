<?php

namespace app\admin\controller\template;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use app\admin\model\template\Page as PageModel;
use app\admin\model\template\ConModel;
use app\admin\model\shop\Product as ProductModel;
use app\admin\model\shop\Group as GroupModel;

/**
 * 模板
 *
 * @icon fa fa-circle-o
 */
class SystemTpl extends Backend
{
    
    /**
     * SystemTpl模型对象
     * @var \app\admin\model\template\SystemTpl
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\template\SystemTpl;
        $this->view->assign("typeList", $this->model->getTypeList());

        $list = collection(PageModel::order('weigh', 'desc')->select())->toArray();
        $tree = Tree::instance();
        $tree->init(collection($list, 'pid'));
        $page_id_list = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $this->assign('page_id_list',$page_id_list);
    }
    

    protected function fetch_product(){
        $products = ProductModel::field('id,name')->order('weigh','desc')->select();
        $groups = GroupModel::field('id,name')->order('weigh','desc')->select();
        $this->assign('products',$products);
        $this->assign('groups',$groups);
    }
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    
                    ->where($where)
                    ->where('type',1)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    
                    ->where($where)
                ->where('type',1)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','name','weigh','color_image','default_switch','updatetime','createtime']);
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 设为首页模板
     */
    public function set_default($id){
        $old = $this->model->where('default_switch',1)->find();
        $row = $this->model->where('id',$id)->find();
        $row->default_switch == 0 or $this->error('当前模板已是首页模板');
        Db::startTrans();
        $bool = true;
        $row->default_switch = 1;
        $row->save() or $bool=false;
        if($old){
            $old->default_switch = 0;
            $old->save()!==false or $bool=false;
        }
        if($bool){
            Db::commit();
            $this->success('操作成功');
        }else{
            Db::rollback();
            $this->error('操作失败');
        }
    }

    /**
     * 模板装修首页
     */
    public function setting($id){
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

        }
        $products = collection(ProductModel::field('id,name,image,min_price,min_price_score,line_price')->order('weigh','desc')->select())->toArray();
        $info = $this->model->where('id',$id)->find();
        $this->assign('info',$info);
        $con_list = collection(ConModel::where('template_id',$id)->order(['position'=>'asc','weigh'=>'desc'])->select())->toArray();
        $cons = [];
        if(!empty($con_list)){
            foreach ($con_list as $v){
                $v['is_product'] != 1 or $v['product_list'] = [];
                if($v['is_product'] == 1 && $v['product_ids'] && !empty($products)){
                    $ids = explode(',',$v['product_ids']);
                    foreach ($products as $p){
                        if(in_array($p['id'],$ids)){
                            $v['product_list'][] = $p;
                        }
                    }
                }
                $cons[$v['position']][] = $v;
            }
        }

        $this->assign('cons',$cons);
        $field = ['g.*','p.id product_id','p.name product_name','p.image','p.line_price',];
        $groups = collection(GroupModel::product_list($field,0,null,'',[],['g.weigh'=>'desc'],4))->toArray();
        $this->assign('groups',$groups);
        return $this->view->fetch($id);
    }

    /**
     * 新增装修内容
     */
    public function add_con(){
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $gets = $this->request->only(['template_id','position','type','image_size']);
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params = array_merge($gets,$params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if($params['template_page_id'] == 2){
                    $params['link_id'] = $this->request->request('product_id') or $this->error('请选择产品');
                }elseif($params['template_page_id'] == 20){
                    $params['link_id'] = $this->request->request('group_id') or $this->error('请选择团购活动');;
                }
                $params['has_delete'] = 1;
                $result = true;
                Db::startTrans();
                try {
                    $result = ConModel::create($params);
                    if($result){
                        Db::commit();
                        $this->success('操作成功');
                    }else{
                        Db::rollback();
                        $this->error('操作失败');
                    }
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
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->fetch_product();
        return $this->view->fetch();
    }

    /**
     * 编辑装修内容
     */
    public function edit_con($id){
        $row = ConModel::where('id',$id)->find();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $gets = $this->request->only(['template_id','position','type']);
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params = array_merge($gets,$params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = true;
                if($params['template_page_id'] == 2){
                    $params['link_id'] = $this->request->request('product_id') or $this->error('请选择产品');
                }elseif($params['template_page_id'] == 20){
                    $params['link_id'] = $this->request->request('group_id') or $this->error('请选择团购活动');;
                }
                $params['product_ids'] = implode(',',$params['product_ids']);
                $row['type'] == 1 || $params['image'] or $this->error('请上传图片');
                Db::startTrans();
                try {
                    $params['updatetime'] = time();
                    $result = ConModel::where('id',$id)->update($params);
                    if($result){
                        Db::commit();
                        $this->success('操作成功');
                    }else{
                        Db::rollback();
                        $this->error('操作失败');
                    }
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
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign('row',$row);
        $this->fetch_product();
        return $this->view->fetch();
    }

    /**
     * 删除装修内容
     */
    public function del_con($id){
        $result = ConModel::where('id',$id)->where('has_delete',1)->delete();
        return $result?$this->success():$this->error();
    }

    /**
     * 设置模板显示产品的数量
     */
    public function show_product_number($id){
        $row = $this->model->where('id',$id)->find();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            intval($params['show_product_number']) > 0 or $this->error('数量有误');
            $this->model->where('id',$id)->update($params)?$this->success('操作成功'):$this->error('操作失败');
        }
        $this->assign('row',$row);
        return $this->view->fetch();
    }


}

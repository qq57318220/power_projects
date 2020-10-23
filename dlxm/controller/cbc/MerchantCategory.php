<?php

namespace app\admin\controller\cbc;

use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 商户分类管理
 *
 * @icon fa fa-circle-o
 */
class MerchantCategory extends Backend
{
    
    /**
     * MerchantCategory模型对象
     * @var \app\admin\model\cbc\MerchantCategory
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\cbc\MerchantCategory;
        $this->parentList();
    }

    protected function parentList($merchant_id=null){
        $tree = Tree::instance();
        $filter = $this->request->get("filter", '');
        $where = (array)json_decode($filter, true)?:[];

        $tree->init(collection($this->model->where($where)->order('weigh desc,id desc')->select())->toArray(), 'pid');
        $this->categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = [0 => ['id'=>'','name' => __('None')]];
        foreach ($this->categorylist as $k => $v) {
            $categorydata[$v['id']] = $v;
        }
        $this->view->assign("parentList", $categorydata);
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

            $total = count($this->categorylist);
            $result = array("total" => $total, "rows" => $this->categorylist);

            return json($result);
        }
        return $this->view->fetch();
    }
}

<?php

namespace app\admin\controller\examine;

use app\common\controller\Backend;
use fast\Tree;

/**
 * 检验标准分类
 *
 * @icon fa fa-circle-o
 */
class Plate extends Backend
{
    
    /**
     * Plate模型对象
     * @var \app\admin\model\examine\Plate
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\examine\Plate;

        $ruleList = collection($this->model->order('id', 'asc')->select())->toArray();
        unset($v);
        Tree::instance()->init($ruleList);
        $this->rulelist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
        $ruledata = [0 => __('None')];
        foreach ($this->rulelist as $k => &$v) {
            $ruledata[$v['id']] = $v['name'];
        }
        unset($v);
        $this->view->assign('ruledata', $ruledata);
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $list = $this->rulelist;
            $total = count($this->rulelist);

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
}

<?php

namespace app\admin\controller\service;

use app\common\controller\Backend;

/**
 * 聊天记录管理
 *
 * @icon fa fa-circle-o
 */
class ServiceRecord extends Backend
{
    
    /**
     * ServiceRecord模型对象
     * @var \app\admin\model\ServiceRecord
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\service\ServiceRecord;
        $this->view->assign("senderIdentityList", $this->model->getSenderIdentityList());
        $this->view->assign("messageTypeList", $this->model->getMessageTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
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
        //当前是否为关联查询
        $this->relationSearch = true;
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
                    ->with(['user','merchant'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','merchant'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
//            dump($list);exit;
//            foreach ($list as $row) {
//                $row->visible(['id','session_id','sender_identity','sender_id','message_type','message','status','createtime']);
//                $row->visible(['user']);
//				$row->getRelation('user')->visible(['nickname','avatar']);
//                $row->visible(['merchant']);
//                $row->getRelation('merchant')->visible(['name']);
//            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}

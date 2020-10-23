<?php

namespace app\admin\controller\service;

use app\admin\model\service\ServiceRecord;
use app\admin\model\User;
use app\common\controller\Backend;
use think\Db;

/**
 * 客服会话管理
 *
 * @icon fa fa-circle-o
 */
class ServiceSession extends Backend
{
    
    /**
     * ServiceSession模型对象
     * @var \app\admin\model\ServiceSession
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\service\ServiceSession;

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
                    ->with(['user','admin','merchant'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['user','admin','merchant'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $row) {
                $row->visible(['id','user_id','csr_id','createtime','merchant_id','deletetime']);
                $row->visible(['user']);
				$row->getRelation('user')->visible(['nickname','avatar']);
				$row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
                $row->visible(['merchant']);
                $row->getRelation('merchant')->visible(['name']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function detail($ids)
    {

        $session = \app\admin\model\service\ServiceSession::where('id',$ids)->find();
        $user = User::get($session['user_id']);
        $messege_list  =  ServiceRecord::getRecordList($session);
        if(empty($messege_list)){
            $maxid = 0;
        }else{
            $maxid = $messege_list[0]['id'];
        }

        array_multisort($messege_list,SORT_ASC);
        $this->assign('messege_list',$messege_list);
        $this->assign('user',$user);
        $this->assign('ids',$ids);
        $this->assign('maxid',$maxid);
        return $this->view->fetch();
    }

    public function send()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data) {
                $session = \app\admin\model\service\ServiceSession::where('id',$data['session_id'])->find();
                $data = $this->preExcludeFields($data);
                $data['merchant_id'] = $session['merchant_id'];
                $data['sender_identity'] = 0;
                $data['sender_id'] = $this->auth->id;
                $data['status'] = 0;
                $data['createtime'] = time();
                if(empty($data['message'])){
                    $this->error('消息不能为空');
                }
                $result = ServiceRecord::create($data);
                $this->success('发送成功');
            }

        }else{
            $this->error('发送失败');
        }
    }

    public function getMessageList($id,$page)
    {
        $maxid = $this->request->request('maxid');
        $session = \app\admin\model\service\ServiceSession::where('id',$id)->find();
        $messege_list  =  ServiceRecord::getRecordList($session,$page,0,$maxid);
        array_multisort($messege_list,SORT_ASC);
        $count =  count($messege_list);
        $data = [
            'page'=>$page,
            'total'=>$count,
            'list'=>$messege_list
        ];
        $this->result($data,1,'获取成功');
    }

}

<?php

namespace app\admin\controller\question;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\member\UserScoreLog;
use app\admin\model\member\User;

/**
 * 回复内容(专家会诊)
 *
 * @icon fa fa-circle-o
 */
class ExpertReply extends Backend
{
    
    /**
     * ExpertReply模型对象
     * @var \app\admin\model\question\ExpertReply
     */
    protected $model = null;
    protected $dataLimit = 'auth';
    protected $dataLimitField = 'admin_id';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\question\ExpertReply;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("userList", $this->model->getUserList());
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
            $new_where = [];
            if(!empty($_GET['qid'])){
                $new_where['expert_reply.qid'] = $_GET['qid'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['admin'])
                    ->order('expert_reply.id desc')
                    ->where($where)
                    ->where($new_where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['admin'])
                    ->order('expert_reply.id desc')
                    ->where($where)
                    ->where($new_where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('admin')->visible(['username']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function examine($ids){
        $row = $this->model->get($ids);
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
                        $row->validateFailException(true)->validate($validate);
                    }

                    $where['excerpt'] = array('like','%发表评论%');
                    $where['user_id'] = $row['type_id'];
                    $where['createtime'] = array('gt',strtotime(date('Y-m-d',time())));
                    $count = UserScoreLog::where($where)->count();
                    if($params['status'] == '1' && $count < 10 && $row['type'] == '1'){
                        $before = User::where('id',$row['type_id'])->value('score');
                        $after = $before + 5;
                        User::plusScore($row['type_id'],5);
                        User::plusTotalScore($row['type_id'],5);
                        UserScoreLog::insertData($row['type_id'],1,5,$before,$after,'','发表评论');
                    }

                    $result = $row->allowField(true)->save($params);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 审核
     */
    public function audit($ids = null)
    {
        $post = input('');
        foreach($post['val'] as $k=>$v){
            Db::name('question_expert_reply')->where(['id'=>$v['id']])->update(['status'=>'1']);
        }
        $this->success('成功');
    }
    
    /**
     * 回复
     */
    public function reply($ids){
        $row = $this->model->get($ids);
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
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['qid'] = $row['qid'];
                    $params['replied_id'] = $row['id'];
                    $params['type'] = 2;
                    $params['type_id'] = $this->auth->id;
                    $params['admin_id'] = $this->auth->id;
                    $params['create_time'] = time();
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }
}

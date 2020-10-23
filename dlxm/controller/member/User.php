<?php

namespace app\admin\controller\member;

use app\admin\model\member\User as UserModel;
use app\admin\model\member\UserScoreLog;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend
{
    
    /**
     * User模型对象
     * @var \app\admin\model\member\User
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\member\User;
        $this->view->assign("identList", $this->model->getGradeList());
        $this->view->assign("genderList", $this->model->getGenderList());
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
                ->with('level')
                ->with('grade')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 消费积分
     */
    public function use_score($ids = null)
    {
        $row = $this->model->where('id',$ids)->find();
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
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    UserModel::cutScore($row->id,$params['use_score']) or $result = false;
                    UserScoreLog::insertData($row->id,2,$params['use_score'],$row->score,$row->score-$params['use_score'],'','线下消费') or $result = false;
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
                    Db::commit();
                    $this->success();
                } else {
                    Db::rollback();
                    $this->error();
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 推广员收益明细
     */
    public function earnings($ids){
        $merchant_id = $this->auth->merchant_id;
        if(empty($merchant_id)){
            $where = ' 1 = 1 ';
        }else{
            $where = ' merchant_id = '.$merchant_id;
        }
        $time = time();
        //7天赚了多少
        $startTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endTime = mktime(0,0,0,date('m'),date('d')-7,date('Y'));
        $sevenDaysAmount = Db::name('shop_reward')->field('sum(reward_total)  as sum')->where($where)->whereBetween('createtime',$startTime.','.$endTime)->where(['status'=>'2','promoter_uid'=>$ids])->find();
        //30天赚了多少
        $endTime = mktime(0,0,0,date('m'),date('d')-7,date('Y'));
        $thirtyDaysAmount = Db::name('shop_reward')->field('sum(reward_total) as sum')->where($where)->whereBetween('createtime',$startTime.','.$endTime)->where(['status'=>'2','promoter_uid'=>$ids])->find();
        //当天赚了多少
        $toDayAmount = Db::name('shop_reward')->field('sum(reward_total) as sum')->where($where)->whereBetween('createtime',$startTime.','.$time)->where(['status'=>'2','promoter_uid'=>$ids])->find();
        //总赚了多少
        $sum = Db::name('shop_reward')->field('sum(reward_total) as sum')->where($where)->where(['status'=>'2','promoter_uid'=>$ids])->find();

        $this->assign([
            'sevenDaysAmount'=>$sevenDaysAmount['sum'],
            'thirtyDaysAmount'=>$thirtyDaysAmount['sum'],
            'toDayAmount'=>$toDayAmount['sum'],
            'sum'=>$sum['sum'],
        ]);
        return $this->fetch();
    }

}

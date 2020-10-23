<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Random;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\Admin as AdminModel;
use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;

/**
 * 合作商
 *
 * @icon fa fa-circle-o
 */
class Merchant extends Backend
{

    /**
     * Merchant模型对象
     * @var \app\admin\model\Merchant
     */
    protected $model = null;

    protected $noNeedRight = ['getMchList'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Merchant;
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 获取商户列表，公共调用方法
     */
    public function getMchList($type=1){
        $where = $this->auth->merchant_id?['id'=>$this->auth->merchant_id]:[];
        $list = $this->model->where($where)->select();
        return $type?$list:['list'=>$list,'count'=>count($list)];
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
            $id = $this->getMchId();
            $mchWhere = '1=1';
            if($id != 1){
                $mchWhere = $id?['id'=>$id]:[];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($mchWhere)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($mchWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach($list as $k=>$v){
                $userId = Db::name('user_grade_merchant')->field('user_id')->where(['merchant_id'=>$v['id'],'status'=>'2'])->select();
                $userId = array_column($userId,'user_id');
                $userId = implode(',',$userId);
                $list[$k]['merchant'] = !empty($userId) ? $userId : 0;
            }


            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign('isSuperAdmin',$this->auth->isSuperAdmin());
        $this->assignconfig('admin_merchant_id',$this->auth->merchant_id);
        return $this->view->fetch();
    }

    /**
     * 添加
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
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validate($validate);
                    }
                    $area = explode('/',$params['city']);
                    count($area) == 3 or $this->error('请选择完整的地址');
                    list($params['province'],$params['city'],$params['district']) = $area;
                    $result = $this->model->allowField(true)->save($params);

                    if($result ){
                        $params ['merchant_id'] = Db::getLastInsID();
                        $result = $this->addRoleUser($params);
                    };
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
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        //数据权限校验
//        if($this->auth->merchant_id!=0 && $row->id !=$this->auth->merchant_id){
//            $this->error('没有访问权限~');
//        }
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
                    list($params['province'],$params['city'],$params['district']) = explode('/',$params['city']);
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
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            !in_array(1,explode(',',$ids)) or $this->error('平台商户不可删除');
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
                    \app\api\model\Subscribe::where('merchant_id',$v->id)->delete();
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
     * 设置或取消设置热卖商家
     * @param  [type] $ids [description]
     * @return [type]      [description]
     */
    public function hot($ids){
        $row = $this->model->where('id',$ids)->find();
        if($row){
            if($row['is_hot'] == '0'){
                $is_hot = 1;
            }else{
                $is_hot = 0;
            }
            if($this->model->where('id',$ids)->update(['is_hot' => $is_hot])){
                $this->success('执行成功！');
            }else{
                $this->error('执行失败！');
            }
        }else{
            $this->error('不存在该商家！');
        }
        $this->success('执行成功','',$row);
    }

    /**
     * 商户审核
     */
    public function check($ids){
        $row = $this->model->where('id',$ids)->find();
        $row->status == 0 or $this->error('已审核');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            in_array($params['status'],[1,3]) or die();
            Db::startTrans();
            $bool = $row->save($params);
            if($bool && $params['status'] == 1){
                //审核通过，建立账号
                $row->merchant_id = $row->id;
                $this->addRoleUser($row) or $bool = false;
            }
            if($bool){
                Db::commit();
                $this->success('审核成功');
            }else{
                Db::rollback();
                $this->error('审核失败');
            }

        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 发起提现
     */
    public function cash()
    {
        $left_money =  $this->model->where('id',$this->auth->merchant_id)->value('money');
        $this->view->assign('left_money',$left_money );
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if($params['money'] > $left_money){
                $this->error('余额不足');
            }
            $bank_info =  \app\admin\model\MerchantBank::get($params['bank_id']);
            $data  = [
                'status'           =>    1,
                'merchant_id'      => $this->auth->merchant_id,
                'money'            => $params['money'],
                'bank'             => $bank_info['bank'],
                'bank_name'        => $bank_info['bank_name'],
                'bank_account'     => $bank_info['bank_account'],
                'createtime'       => time()
            ];
            $bool = false;
            Db::startTrans();
              $bool =  Db::name('merchant_cash')->insert($data);
              $bool = $this->model->where('id',$this->auth->merchant_id)->setDec('money',$params['money']);
            if($bool){
                Db::commit();
                $this->success('申请成功');
            }else{
                Db::rollback();
                $this->error('申请失败');
            }

        }
        $default_bank = \app\admin\model\MerchantBank::where('default_switch',1)->order('createtime desc')->value('id');
        $this->view->assign('default_bank',$default_bank);
        return $this->view->fetch();

    }

    //角色、用户、角色用户入库
    protected function addRoleUser($param){
        $now = time();
        //创建用户
        $uData = [
            'username'  => $param['username'],
            'nickname'  => $param['name'].'管理员',
            'password'  => $param['password'],
            'salt'      => $param['salt'],
            'avatar'    => '/assets/img/avatar.png',
            'createtime'    =>  $now,
            'updatetime'    =>  $now,
            'merchant_id'    => $param['merchant_id'],
        ];
        if(!$urData['uid'] = AdminModel::insertGetId($uData)) return false;

        //创建角色
        $rData = [
            'pid'           =>  1,
            'name'          =>  $param['name'],
            'status'        =>  'normal',
            'createtime'    =>  $now,
            'updatetime'    =>  $now,
            'rules'         =>  AuthGroup::where('id',2)->find()->rules,
        ];
        if(!$urData['group_id'] = AuthGroup::insertGetId($rData)) return false;

        //关联用户、角色
        return AuthGroupAccess::insert($urData)?true:false;

    }


    public function import()
    {
        return parent::import();
    }



}

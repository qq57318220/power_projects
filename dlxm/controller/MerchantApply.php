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
use app\admin\model\Admin;
/**
 * 合作商
 *
 * @icon fa fa-circle-o
 */
class MerchantApply extends Backend
{

    /**
     * Merchant模型对象
     * @var \app\admin\model\MerchantApply
     */
    protected $model = null;

    protected $noNeedRight = ['getMchList'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\MerchantApply();
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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
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
                    $this->model->where('username',$params['username'])->count() == 0 && AdminModel::where('username',$params['username'])->count() == 0 or $this->error('用户名已存在');
                    $area = explode('/',$params['city']);
                    count($area) == 3 or $this->error('请选择完整的地址');
                    list($params['province'],$params['city'],$params['district']) = $area;
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                    $params['status'] = 0;

                    $result = $this->model->allowField(true)->save($params);
                    if($result ){
                        //创建合作商
                        \app\admin\model\Merchant::insert([
                            'name'=>$params['name'],
                            'tel' =>$params['tel'],
                            'weixin'    => $params['weixin'],
                            'logo'=>'',
                            'province'=>$params['province'],
                            'city' => $params['city'],
                            'district'=>$params['district'],
                            'address'=>$params['address'],
                            'status'=>1,
                            'updatetime'=>time(),
                            'createtime'=>time()
                        ]);
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
            //$this->error(json_encode($adminIds));
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();

            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                    $where['username='] = $v['username'];
                    Admin::where($where)->delete();
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
     * 审核合作商
     */
    public function check($ids){
        $row = $this->model->where('id',$ids)->find();
        $row['status']!= 1 or $this->error('已审核');
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            in_array($params['status'],[1,3]) or die();
            Db::startTrans();
            $bool = $row->save($params);
            if($bool && $params['status'] == 1){
                //创建合作商
                \app\admin\model\Merchant::insert([
                    'name'=>$row['name'],
                    'tel' =>$row['tel'],
                    'weixin' =>$row['weixin'],
                    'logo'=>$row['logo'],
                    'province'=>$row['province'],
                    'city' => $row['city'],
                    'district'=>$row['district'],
                    'address'=>$row['address'],
                    'status'=>1,
                    'updatetime'=>time(),
                    'createtime'=>time(),
                    'logo'=>'/assets/img/WechatIMG267.png',
                ]) or $bool = false;
                $row['merchant_id'] = Db::getLastInsID();
//                AdminModel::where('username',$row['username'])->find()?$this->error('已审核通过'):'';
//                $this->addRoleUser($row) or $bool = false;
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



}

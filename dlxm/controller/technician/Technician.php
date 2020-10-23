<?php

namespace app\admin\controller\technician;

use app\common\controller\Backend;
use app\admin\model\User;
use think\Db;
use app\admin\model\Information;
use think\Exception;
use think\exception\PDOException;

//use app\admin\model\technician\Technician;

/**
 * 技术员管理
 *
 * @icon fa fa-circle-o
 */
class Technician extends Backend
{

    /**
     * Technician模型对象
     * @var \app\admin\model\technician\Technician
     */
    protected $model = null;
    protected $dataLimit = 'auth';
    protected $dataLimitField = 'admin_id';
    protected $noNeedLogin = ['getListTechnicians'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\technician\Technician;
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

            $merchant = $this->auth->merchant_id;
            if(!empty($merchant)){
                $where = 'technician.merchant_id = '.$this->auth->merchant_id;
            }


            $total = $this->model
                ->with(['admin','user'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['admin','user'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $row) {

                $row->getRelation('admin')->visible(['username']);
                $row->getRelation('user')->visible(['nickname']);
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

                    if($params['status'] == 2){
                        User::where('id', $row['user_id'])->update(['type' => 1]);
                        $arr = array(
                            'user_id'   => $row['user_id'],
                            'type'  => 0,
                            'content'   => '恭喜你，平台通过您提交的技术员申请，现在您已经是平台管辖下的技术员，快开始接受工单吧',
                            'cate'  => 0,
                            'create_time'   => time()
                        );
                    }elseif($params['status'] == '0'){
                        $arr = array(
                            'user_id'   => $row['user_id'],
                            'type'  => 0,
                            'content'   => '很抱歉，平台驳回您提交的技术员申请',
                            'cate'  => 0,
                            'create_time'   => time()
                        );
                    }
                    Information::insert($arr);
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
    //原来的不敢动
    public function getTechnicians(){
        $where['status'] = 2;

        if($this->auth->merchant_id > 1 ){
            $where['merchant_id'] = $this->auth->merchant_id;
        }
        $total = $this->model->where($where)->count();
        $list = $this->model->where($where)->select();

        $list = collection($list)->toArray();
        $result = array("total" => $total, "rows" => $list);

        return json($result);
    }
    public function getListTechnicians(){
        $where = input('custom/a');
        $where['status'] = 2;

        $total = $this->model->where($where)->count();
        $list = $this->model->where($where)->select();

        $list = collection($list)->toArray();
        $result = array("total" => $total, "rows" => $list);

        return json($result);
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
                    User::where('id',$v['user_id'])->update(['type' => 0]);
                    $count += $v->delete();
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
	 * 批量更新
	 */
	public function multi($ids = "")
	{
		$ids = $ids ? $ids : $this->request->param("ids");
		if ($ids) {
			if ($this->request->has('params')) {
				parse_str($this->request->post("params"), $values);
				$this->multiFields = 'disable';

				$values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));

				if ($values || $this->auth->isSuperAdmin()) {
					$adminIds = $this->getDataLimitAdminIds();
					if (is_array($adminIds)) {
						$this->model->where($this->dataLimitField, 'in', $adminIds);
					}
					$count = 0;

					Db::startTrans();
					try {
						$list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
						foreach ($list as $index => $item) {
							$count += $item->allowField(true)->isUpdate(true)->save($values);
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
						$this->error(__('No rows were updated'));
					}
				} else {
					$this->error(__('You have no permission'));
				}
			}
		}
		$this->error(__('Parameter %s can not be empty', 'ids'));
	}


}

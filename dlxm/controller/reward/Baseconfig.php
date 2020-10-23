<?php

namespace app\admin\controller\reward;

use app\admin\controller\general\Config;
use app\admin\model\reward\team\UserTeam;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 奖励基础配置
 *
 * @icon fa fa-circle-o
 */
class Baseconfig extends Backend
{
    
    /**
     * UserTeam模型对象
     * @var \app\admin\model\reward\team\UserTeam
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\reward\team\UserTeam;

    }

    /**
     * 配置
     */
    public function index()
    {
        if($this->request->isPost()){
            $row = $this->request->post("row/a");
            $row['promotion_ratio'] + $row['team_ratio'] <= 1 or $this->error('分配比例之和不能大于1');
            $data = $this->request->only(['ratio_id','ratio']);
            $configList = [];
            $model = model('Config');
            foreach ($model->all() as $v) {
                if (isset($row[$v['name']])) {
                    $value = $row[$v['name']];
                    if (is_array($value) && isset($value['field'])) {
                        $value = json_encode(ConfigModel::getArrayData($value), JSON_UNESCAPED_UNICODE);
                    } else {
                        $value = is_array($value) ? implode(',', $value) : $value;
                    }
                    $v['value'] = $value;
                    $configList[] = $v->toArray();
                }
            }
            Db::startTrans();
            $bool = $model->allowField(true)->saveAll($configList);

            if($bool){
                (new Config)->refreshFile();
                Db::commit();
                $this->success();
            }else{
                Db::rollback();
                $this->error();
            }


        }

        $this->view->assign("row", config('site'));

        return $this->view->fetch();
    }
    

}

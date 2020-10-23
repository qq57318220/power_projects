<?php

namespace app\admin\controller\market;

use app\admin\model\UserDeep;
use app\admin\model\UserGrade;
use app\admin\model\UserGradeRatio;
use app\common\controller\Backend;
use app\admin\controller\general\Config;
use Think\Db;

/**
 * 分销
 *
 * @icon fa fa-circle-o
 */
class Promotion extends Backend
{


    /**
     * 推广配置
     */
    public function index()
    {
        if($this->request->isPost()){
            $row = $this->request->post("row/a");
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
            foreach ($data['ratio_id'] as $k=>$v){
                $data['ratio'][$k] >= 0 or $this->error('');
                UserGradeRatio::where('id',$v)->update(['ratio'=>$data['ratio'][$k]])!==false or $bool = false;
            }

            //校验数值的合法性
            $list = collection(UserGradeRatio::with('userGrade')->with('userDeep')->order('grade','asc')->order('deep','asc')->select())->toArray();
            $last = $list[count($list)-1];//最后一条数据
            list($max_grade,$max_deep) = [$last['grade'], $last['deep']];
            $max_grade_list = array_slice($list,-$max_deep);
            $max_grade_sum = array_sum(array_map(function($val){return $val['ratio'];}, $max_grade_list));//最高级别设置值之和不能超过1
            if($max_grade_sum > 1){
                Db::rollback();
                $this->error($last['user_grade']['name'].'身份深度值之和不能超过1');
            }
            $deep = [];
           foreach ($list as $k=>$v){
               $deep[$v['deep']][$v['grade']] = $v;
           }

            foreach ($deep as $item){
                krsort($item);
                $item = array_values($item);
                foreach ($item as $k=>$v){
                    if(empty($item[$k+1])){
                        continue;
                    }
                    if($v['ratio'] < $item[$k+1]['ratio']){
                        Db::rollback();
                        $this->error($v['user_deep']['name'].'中 ['.$v['user_grade']['name'].'] 的值不能小于 ['.$item[$k+1]['user_grade']['name'].']');
                    }
                }
            }
            if($bool){
                (new Config)->refreshFile();
                Db::commit();
                $this->success();
            }else{
                Db::rollback();
                $this->error();
            }


        }
        $GradeRatio = UserGrade::getGradeRatio();
        unset($GradeRatio[0]);
        $this->view->assign("row", config('site'));
        $this->assign('deeps',UserDeep::order('deep','asc')->select());
        $this->assign('gradeRatios',$GradeRatio);
        return $this->view->fetch();
    }
    

}

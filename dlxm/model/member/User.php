<?php

namespace app\admin\model\member;

use app\admin\model\UserGrade;
use think\Model;


class User extends Model
{
    // 表名
    protected $name = 'user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'gender_text',
        'prevtime_text',
        'logintime_text',
        'jointime_text',
        'grade_text'
    ];
    

    
    public function getGenderList()
    {
        return ['0' => __('Gender 0'), '1' => __('Gender 1')];
    }

    public function getGradeList()
    {
        $list = UserGrade::field('grade as id,name')->select();
        $lists = [];
        foreach ($list as $key =>$value){
            $lists[] = $value['name'];
        }
//        halt($lists);
        return $lists;
    }


    public function getGenderTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['gender']) ? $data['gender'] : '');
        $list = $this->getGenderList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['prevtime']) ? $data['prevtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['logintime']) ? $data['logintime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['jointime']) ? $data['jointime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getGradeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['grade']) ? $data['grade'] : '');
        $list = $this->getGradeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPrevtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLogintimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setJointimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    //提升会员等级
    public static function up_user_level($user_id){
        if(!$user_id){
            return false;
        }
        //当前用户等级
        $user_info = \app\admin\model\User::where('id','=',$user_id)->find()->toArray();
        $next_level = \app\admin\model\member\Level::where('min_amount','<=',$user_info['total_amount'])->whereOr('min_score','<=',$user_info['total_score'])->order('level','desc')->find();
        if($next_level){
            if($next_level['id'] != $user_info['level_id']){
                $next_level = $next_level->toArray();
            }else{
                return true;
            }
        }else{
            return true;
        }
        $res = \app\admin\model\User::where('id','=',$user_id)->update(['level_id'=>$next_level['id']]);
        return $res ? true : false;
    }

    public function level(){
        return $this->hasOne('app\admin\model\member\Level','id','level_id');
    }

    public function grade(){
        return $this->hasOne('app\admin\model\UserGrade','grade','grade');
    }

    public function team(){
        return $this->hasOne('app\admin\model\reward\team\UserTeam','team_grade','team_grade');
    }

    //减少用户零钱
    public static function cutMoney($user_id,$money){
        return self::where('id',$user_id)->where('money','>=',$money)->setDec('money',$money);
    }

    //增加用户零钱
    public static function plusMoney($user_id,$money){
        return self::where('id',$user_id)->setInc('money',$money);
    }

    //减少用户积分
    public static function cutScore($user_id,$score){
        return self::where('id',$user_id)->where('score','>=',$score)->setDec('score',$score);
    }

    //增加用户积分
    public static function plusScore($user_id,$score){
        return self::where('id',$user_id)->setInc('score',$score);
    }

    //增加用户历史积分
    public static function plusTotalScore($user_id,$score){
        return self::where('id',$user_id)->setInc('total_score',$score);
    }

    //减少用户购物币
    public static function cutGold($user_id,$gold){
        return self::where('id',$user_id)->where('gold','>=',$gold)->setDec('gold',$gold);
    }

    //增加用户购物币
    public static function plusGold($user_id,$gold){
        return self::where('id',$user_id)->setInc('gold',$gold);
    }

    //增加用户余额
    public static function plusAmount($user_id,$amount){
        return self::where('id',$user_id)->setInc('amount',$amount);
    }

    //减少用户余额
    public static function cutAmount($user_id,$amount){
        return self::where('id',$user_id)->where('amount','>=',$amount)->setDec('amount',$amount);
    }

}

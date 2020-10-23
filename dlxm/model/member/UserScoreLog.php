<?php

namespace app\admin\model\member;

use think\Model;


class UserScoreLog extends Model
{
    // 表名
    protected $name = 'user_score_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    /*****
     * @param $mark 符号:1=加+,2=减-
     * @param $money 变更积分
     * @param $before 变更前积分
     * @param $after 变更后积分
     * @param $memo 备注
     *
     */
    public static function insertData($user_id,$mark,$score,$before,$after,$memo='',$excerpt=''){
        $data = [
            'user_id'      =>  $user_id,
            'mark'      =>  $mark,
            'score'     =>  $score,
            'before'    =>  $before,
            'after'     =>  $after,
            'memo'      =>  $memo,
            'excerpt'   =>  $excerpt,
            'createtime'=>  time(),
        ];
        return self::insertGetId($data);
    }


}

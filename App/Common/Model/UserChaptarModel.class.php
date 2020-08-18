<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 18:34
 */
namespace Common\Model;
use Think\Model;
class UserChaptarModel extends Model{
    //根据用户id获取用户的消费记录
    public function getCustomRecods($userid,$index=0){
        $data=$this->join('ys_novel on ys_user_chaptar.type_id=ys_novel.type_id')
            ->field('ys_user_chaptar.*,ys_novel.name')
            ->where('user_id='.$userid)
            ->order('ys_user_chaptar.add_time desc')
            ->limit($index,4)
            ->select();
        return $data;
    }
}
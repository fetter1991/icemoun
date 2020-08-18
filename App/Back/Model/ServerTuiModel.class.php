<?php

/**
 * 智能推送模型
 * @time         2019-5-9
 * @author       tsj
 * @version     1.0
 */
namespace Back\Model;
use Think\Model;


class ServerTuiModel extends Model{
    protected $tableName = 'service_tui'; 
    protected  $_validate = array(
        array('title','require','推送标题不能为空'),
        array('desc','require','推送说明不能为空'),
        array('img_url','require','图片地址不能为空'),
        array('rank','require','等级不能为空'),
        array('type','require','等级不能为空'),
        array('max_sortrank','sortrankCheck','排序值不能为空',0,'callback'),
        array('status','require','状态不能为空')
    );
    
    protected $_auto = array(
        array('add_time','time',1,'function'),
        array('sortrank','sortrank',1,'callback'),
        array('yy2c','getYy2c',3,'callback'),
    );
    
    protected function sortrankCheck(){
        $max_sortrank = I('post.max_sortrank');
        $sortrank = I('post.sortrank');
        if($max_sortrank == 1 && $sortrank == ''){
            return false;
        }else{
            return true;
        }
    }
    
    protected function sortrank(){
        $max_sortrank = I('post.max_sortrank');
        $sortrank = I('post.sortrank');
        if($max_sortrank == 1){
            return $sortrank;
        }else{
            $max = M('service_tui')->limit(0,1)->order('sortrank desc')->getField('sortrank');
            $maxSortrank = $max ? $max+1 : 1;
            return $maxSortrank;
        }
    }
    
    protected function getYy2c() {
        $max_sortrank = $_POST['yy2c'];
        return $max_sortrank;
    }
}
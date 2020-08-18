<?php

/* 
 * 选择渠道或vip功能
 */
namespace Back\Controller;
class SelectChannelController extends CommonController{
    
    public function index() {
       
        $data = I('get.val');
        $memberdata = I('get.member_id');
        $is_show_channel = I('get.is_show_channel',1);
        $is_show_member = I('get.is_show_member',1);
        $where['status'] = array('eq', '1');
        //渠道数据
        $channellistarr = '';
        if (!empty($data)) {
            $this->assign('data', $data);
            $orders = explode(',', $data);
            $channellistarr = $orders;
            if (!empty($orders)) {
                session('channel_not',$channellistarr);
                $wheres['id'] = array('in', $orders);
                $moviesin = M('channel')->where($wheres)->field('nick_name,id,member_id')->select();
                $this->assign('listin', $moviesin);
                $where['id'] = array('not in', $orders);
            }
        }else{
            session('channel_not',null);
        }
        $where['status'] = array('neq',2);
        $count = M('channel')->where($where)->count(1);
        $movies = M('channel')->where($where)->limit(0, 20)->field('nick_name,id,member_id')->select();
        $this->assign('list', $movies);
        $this->assign('page', 1);
        $this->assign('count', $count);
        $channel_last = ceil($count/20); 
        $this->assign('channel_last', $channel_last);
        $this->assign('channelArr', $channellistarr);
        //vip数据
        $memberlistarr = '';
        if (!empty($memberdata)) {
            $this->assign('memberdata', $memberdata);
            $memberorders = explode(',', $memberdata);
            $memberlistarr = $memberorders;
            if (!empty($memberorders)) {
                session('member_not',$memberlistarr);
                $memberwheres['uid'] = array('in', $memberorders);
                $memberin = M('member')->where($memberwheres)->field('uid,user')->select();
                $this->assign('memberlistin', $memberin);
                $memberwhere['uid'] = array('not in', $memberorders);
            }
        }else{
            session('member_not',null);
        }
        
        $memberwhere['status'] = 1;
        $memberwhere['is_vip'] = 0;
        $vipcount = M('member')->where($memberwhere)->count(1);
        $memberarr =  M('member')->where($memberwhere)->limit(0, 20)->field('uid,user')->select();
        $cmember_last = ceil($vipcount/20); 
        $this->assign('memberlist', $memberarr);
        $this->assign('membercount', $vipcount);
        $this->assign('is_show_channel', $is_show_channel);
        $this->assign('is_show_member', $is_show_member);
        $this->assign('memberArr', $memberlistarr);
        
        $this->display();
    }
    
    public function getInChannel() {
        $data = I('get.val');
        $where['status'] = array('eq', '1');
        //渠道数据
        $channellistarr = '';
        if (!empty($data)) {
            $this->assign('data', $data);
            $orders = explode(',', $data);
            $channellistarr = $orders;
            if (!empty($orders)) {
                session('channel_not',$channellistarr);
                $wheres['id'] = array('in', $orders);
                $moviesin = M('channel')->where($wheres)->field('nick_name,id,member_id')->select();
                $this->assign('listin', $moviesin);
                $where['id'] = array('not in', $orders);
            }
        }else{
            session('channel_not',null);
        }
        $where['status'] = array('neq',2);
        $count = M('channel')->where($where)->count(1);
        $movies = M('channel')->where($where)->limit(0, 20)->field('nick_name,id,member_id')->select();
        $this->assign('list', $movies);
        $this->assign('page', 1);
        $this->assign('count', $count);
        $channel_last = ceil($count/20); 
        $this->assign('channel_last', $channel_last);
        $this->assign('channelArr', $channellistarr);
        $this->display();
    }
    

    
    public function getChannelData() {
        $page = I('get.page');
        if($page){
            $name = I('get.name');
            if(!empty($name)){
                $where['nick_name'] = array('like','%'.$name.'%');
            }
            $where['status'] = array('neq',2);
            if(session('?channel_not')){
                $where['id'] = array('not in', session('channel_not'));
            }
            $count = M('channel')->where($where)->count(1);
            $channel_last = ceil($count/20); 
            $indexpage = $page>0 && $channel_last <= $channel_last ? ($page-1)*20 : 0;
            $movies = M('channel')->where($where)->limit($indexpage, 20)->field('nick_name,id,member_id')->select();
            $ajaxRturn['count'] = $count;
            $ajaxRturn['list'] = $movies;
            $ajaxRturn['page'] = $channel_last;
            $this->ajaxReturn($ajaxRturn);
        }else{
            $arr = array();
            $this->ajaxReturn($arr);
        }
    }
    
    public function getAllChannelData() {
        $name = I('get.vipname');
        if($name){
            if(is_numeric($name)){
                $where['member_id'] = $name;
                $member  = M('member')->where('uid ='.$name)->field('user')->find();
            }else{
                $memberwhere['user']= ['like','%'.$name.'%'];
                $member  = M('member')->where($memberwhere)->field('uid,user')->find();
                $where['member_id'] = $member['uid'];
            }
            if($where['member_id'] == ''){
                $ajaxRturn['code'] = 0;
                $ajaxRturn['list'] = '';
                $this->ajaxReturn($ajaxRturn);
            }
            $where['status'] = array('neq',2);
            $movies = M('channel')->where($where)->field('nick_name,id,member_id')->select();
            $ajaxRturn['code'] = 200;
            $ajaxRturn['list'] = $movies;
            $ajaxRturn['member_name'] = $member['user'];
            $this->ajaxReturn($ajaxRturn);
        }else{
            $ajaxRturn['code'] = 0;
            $ajaxRturn['list'] = '';
            $this->ajaxReturn($ajaxRturn);
        }
    }
    
    public function getMemberData() {
        $page = I('get.page');
        if($page){
            $name = I('get.name');
            if(!empty($name)){
                $where['user'] = array('like','%'.$name.'%');
            }
            $where['status'] = array('neq',2);
            if(session('?member_not')){
                $where['uid'] = array('not in', session('member_not'));
            }
            $count = M('member')->where($where)->count(1);
            $channel_last = ceil($count/20); 
            $indexpage = $page>0 && $channel_last <= $channel_last ? ($page-1)*20 : 0;
            $movies = M('member')->where($where)->limit($indexpage, 20)->field('user,uid')->select();
            $ajaxRturn['count'] = $count;
            $ajaxRturn['list'] = $movies;
            $ajaxRturn['page'] = $channel_last;
            $this->ajaxReturn($ajaxRturn);
        }else{
            $arr = array();
            $this->ajaxReturn($arr);
        }
    }
    
    public function selectAppChannel() {
        $data = I('get.val');
        $where['status'] = array('eq', '1');
        //渠道数据
        $channellistarr = '';
        if (!empty($data)) {
            $this->assign('data', $data);
            $orders = explode(',', $data);
            $channellistarr = $orders;
            if (!empty($orders)) {
                session('app_channel_not',$channellistarr);
                $wheres['id'] = array('in', $orders);
                $moviesin = M('app_channel')->where($wheres)->field('account as nick_name,id')->select();
                $this->assign('listin', $moviesin);
                $where['id'] = array('not in', $orders);
            }
        }else{
            session('app_channel_not',null);
        }
        $count = M('app_channel')->where($where)->count(1);
        $movies = M('app_channel')->where($where)->limit(0, 20)->field('account as nick_name,id')->select();
        $this->assign('list', $movies);
        $this->assign('page', 1);
        $this->assign('count', $count);
        $channel_last = ceil($count/20); 
        $this->assign('channel_last', $channel_last);
        $this->assign('channelArr', $channellistarr);
        $this->display();
    }
    
    public function getAppChannelData() {
        $page = I('get.page');
        if($page){
            $name = I('get.name');
            if(!empty($name)){
                $where['account'] = array('like','%'.$name.'%');
            }
            $where['status'] = array('neq',2);
            if(session('?app_channel_not')){
                $where['id'] = array('not in', session('app_channel_not'));
            }
            $count = M('app_channel')->where($where)->count(1);
            $channel_last = ceil($count/20); 
            $indexpage = $page>0 && $channel_last <= $channel_last ? ($page-1)*20 : 0;
            $movies = M('app_channel')->where($where)->limit($indexpage, 20)->field('account as nick_name,id')->select();
            $ajaxRturn['count'] = $count;
            $ajaxRturn['list'] = $movies;
            $ajaxRturn['page'] = $channel_last;
            $this->ajaxReturn($ajaxRturn);
        }else{
            $arr = array();
            $this->ajaxReturn($arr);
        }
    }
    
}

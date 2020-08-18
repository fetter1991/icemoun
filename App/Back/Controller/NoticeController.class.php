<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/8 0008
 * Time: 17:37
 */
namespace Back\Controller;

use Think\Controller;

class NoticeController extends CommonController{
    public function index(){
        $array = array();
        $this->_list('notice',$array,'id desc');
        $this->display();
    }

    //用户公告列表
    public function userList(){

        $count = M('user_message')->where(array('type'=>1,'user_id'=>0))->count();
        import('Common.Lib.Page');
        $page = new \Common\Page($count,20);
        $voList = M('user_message')->where(array('type'=>1,'user_id'=>0))->order('reply_time desc')->select();
        foreach($voList as $k=>$v){
            $json = json_decode($v['content'],true);
            $voList[$k]['title'] = $json['title'];
        }
        $this->assign('list', $voList);

        $this->assign('page',$page->show());
        $this->display();
    }
    //作者公告列表
    public function authorList(){
        $count = M('author_notice')->where(array('status'=>1))->count();
        import('Common.Lib.Page');
        $page = new \Common\Page($count,20);
        $voList = M('author_notice')->where(array('status'=>1))->order('id desc')->select();
        $this->assign('list', $voList);
        $this->assign('page',$page->show());
        $this->display();
    }

    //获取用户公告
    public function getUserNoticeContent(){
        if(!IS_AJAX){
            exit('非法入口');
        }

        $id = I('post.id');
        $details = json_decode(M('user_message')->where('id ='.$id)->getField('content'),true);

        if(empty($details)){
            $this->ajaxReturn(array('code'=>0));
        }else{
            $this->ajaxReturn(array('code'=>200,'title'=>$details['title'],'content'=>html_entity_decode($details['content'])));
        }
    }

    public function getContent(){
        if(!IS_AJAX){
            exit('非法入口');
        }

        $id = I('post.id');
        $details = M('notice')->where('id ='.$id)->find();
        if(empty($details)){
            $this->ajaxReturn(array('code'=>0));
        }else{
            $this->ajaxReturn(array('code'=>200,'title'=>$details['title'],'content'=>html_entity_decode($details['content'])));
        }
    }

    public function add(){
        $this->display();
    }

    public function doAdd(){
        if(!IS_AJAX){
            exit('非法入口');
        }

        $data = I('post.');

        if($data['type'] == 1){
            $data['add_time'] = time();
            $bool = M('notice')->add($data);
        }else if($data['type'] == 2){
            $data['reply_time'] = time();
            $data['type'] = 1;
            $data['content'] =json_encode(array('title'=>$data['title'],'content'=>$data['content']));
            $bool =M('user_message')->add($data);
        }else{
            $data['add_time'] = time();
            $data['status'] = 1;
            $bool =M('author_notice')->add($data);
        }
        if($bool){
            $this->ajaxReturn(array('code'=>200));
        }else{
            $this->ajaxReturn(array('code'=>0,'msg'=>'保存失败'));
        }
    }

    public function edit(){
        $id = I('get.id');
        $type = I('get.type',0);
        $this->assign('type',$type);
        if($type == 2){
            $details = M('user_message')->where('id ='.$id)->find();
            $content = json_decode($details['content'],true);
            $details['title'] = $content['title'];
            $details['content'] = $content['content'];
        }else{
            $details = M('notice')->where('id ='.$id)->find();

        }

        $this->assign('details',$details);
        $this->display();
    }

    public function doEdit(){
        if(!IS_AJAX){
            exit('非法入口');
        }

        $data = I('post.');
        if($data['type'] == 2){
            $data['reply_time'] = time();
            $data['type'] = 1;
            $data['content'] =json_encode(array('title'=>$data['title'],'content'=>$data['content']));
            $bool = M('user_message')->save($data);
        }else{
            $bool = M('notice')->save($data);
        }
        if($bool){
            $this->ajaxReturn(array('code'=>200));
        }else{
            $this->ajaxReturn(array('code'=>0,'msg'=>'保存失败'));
        }
    }

    public function getAuthorNotice() {
        $id = I('get.id');
        $result = M('author_notice')->where('id ='.$id)->find();
        $this->ajaxReturn($result);
    }
    
    public function changeAuthor() {
        $form = I('post.');
        $result = M('author_notice')->save($form);
        if($result){
            $this->success('操作成功');
        }else{
            $this->error('更新失败');
        }
    }
    
    public function del(){
        if(!IS_AJAX){
            exit('非法入口');
        }
        $id = I('post.id');
        $type = I('post.type');
        if($type == 2){
            $bool = M('user_message')->where('id ='.$id)->delete();
        }else if($type == 1){
            $bool = M('notice')->where('id ='.$id)->delete();
        }else{
            $bool = M('author_notice')->where('id ='.$id)->save(['status'=>0]);
        }
        if($bool){
            $this->ajaxReturn(array('code'=>200));
        }else{
            $this->ajaxReturn(array('code'=>0));
        }
    }
}
<?php

/**
 * 礼品
 * @time         2019-6-21
 * @author       tsj
 * @version     1.0
 */

namespace Back\Controller;
class GiftsController extends CommentController{
    
    public function __construct() {
        parent::__construct();
        import('Common.Lib.Page');
    }

    /**
     * 列表页
     */
    public function index() {
        $Model = M('gifts');
        $getForm = I('get.');
        $where = array();
        if($getForm['name']){
            if(is_numeric($getForm['name'])){
                $where['id'] = $getForm['name'];
            }else{
                $where['title'] = array('like','%'.$getForm['name'].'%');
            }
        }
        $countNumber = $Model->where($where)->count(1);
        $page = new \Common\Page($countNumber);
        $result = $Model->where($where)
                        ->field('id,title,url,gold,status,count,order_num,add_time')
                        ->limit($page->firstRow, $page->listRows)->order('order_num desc')->select();
        $returnData['list'] = json_encode($result,true);
        $returnData['page'] = $page->show();
        $this->assign($returnData);
        $this->display();
    }
    
    public function add() {
        $Model = M('gifts');
        $rules = array(
            array('title', 'require', '标题不能为空'),
//            array('url', 'require', '图片不能为空'),
            array('gold', 'require', '金币不能为空'),
            array('status', 'require', '状态不能为空'),
            array('order_num', 'require', '排序值不能为空')
        );
        if (!$Model->validate($rules)->create()) {
            $this->ajaxReturn(['code'=>500,'msg'=>$Model->getError()]);
        } else {
            $post = I('post.');
            $addData['title'] = $post['title'];
            $addData['url'] = $post['url'];
            $addData['gold'] = $post['gold'];
            $addData['status'] = $post['status'];
            $addData['order_num'] = $post['order_num'];
            $addData['add_time'] = time();
            $addData['update_time'] = time();
            $res = $Model->add($addData);
            if($res){
                $this->ajaxReturn(['code'=>200,'msg'=>'添加成功']);
            }else{
                $this->ajaxReturn(['code'=>500,'msg'=>'添加失败']);
            }
        }
    }
    
    public function edit() {
        $Model = M('gifts');
        $rules = array(
            array('title', 'require', '标题不能为空'),
//            array('url', 'require', '图片不能为空'),
            array('gold', 'require', '金币不能为空'),
            array('status', 'require', '状态不能为空'),
            array('order_num', 'require', '排序值不能为空')
        );
        if (!$Model->validate($rules)->create()) {
            $this->error($Model->getError());
        } else {
            $post = I('post.');
            $addData['id'] = $post['id'];
            $addData['title'] = $post['title'];
            $addData['url'] = $post['url'];
            $addData['gold'] = $post['gold'];
            $addData['status'] = $post['status'];
            $addData['order_num'] = $post['order_num'];
            $addData['update_time'] = time();
            $res = $Model->save($addData);
            if($res){
                $this->ajaxReturn(['code'=>200,'msg'=>'修改成功']);
            }else{
                $this->ajaxReturn(['code'=>500,'msg'=>'修改失败']);
            }
        }
    }
    
    /**
     * 上下架
     */
    public function del() {
        $gifts_id = I('get.id');
        $gifts_info = M('gifts')->where('id ="' . $gifts_id . '"')->find();
        $status_get = $gifts_info['status'];
        $status = $status_get == 0 ? 1 : 0;
        $data['status'] = $status;
        if (M('gifts')->where('id ="' . $gifts_id . '"')->save($data)) {
            $this->ajaxReturn(array('code' => 200, 'msg' => '操作成功', 'status' => $status));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '操作失败'));
        }
    }
    
    /**
     * 获取单个礼品详情
     */
    public function getGiftsInfo() {
        $gifts_id = I('get.id');
        $gifts = M('gifts')->where('id ='.$gifts_id)->find();
        $this->ajaxReturn(['code'=>200,'data'=>$gifts]);
    }
    
}
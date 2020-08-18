<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/20 0020
 * Time: 19:05
 */

namespace Back\Controller;

use Think\Controller;

class RechargeController extends CommonController {

    //充值编辑，查询
    public function edit() {

        $where['activity_id'] = I('get.type') == 2 ? ['gt', 0] : 0;
        $count = M('goods')->where($where)->count(1);
        $Pagenum = 10;
        import('Common.Lib.Page');
        $page = new \Common\Page($count, $Pagenum);
        $data = M('goods')
                ->where($where)
                ->field('price,num,donate_num,deadline_num,title,content,type,status,add_time,hot,id,is_h5,is_app')
                ->order('id desc')
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
        $show = $page->show();
        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }

    //添加充值
    public function add() {
        $rules = array(
            array('price', 'require', '充值金额不能为空'),
            array('num', 'require', '充值书币不能为空'),
            array('donate_num', 'require', '赠送书币不能为空'),
            array('title', 'require', '标题不能为空' ),
            array('content', 'require', '内容不能为空'),
            array('order_num',  'require', '排序值不能为空'),
        );
        $User = M("goods"); // 实例化User对象
        if (!$User->validate($rules)->create()) {
            // 如果创建失败 表示验证没有通过 输出错误提示信息
            $this->error($User->getError());
        } else {
            $post = I('post.');
            $data['price'] = $post['price']; //充值金额
            $data['num'] = $post['num']; //充值书币
            $data['donate_num'] = empty($post['donate_num']) ? 0 : $post['donate_num']; //赠送书币
            $data['deadline_num'] = empty($post['day_num']) ? 0 : $post['day_num'] * 86400; //充值天数
            $data['title'] = $post['title']; //vip标题
            $data['content'] = $post['content']; //vip内容
            $data['order_num'] = $post['order_num']; //排序值
            $data['type'] = $post['type']; //0:充值书币 1:充值vip
            $data['status'] = $post['status']; //0:下架 1:上架
            $data['add_time'] = time(); //添加时间
            $data['hot'] = $post['hot']; //热门
            $data['is_h5'] = $post['is_h5'] ? 1 : 0; //热门
            $data['is_app'] = $post['is_app'] ? 1 : 0; //热门
            if($post['activity_id']){
                $data['activity_id'] = $post['activity_id']; //活动ID
            }
            if ($post['id']) {
                if ($res = $User->where(['id' => $post['id']])->save($data)) {
                    $this->success('修改成功', 'edit');
                    die;
                } else {
                    $this->error('请求超时');
                    die;
                }
            }
            if ($res = $User->add($data)) {
                $this->success('添加成功', 'edit');
                die;
            } else {
                $this->error('请求超时');
                die;
            }
        }
    }

    //显示
    public function Recharge() {
        if (IS_GET) {
            $get = I('get.');
            if ($data = M('goods')->where(['id' => $get['id']])->find()) {
                $this->ajaxReturn(array('code' => 200, 'data' => $data));
                die;
            }
        }
    }

    //删除
    public function del($id) {
        if ($res = M('goods')->where(['id' => $id])->delete()) {
            $this->ajaxReturn(array('code' => 200));
        } else {
            $this->ajaxReturn(array('code' => 0));
        }
    }

    //修改
    public function editRecharge() {
         $rules = array(
            array('price', 'require', '充值金额不能为空'),
            array('num', 'require', '充值书币不能为空'),
            array('donate_num', 'require', '赠送书币不能为空'),
            array('title', 'require', '标题不能为空' ),
            array('content', 'require', '内容不能为空'),
            array('order_num',  'require', '排序值不能为空'),
        );
        $User = M("goods"); // 实例化User对象
        if (!$User->validate($rules)->create()) {
            // 如果创建失败 表示验证没有通过 输出错误提示信息
            $this->error($User->getError());
        } else {
            $post = I('post.');
            $data['price'] = $post['price']; //充值金额
            $data['num'] = $post['num']; //充值书币
            $data['donate_num'] = empty($post['donate_num']) ? 0 : $post['donate_num']; //赠送书币
            $data['deadline_num'] = empty($post['day_num']) ? 0 : $post['day_num'] * 86400; //充值天数
            $data['title'] = $post['title']; //vip标题
            $data['content'] = $post['content']; //vip内容
            $data['order_num'] = $post['order_num']; //排序值
            $data['type'] = $post['type']; //0:充值书币 1:充值vip
            $data['status'] = $post['status']; //0:下架 1:上架
            $data['add_time'] = time(); //添加时间
            $data['hot'] = $post['hot']; //热门
            $data['is_h5'] = $post['is_h5'] ? 1 : 0; //热门
            $data['is_app'] = $post['is_app'] ? 1 : 0; //热门
            if ($post['id']) {
                if ($res = $User->where(['id' => $post['id']])->save($data)) {
                    $this->success('修改成功', 'edit');
                    die;
                } else {
                    $this->error('请求超时');
                    die;
                }
            }
        }
    }
    
    public function getActive() {

        $where['end_time'] = array('egt', time());
        $where['status'] = 1;
        $count = M('activity')->where($where)->count(1);
        $movies = M('activity')->where($where)->limit(0, 20)->field('title as nick_name,id')->select();
        $this->assign('list', $movies);
        $this->assign('page', 1);
        $this->assign('count', $count);
        $channel_last = ceil($count/20); 
        $this->assign('channel_last', $channel_last);
        $this->display();
    }
    
    public function getActiveData() {
        $page = I('get.page');
        if($page){
            $name = I('get.name');
            if(!empty($name)){
                $where['title'] = array('like','%'.$name.'%');
            }
            $where['status'] = 1;
            $where['end_time'] = array('egt', time());
            $count = M('activity')->where($where)->count(1);
            $channel_last = ceil($count/20); 
            $indexpage = $page>0 && $channel_last <= $channel_last ? ($page-1)*20 : 0;
            $movies = M('activity')->where($where)->limit($indexpage, 20)->field('title as nick_name,id')->select();
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

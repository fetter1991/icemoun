<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Back\Controller;

class DailiController extends CommonController {

    public function index() {
        $nick_name = I('get.nick_name');
        if (!empty(trim($nick_name))) {
            $where['a.nick_name'] = $nick_name;
            $this->assign('nick_name', $nick_name);
        }
        $where['a.status'] = 1;
        $count = M('daili as a')->where($where)->count();
        import('Common.Lib.Page');
        $p = new \Common\Page($count, 20);
        $show = $p->show(); // 分页显示输出
        $sql = M('daili_v_channel as b')->field('count(b.id)')->where('a.id = b.daili_id')->select(false);
        $data = M('daili as a')
                ->where($where)
                ->field('('.$sql.') as number,a.id,a.nick_name,a.account,a.bank,a.card_name,a.card_no,a.add_time,a.separate')
                ->limit($p->firstRow, $p->listRows)
                ->select();
        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }

    //添加代理
    public function add() {
        $companys = M('Company')->where('status=1')->select();
        $this->assign('companys', $companys);
        $this->display();
    }

    //新增代理加入数据库操作
    public function addpost() {
        $data = I('post.');
        $data['add_time'] = time();
        $data['password'] = md5($data['password']);
        $insertId = M('daili')->add($data);
        if ($insertId) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Daili/index');
            $res['data'] = $data;
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
            $res['jump_url'] = U('Daili/add');
            $res['data'] = [];
        }
        $this->ajaxReturn($res);
    }


    //更改密码
    public function changepwd() {
        $data = I('post.');
        $id = $data['id'];
        $newpwd = $data['val']; //新密码
        $originalpwd = M('daili')->where('id=' . $id)->getField('password'); //原始密码
        if (md5($newpwd) === $originalpwd) {
            $res['code'] = 2;
            $res['msg'] = '不能与原始密码相同';
        } else {
            $result = M('daili')->where('id=' . $id)->setField('password', md5($newpwd)); //原始密码
            if ($result) {
                $res['code'] = 0;
                $res['msg'] = '修改成功';
            } else {
                $res['code'] = 1;
                $res['msg'] = '修改失败';
            }
        }
        $this->ajaxReturn($res);
    }

    //查看代理公众号
    public function see() {
        $id = I('get.id');
        $result = M('daili_v_channel as a')
                ->field('a.id,b.nick_name')
                ->where("a.daili_id ={$id} and b.status=1")
                ->join('left join yy_channel as b on a.channel_id = b.id')
                ->select();
        if ($result) {
            $res['code'] = 0;
            $res['data'] = $result;
        } else {
            $res['code'] = 1;
            $res['data'] = [];
        }
        $this->ajaxReturn($res);
    }

    //删除该代理下的公众号
    public function delChannel() {
        $id = I('post.id');
        $result = M('daili_v_channel')->where('id=' . $id)->delete();
        if ($result) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Daili/index');
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
        }
        $this->ajaxReturn($res);
    }

    //删除代理
    public function del() {
        if (IS_AJAX) {
            $id = I('post.id');
            $result = M('daili')->where('id=' . $id)->setField('status', 0);
            if ($result) {
                $res['code'] = 0;
                $res['msg'] = 'ok';
            } else {
                $res['code'] = 1;
                $res['msg'] = 'error';
            }
            $this->ajaxReturn($res);
        }
    }

    //编辑代理信息
    public function edit() {
        $id = I('get.id');
        $data = M('daili')->where('id=' . $id)->find();
        $this->assign('data', $data);
        $this->display();
    }

    //修改代理信息
    public function editpost() {
        $data = I('post.');
        $id = $data['id'];
        $data['add_time'] = time();
        $insertId = M('daili')->where('id=' . $id)->save($data);
        if ($insertId) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Daili/index');
            $res['data'] = $data;
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
        }
        $this->ajaxReturn($res);
    }

}

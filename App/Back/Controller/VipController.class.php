<?php

namespace Back\Controller;

use Think\Controller;

class VipController extends CommonController {

    public function index() {
        $keywords = I('get.user');
        if (!empty(trim($keywords))) {
            $where['user|account'] = $keywords;
            $this->assign('keywords', $keywords);
        }
        $where['status'] = 1;

        $count = M('Member')->where($where)->count();
        import('Common.Lib.Page');
        $p = new \Common\Page($count, 20);
        $show = $p->show(); // 分页显示输出

        $data = M('Member')
                ->where($where)
                ->limit($p->firstRow, $p->listRows)
                ->select();
        foreach ($data as $k => $v) {
            $data[$k]['company'] = M('Company')->where('id=' . $v['company_id'])->getField('nick_name');
        }
        $this->assign('page', $show);
        $this->assign('data', $data);
        $this->display();
    }

    //添加会员
    public function add() {
        $companys = M('Company')->where('status=1')->select();
        $this->assign('companys', $companys);
        $this->display();
    }

    //新增会员加入数据库操作
    public function addpost() {
        $data = I('post.');
        $data['add_time'] = time();
        $data['password'] = md5($data['password']);
        $insertId = M('Member')->add($data);
        if ($insertId) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Vip/index');
            $res['data'] = $data;
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
            $res['jump_url'] = U('Vip/add');
            $res['data'] = [];
        }
        $this->ajaxReturn($res);
    }

    //添加超级会员
    public function addSvip() {
        $companys = M('Company')->where('status=1')->select();
        $this->assign('companys', $companys);
        $this->display();
    }

    public function addsvippost() {
        $data = I('post.');
        $data['add_time'] = time();
        $data['password'] = md5($data['password']);
        $data['is_vip'] = 1;
        $insertId = M('Member')->add($data);
        if ($insertId) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Vip/index');
            $res['data'] = $data;
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
            $res['jump_url'] = U('Vip/add');
            $res['data'] = [];
        }
        $this->ajaxReturn($res);
    }

    /**
     * 强制下线
     */
    public function signOut() {
        if (!IS_AJAX) {
            $returnData['code'] = 0;
            $returnData['msg'] = '非法访问';
            $this->ajaxReturn($returnData);
            exit();
        }
        $channel_id = I('post.id');
        if (empty($channel_id) || !is_numeric($channel_id)) {
            $returnData['code'] = 0;
            $returnData['msg'] = '参数错误';
            $this->ajaxReturn($returnData);
            exit();
        }
        M('member')->where('uid =' . $channel_id)->save(array('session_time' => 0));
        $returnData['code'] = 200;
        $returnData['msg'] = '操作成功';
        $this->ajaxReturn($returnData);
    }

    //更改会员密码
    public function changepwd() {
        $data = I('post.');
        $id = $data['id'];
        $newpwd = $data['val']; //新密码
        $originalpwd = M('Member')->where('uid=' . $id)->getField('password'); //原始密码
        if (md5($newpwd) === $originalpwd) {
            $res['code'] = 2;
            $res['msg'] = '不能与原始密码相同';
        } else {
            $save['password'] = md5($newpwd);
            $save['session_time'] = 0;
            $result = M('Member')->where('uid=' . $id)->save($save); //原始密码
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

    //查看会员公众号
    public function see() {
        $id = I('get.id');
        $result = M('Member')
                ->field('yy_member.user,yy_channel.*')
                ->where("yy_member.uid={$id} and yy_channel.status=1")
                ->join('yy_channel on yy_channel.member_id=yy_member.uid')
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

    //删除该会员下的公众号
    public function delChannel() {
        $id = I('post.id');
        $result = M('channel')->where('id=' . $id)->setField('member_id', 0);
        if ($result) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Vip/index');
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
        }
        $this->ajaxReturn($res);
    }

    //删除会员
    public function del() {
        if (IS_AJAX) {
            $id = I('post.id');
            $result = M('Member')->where('uid=' . $id)->setField('status', 0);
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

    //编辑会员信息
    public function edit() {
        $id = I('get.id');
        $data = M('member')->where('uid=' . $id)->find();
        $companys = M('Company')->where('status=1')->select();
        $this->assign('companys', $companys);
        $this->assign('data', $data);
        $this->display();
    }

    public function editpost() {
        $data = I('post.');
        $id = $data['uid'];
        $data['add_time'] = time();
        $insertId = M('Member')->where('uid=' . $id)->save($data);
        if ($insertId) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['jump_url'] = U('Vip/index');
            $res['data'] = $data;
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
        }
        $this->ajaxReturn($res);
    }

}

?>
<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/26 0026
 * Time: 16:40
 */

namespace Back\Controller;

use Think\Controller;

class MemberController extends CommonController {

    //用户管理
    public function index() {
        $adminId = session('user_id');
        $where = array();
        if ($adminId != 1) {
            $where['m.status'] = array('neq', 2);
        }
        import('Common.Lib.Page');
        $count = M('admin as m ')
                        ->join('yy_auth_group_access as a on m.id=a.uid')
                        ->join('left join yy_auth_group as b on a.group_id=b.id')
                        ->field('m.id,m.account,b.name')->count();
        $page = new \Common\Page($count, 20);
        $data = M('admin as m ')
                ->where($where)
                ->join('yy_auth_group_access as a on m.id=a.uid')
                ->join('left join yy_auth_group as b on a.group_id=b.id')
                ->field('m.id,m.account,b.name,m.status')
                ->limit($page->firstRow, $page->listRows)
                ->select();
        $this->assign('page', $page->show());
        $this->assign('data', $data);
        $this->display();
    }

    ///新增后台用户
    public function add() {
        $usergroup = M('auth_group')->field('id,name')->select();
        $this->assign('usergroup', $usergroup);
        if (IS_POST) {
            $post = I('post.');
            if (!$post['account']) {
                $this->error('账号不能为空！');
            }
            if (!$post['password']) {
                $this->error('账户密码不能为空！');
            }
            if (!$post['username']) {
                $this->error('用户名不能为空！');
            }
            if ($res = M('admin')->where(['account' => $post['account']])->count()) {
                $this->error('用户名已被占用');
            }
            $Admin = D('Admin');
            $data['password'] = $Admin->getPwd($post['password']);
            $data['add_time'] = time();
            $data['username'] = $post['username'];
            $data['create_id'] = session('user_id');
            $data['account'] = $post['account'];
            $uid = M('admin')->data($data)->add();
            $res = M('auth_group_access')->data(array('group_id' => $post['group_id'], 'uid' => $uid))->add();
            if ($res) {
                $channel_data['user_id'] = $uid;
                $channel_data['channel_id'] = $post['channel_id'];
                M('admin_channel')->add($channel_data);
                $this->success('添加成功', U('add'));
                die();
            }
        }
        $this->display();
    }

    public function admin_group() {
        $this->display('index');
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
        M('admin')->where('id =' . $channel_id)->save(array('session_time' => 0));
        $returnData['code'] = 200;
        $returnData['msg'] = '操作成功';
        $this->ajaxReturn($returnData);
    }

    //编辑修改
    public function edit($uid) {
        if (empty($uid)) {
            $this->redirect('Member/index');
        }
        $data = M('admin as m ')
                        ->join('yy_auth_group_access as a on m.id=a.uid')
                        ->join('left join yy_auth_group as b on a.group_id=b.id')
                        ->join('left join yy_admin_channel as c on c.user_id = m.id')
                        ->where(['m.id' => $uid])
                        ->field('m.account,b.name,m.id,m.username,a.group_id,c.channel_id,c.id as channel_table_id')->find();
        $data1 = M('auth_group')->field('id,name')->select();
        $this->assign('data1', $data1);
        $this->assign('data', $data);
        if (IS_POST) {
            $post = I('post.');
            if ($post['username']) {
                $da2['username'] = $post['username'];
                $res2 = M('admin')->where(['id' => $post['uid']])->save($da2);
            }
            if ($post['group_id']) {
                $da3['group_id'] = $post['group_id'];
                $res1 = M('auth_group_access')->where(['uid' => $post['uid']])->save($da3);
            }
            if ($post['channel_table_id']) {
                $channel_id['channel_id'] = $post['channel_id'];
                $res3 = M('admin_channel')->where(['id' => $post['channel_table_id']])->save($channel_id);
            } else if ($post['channel_id']) {
                $channel_id['channel_id'] = $post['channel_id'];
                $channel_id['user_id'] = $post['uid'];
                $res3 = M('admin_channel')->add($channel_id);
            }

            if ($res1 || $res2 || $res3) {
                $this->success('修改成功', U('index'));
                die;
            } else {
                $this->error('修改失败', U('index'));
                die;
            }
        }
        $this->display();
    }

    //删除后台用户
    public function del() {
        if (!IS_AJAX) {
            return;
        }
        $id = $_POST['id'];
        $save['status'] = 2;
        $bool = M('admin')->where(['id' => $id])->save($save);
        if ($bool) {
            $this->ajaxReturn(array('code' => 200));
        } else {
            $this->ajaxReturn(array('code' => 0));
        }
    }

    //停用后台用户
    public function stop() {
        if (!IS_AJAX) {
            return;
        }
        $id = $_POST['id'];
        $type = $_POST['type'];
        $save['status'] = ($type == 0) ? 1 : 0;
        $bool = M('admin')->where(['id' => $id])->save($save);
        if ($bool) {
            $this->ajaxReturn(array('code' => 200));
        } else {
            $this->ajaxReturn(array('code' => 0));
        }
    }

    /**
     * 修改密码
     */
    public function doResetPwd() {
        $Channel = D('Channel');
        if (false === $Channel->field('password,id')->create() || false === $Channel->save()) {
            $this->error($Channel->getError());
        }
        $form = I('post.');
        if ($form['password'] != '' && ($form['password'] == $form['rpassword'])) {
            $Admin = D('Admin');
            $da['password'] = $Admin->getPwd($form['password']);
            $da['add_time'] = time();
            $da['session_time'] = 0;
            $res = M('admin')->where(['id' => $form['id']])->save($da);
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $this->error('参数错误');
        }
    }

    public function selectChannel() {

        import('Common.Lib.Page');
        $name = I('get.name');
        $data = I('get.val');
        $where['status'] = array('eq', '1');
        if (!empty($name)) {
            if (is_numeric($name)) {
                $where['id'] = $name;
            } else {
                $where['nick_name'] = array('like', '%' . $name . '%');
            }
        }
        $channellistarr = '';
        if (!empty($data)) {
            $this->assign('data', $data);
            $orders = explode(',', $data);
            $channellistarr = $orders;
            if (!empty($orders)) {

                $wheres['id'] = array('in', $orders);
                $moviesin = M('channel')->where($wheres)->field('nick_name,id,member_id')->select();
                $this->assign('listin', $moviesin);
                $where['id'] = array('not in', $orders);
            }
        }
        $where['status'] = 1;
        $count = M('channel')->where($where)->count(1);
        $p = new \Common\Page($count, 20);
        $movies = M('channel')->where($where)->limit($p->firstRow, $p->listRows)->field('nick_name,id,member_id')->select();
        $this->assign('list', $movies);
        $this->assign('page', $p->show());
        $this->assign('channelArr', $channellistarr);
        $this->display();
    }

    public function getChannelName() {
        $channel_id = I('get.channel');
        if ($channel_id) {
            $channel_Arr = explode(',', $channel_id);
           
            foreach($channel_Arr as $value){
                $where['id'] = array('in', $value);
                $channel_name = M('channel')->where($where)->field('nick_name')->getField('nick_name');
                $channel_namestr .= $channel_name . ',';
            }
            $channel_namestr = trim($channel_namestr, ',');
            $this->ajaxReturn(array('data' => $channel_namestr));
        }
    }

}

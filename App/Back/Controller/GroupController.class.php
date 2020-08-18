<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/26 0026
 * Time: 11:17
 */

namespace Back\Controller;
use Common\Page;
use Think\Controller;
use Think\Auth;
class GroupController extends CommonController{
    public function index(){
            $count=M('auth_group')->count();
            import('Common.Lib.Page');
            $p=new Page($count,20);
            $group = M('auth_group')->limit($p->firstRow,$p->listRows)->select();
            $this->assign('list', $group);
            $this->assign('page', $p->show());
            $this->assign('nav', array('user', 'grouplist', 'grouplist'));//导航
            $this->display();
    }
    //获取所有启用的规则
    public function add(){
        $where['status']=1;
        $where['id']=array('NEQ',1);
        $data=M('admin_nav')->where($where)->field('id,pid,name')->select();
        $rule=$this->getMenu($data);
        $this->assign('rule', $rule);
        $this->display();
    }

    public function update()
    {
        $data['name'] = isset($_POST['name']) ? trim($_POST['name']) : false;
        $id = isset($_POST['id']) ? intval($_POST['id']) : false;
        if ($data['name']) {
            $data['status'] = $_POST['status'];
            $rules = $_POST['rules'] ;
            if (is_array($rules)) {
                foreach ($rules as $k => $v) {
                    $rules[$k] = intval($v);
                }
                $rules = implode(',', $rules);
            }
            $data['rules'] = $rules;
            if ($id) {
                $group = M('auth_group')->where('id=' . $id)->data($data)->save();
                if ($group) {
                    $this->success('恭喜，用户组修改成功！','index');
                    exit(0);
                } else {
                    $this->success('未修改内容');
                }
            } else {
                M('auth_group')->data($data)->add();
                $this->success('恭喜，新增用户组成功！','index');
                exit(0);
            }
        }else{
            $this->success('用户组名称不能为空！');
    }
    }

    public function edit(){
        $id = isset($_GET['id']) ? intval($_GET['id']) : false;
        if (!$id) {
            $this->error('参数错误！',U('Goup/index'));
        }
        $group = M('auth_group')->where('id=' . $id)->find();
        if (!$group) {
            $this->error('参数错误');
        }
        //获取所有启用的规则
        $where['status']=1;
        $where['id']=array('NEQ',1);
        $rule = M('admin_nav')->field('id,pid,name')->where($where)->select();
        $group['rules'] = explode(',', $group['rules']);
        $rule = $this->getMenu($rule);
        $this->assign('rule', $rule);
        $this->assign('group', $group);
        $this->assign('nav', array('user', 'grouplist', 'addgroup'));//导航
        $this->display();
    }

    public function del(){
        if(!IS_AJAX){
            exit('非法入口');
        }
        $id=$_POST['id'];
        $bool = M('auth_group')->where(['id'=>$id])->delete();
        if($bool){
            $this->ajaxReturn(array('code' => 200));
        } else {
            $this->ajaxReturn(array('code' => 0));
        }
    }
}
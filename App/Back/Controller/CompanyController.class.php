<?php

namespace Back\Controller;
use Common\Page;
use Think\Controller;


class CompanyController extends CommonController{
    public function index(){
        if(!empty(trim(I('get.nick_name')))){
            $where['nick_name']=I('get.nick_name');
            $this->assign('keywords',I('get.nick_name'));
        }
        $where['status']=1;
        $count=M('Company')->where($where)->count();
        import('Common.Lib.Page');
        $p=new Page($count,20);
        $show       = $p->show();// 分页显示输出

        $data=M('Company')
            ->where($where)
            ->order('add_time desc')
            ->limit($p->firstRow,$p->listRows)
            ->select();

        $this->assign('page',$show);
        $this->assign('data',$data);
        $this->display();
    }

    //添加会员
    public function add(){
        $this->display();
    }

    //新增会员加入数据库操作
    public function addpost(){
        $data=I('post.');
        $data['add_time']=time();
        $insertId=M('Company')->add($data);
        if ($insertId){
            $res['code']=0;
            $res['msg']='ok';
            $res['jump_url']=U('Company/index');
            $res['data']=$data;
        }else{
            $res['code']=1;
            $res['msg']='error';
            $res['jump_url']=U('Company/add');
            $res['data']=[];
        }
        $this->ajaxReturn($res);
    }

    public function edit(){
        $id=I('get.id');
        $data=M('Company')->where('id='.$id)->find();
        $this->assign('data',$data);
        $this->display();
    }

    //修改公司信息
    public function editpost(){
        if (IS_AJAX){
            $data=I('post.');
            $id=$data['id'];
            $result=M('Company')->where('id='.$id)->save($data);
            if ($result){
                $res['code']=0;
                $res['msg']='ok';
                $res['jump_url']=U('Company/index');
            }else{
                $res['code']=1;
                $res['msg']='error';
            }
            $this->ajaxReturn($res);
        }
    }
    //删除会员
    public function del(){
        if (IS_AJAX){
            $id=I('post.id');
            $result=M('Company')->where('id='.$id)->setField('status',0);
            if ($result){
                $res['code']=0;
                $res['msg']='ok';
                $res['jump_url']=U('Company/index');
            }else{
                $res['code']=1;
                $res['msg']='error';
            }
            $this->ajaxReturn($res);
        }
    }
}
?>
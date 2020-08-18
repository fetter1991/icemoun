<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/11
 * Time: 10:16
 */
namespace Back\Controller;
use Think\Upload;

class FormController extends CommonController{
    public function index(){

        $top = M('form')->where('pid = 0')->field('id,name')->select();
        $pid = I('pid',0);

        $map['pid'] = array('eq',$pid);
        $this->_list('form',$map,'add_time desc');

        $this->assign('pid',$pid);
        $this->assign('top',$top);

        $this->display();
    }


    public function doAdd(){
        $data = I('post.');
        // $setting=C('UPLOAD_FILE_QINIU');//七牛配置
        // $setting['savePath'] = 'form/';
        // $setting['autoSub'] = false;
        // $Upload=new Upload($setting);
        // $info=$Upload->upload($_FILES);
        // $data['banner'] = $info['banner']['url'];
        $data['add_time'] = time();
        if($this->_add('form',$data)){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    public function doEdit(){
        $data = I('post.');
        // $setting=C('UPLOAD_FILE_QINIU');//七牛配置
        // $setting['savePath'] = 'form/';
        // $setting['autoSub'] = false;
        // $Upload=new Upload($setting);
        // $info=$Upload->upload($_FILES);
        // if(!empty($info['banner']['url'])){
        //     $data['banner'] = $info['banner']['url'];
        // }
        
        $id = I('post.id');
        if(M('form')->where('id ='.$id)->save($data)){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }

    public function del(){
        if(!IS_AJAX){
            exit('非法入口');
        }
        $id = I('post.id');
        if(M('form')->where('id ='.$id)->delete()){
            $this->ajaxReturn(array('code'=>200));
        }else{
            $this->ajaxReturn(array('code'=>0));
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/18 0018
 * Time: 1:00
 */
namespace Back\Controller;
use Common\Page;
use Think\Controller;
use Dompdf\Dompdf;
use  Dompdf\Options;
class CommodityController extends CommonController{
    //活动商品列表
    public function index(){
        $count=M('article')->where('status=1')->count();
        import('Common.Lib.Page');
        $p=new Page($count,20);
        $data=M('article')
            ->where('status=1')
            ->limit($p->firstRow,$p->listRows)
            ->select();

        foreach ($data as $k => $v){
            $data[$k]['pic']=explode(';',$v['pic']);
        }
        $this->assign('data',$data);
        $this->assign('page',$p->show());
        $this->display();
    }

    //商品添加页面
    public function add(){
        //活动列表
        $activies=M('activity')->where('status=1')->select();
        $this->assign('activies',$activies);
        $this->display();
    }
    
    //提交商品添加
    public function addpost(){
        if (IS_POST){
            $data=I('post.');
            //使用正则表达式匹配正文内容中所有的img标签
            $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';//匹配img标签的正则表达式
            preg_match_all($preg, html_entity_decode($data['pic']), $allImg);//这里匹配所有的img

            $data['pic']=implode(';',$allImg[1]);
            $result=M('article')->add($data);
            if ($result){
                $res['code']=0;
                $res['msg']='ok';
                $res['jump_url']=U('Commodity/index');
            }else{
                $res['code']=1;
                $res['msg']='error';
                $res['jump_url']=U('Commodity/add');
            }
            $this->ajaxReturn($res);
        }
    }
    
    //商品删除
    public function del(){
        if (IS_AJAX){
            $id=I('post.id');
            $result=M('article')->where('id='.$id)->setField('status',0);
            if ($result){
                $res['code']=0;
                $res['msg']='ok';
                $res['jump_url']=U('Commodity/index');
            }else{
                $res['code']=1;
                $res['msg']='error';
                $res['jump_url']=U('Commodity/index');
            }
            $this->ajaxReturn($res);
        }
    }

    //商品修改
    public function edit(){
        //活动列表
        $activies=M('activity')->where('status=1')->select();
        $this->assign('activies',$activies);

        $id=I('get.id');
        $data=M('article')->where('id='.$id)->find();
        $this->assign('data',$data);
        $this->display();
    }
    //保存修改
    public function editpost(){
        if (IS_POST){
            $data=I('post.');
            $id=$data['id'];
            //使用正则表达式匹配正文内容中所有的img标签
            $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';//匹配img标签的正则表达式
            preg_match_all($preg, html_entity_decode($data['pic']), $allImg);//这里匹配所有的img
            $data['pic']=implode(';',$allImg[1]);
            $result=M('article')->where('id='.$id)->save($data);
            if ($result){
                $res['code']=0;
                $res['msg']='ok';
                $res['jump_url']=U('Commodity/index');
            }else{
                $res['code']=1;
                $res['msg']='error';
                $res['jump_url']=U('Commodity/add');
            }
            $this->ajaxReturn($res);
        }
    }
}
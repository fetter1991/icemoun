<?php

/**
 * 求片功能
 * @time         2019-5-27
 * @author       tsj
 * @version     1.0
 */

namespace Back\Controller;

use Common\Lib\Douban;

class SeekMoviesController extends CommonController {

    public function __construct() {
        parent::__construct();
        import('Common.Lib.Page');
    }

    /**
     * 求片列表
     */
    public function index() {
        
        $request = I('get.');
        $where = array();
        $returnData = array();
        if (!empty($request['start_time']) && !empty($request['end_time'])) {
            if ($request['end_time'] < $request['start_time']) {
                $this->error('结束时间不能小于开始时间');
            }
            $startTime = strtotime($request['start_time']);
            $endTime = strtotime($request['end_time']);
            $where['a.add_time'] = array(
                array('egt', $startTime),
                array('elt', $endTime)
            );
            $returnData['start_time'] = $request['start_time'];
            $returnData['end_time'] = $request['end_time'];
        }
        if ($request['user_id']) {
            $where['b.user_id'] = $request['user_id'];
            $returnData['user_id'] = $request['user_id'];
        }
        if ($request['orderName']) {
            $order = $request['orderName'] == 1 ? 'search_count desc' : 'add_time desc';
            $returnData['orderName'] = $request['orderName'];
        }else{
            $order = 'search_count desc';
        }
        if ($request['movies_name']) {
            $where['a.name'] = ['like','%'.$request['movies_name'].'%'];
            $returnData['movies_name'] = $request['movies_name'];
        }
        $count = M('movie_search as a')->where($where)
                ->join('left join yy_movies as c on c.db_id = a.db_id')
                ->count(1);
        $PageObj = new \Common\Page($count, 20);
        $searchData = M('movie_search as a')->where($where)
                        ->join('left join yy_movies as c on c.db_id = a.db_id')
                        ->field('a.id,a.name,a.search_count,a.db_url,a.db_id,a.add_time,c.status,c.id as movies_id,a.status as search_status')
                        ->limit($PageObj->firstRow, $PageObj->listRows)
                        ->order($order)->select();
        $returnData['list'] = $searchData;
        $returnData['page'] = $PageObj->show();
        $this->assign($returnData);
        $this->display();
    }

    public function addMoviesSearch() {

        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 3145728; // 设置附件上传大小
        $upload->exts = array('xlsx', 'xls'); // 设置附件上传类型
        $upload->rootPath = './Public/excelmode/'; // 设置附件上传根目录
        $upload->autoSub = false; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        // 上传文件 
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功
            $filename = './Public/excelmode/' . $info['file']['savename'];
            Vendor("phpexcel.Classes.PHPExcel");
            Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
            $add_arr = array();
            $objPHPExcel = \PHPExcel_IOFactory::createReader("Excel2007")->load($filename);
            $objPHPExcel->setActiveSheetIndex(0);
            $rows = $objPHPExcel->getActiveSheet(1)->getHighestRow();
            $index = 0;
            $is_bulr = true;
            for($i=2;$i<=$rows;$i++){
                $add_arr[$index]['db_id'] = $db_id = $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getValue();
                $add_arr[$index]['search_count'] = $user_id = $objPHPExcel->getActiveSheet()->getCell('B'.$i)->getValue();
              
                if(!is_numeric($db_id) || !is_numeric($user_id)){
                    $is_bulr = false;
                    continue;
                }
                $index++;
            }
            unlink($filename);
            if(!$is_bulr){
                $this->error('导入格式错误，请检查重试','',10);
            }
            $success_row = 0;
            foreach($add_arr as $value){
                $where = array();
                $where['db_id'] = $value['db_id'];
                $ishave = M('movie_search')->where($where)->field('id')->find();
               
                if(!$ishave){
                    $doubanObj = new Douban($value['db_id']);
                    $movies_info = $doubanObj->getAttributes($doubanObj);
                    $insetData = $movies_info;
                    $insetData['db_url'] = 'https://m.douban.com/movie/subject/'.$value['db_id'].'/';
                    $insetData['db_id'] = $value['db_id'];
                    $insetData['search_count'] = $value['search_count'];
                    $insetData['update_time'] = time();
                    $insetData['add_time'] = time();
                    $insetID = M('movie_search')->add($insetData);
                    if($insetID){
                        $success_row++;
                    }
                }else{
                        $insetsear['search_count'] = $value['search_count'];
                        $ojbk = M('movie_search')->where($where)->save($insetsear);
                        if($ojbk ){
                            $success_row++;
                        }
                    }
                }
            
            if($success_row == 0){
                $this->error('批量插入失败');
            }else{
                $this->success('成功'.$success_row.'条，失败'.($index-$success_row).'条.');
            }
        }
    }
    
    public function delect() {
        $id = I('id');
        $res = M('movie_search')->where('id ='.$id)->save(['status'=>0]);
        if($res){
            $this->ajaxReturn(['code'=>200,'msg'=>'删除成功']);
        }else{
            $this->ajaxReturn(['code'=>0,'msg'=>'删除失败']);
        }
    }
    
    public function upperShelf() {
        $id = I('id');
        $res = M('movie_search')->where('id ='.$id)->save(['status'=>1]);
        if($res){
            $this->ajaxReturn(['code'=>200,'msg'=>'上架成功']);
        }else{
            $this->ajaxReturn(['code'=>0,'msg'=>'上架失败']);
        }
    }
    
    public function showUser() {
        $id = I('id');
        $data = M('user_movie_search as a')
                ->join('left join yy_user_info as b on a.user_id = b.user_id')
                ->field('b.nick_name,a.user_id')
                ->where('a.search_id ='.$id)->select();
        $this->ajaxReturn($data);
    }

}

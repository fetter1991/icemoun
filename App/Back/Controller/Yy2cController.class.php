<?php

/**
 * yy2c 公共方法
 * @time         2019-5-9
 * @author       tsj
 * @version     1.0
 */

namespace Back\Controller;

class Yy2cController extends CommonController {

    public function index() {
        
        $this->display();
    }

    public function analysisYy2c($yy2c) {
        $json_yy2c = json_decode($yy2c, true);
        $arr_yy2c = json_decode($json_yy2c['cus_yy2c'], true);
        $push_count = '';
        switch ($arr_yy2c['a']) {
            case 1:
                $movies_id = $arr_yy2c['p']['mid'];
                $movies = M('movies')->where('id =' . $movies_id)->field('name')->find();
                $push_count = 'ID:' . $movies_id . '<br>图解名称：' . $movies['name'];
                break;
            case 2:
                $push_count = '打开充值中心';
                break;
            case 3:
                $push_count = '打开弹窗提示：' . $arr_yy2c['p']['content'];
                break;
            case 4:
                $active_id = $arr_yy2c['p']['aid'];
                $active = M('activity')->where('id =' . $active_id)->field('title')->find();
                $push_count = 'ID:' . $active_id . '<br>活动名称：' . $active['title'];
                break;
            case 5:
                $topic_id = $arr_yy2c['p']['tid'];
                $topic = M('topic')->where('id =' . $topic_id)->field('name')->find();
                $push_count = 'ID:' . $topic_id . '<br>专辑名称：' . $topic['name'];
                break;
            case 100:
                $topic_id = $arr_yy2c['p']['url'];
                $push_count = '打开URL：' . $topic_id;
                break;
            default:
                break;
        }
        return $push_count;
    }

    /**
     * 选择影片
     */
    public function getmovies() {
        import('Common.Lib.Page');
        $name = I('get.name');
        $type = I('get.type');

        $where['status'] = 1;
        if (!empty($name)) {
            if (is_numeric($name)) {
                $where['id'] = $name;
            } else {
                $map['name'] = array('like', '%' . $name . '%');
                $map['org_name'] = array('like', '%' . $name . '%');
                $map['_logic'] = 'or';
                $where['_complex'] = $map;
            }
        }
        if (!empty($type)) {
            $this->assign('type', $type);
        }
        $count = M('Movies')->where($where)->count(1);
        $p = new \Common\Page($count, 20);
        $movies = M('Movies')->where($where)->limit($p->firstRow, $p->listRows)->field('id,name,org_name,banner,cover,tags,desc,rank')->order('id desc')->select();
        $this->assign('list', $movies);
        $this->assign('page', $p->show());
        $this->display('getmovies');
    }

    /**
     * 获取活动内容
     */
    public function selectActivity() {
        $Model = M('activity');

        //取得满足条件的记录数
        $where['status'] = 1;
        $where['end_time'] = array('egt', time());
        $where['begin_time'] = array('elt', time());
        $count = $Model->where($where)->count('1');
        import('Common.Lib.Page');
        $page_size = C('PAGE_LIST_SIZE');
        $page = new \Common\Page($count, $page_size);
        if (I('get.name')) {
            if (is_numeric(I('get.name'))) {
                $where['id'] = I('get.name');
            } else {
                $where['title'] = array('like', I('get.name') . '%');
            }
        }
        $voList = $Model->alias('a')->where($where)->order('add_time desc')->limit($page->firstRow . ',' . $page->listRows)->field('id,title,begin_time,end_time')->select();
        $this->assign('list', $voList);
        $this->assign('page', $page->show());
        $this->display('selectActivity');
    }

    /**
     * 获取专辑内容
     */
    public function selectTopic() {
        $Model = M('Topic');

        //取得满足条件的记录数
        $where['status'] = 1;

        $count = $Model->where($where)->count('1');
        import('Common.Lib.Page');
        $page_size = C('PAGE_LIST_SIZE');
        $page = new \Common\Page($count, $page_size);
        if (I('get.name')) {
            if (is_numeric(I('get.name'))) {
                $where['id'] = I('get.name');
            } else {
                $where['a.name'] = array('like', I('get.name') . '%');
            }
        }
        $voList = $Model->alias('a')->where($where)->order('add_time desc')->limit($page->firstRow . ',' . $page->listRows)->field('id,name,desc,cover,rank')->select();
        $this->assign('list', $voList);
        $this->assign('page', $page->show());
        $this->display('selectTopic');
    }

}

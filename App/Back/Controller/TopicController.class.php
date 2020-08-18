<?php

/**
 * “专辑模块”功能模块
 *
 * @author      田少军
 * @version     1.0 版本号
 */

namespace Back\Controller;

class TopicController extends CommonController {

    public function index() {
        //获取类别
        $form = M('form')->where('pid = 0')->field('id,name')->select();
        $this->assign('selCategory', $form);
        //end 分类
        //作者
        $author = M('author')->where(array('status' => 1))->field('id,nick_name')->select();
        $this->assign('author', $author);
        //end作者
        //专辑查询
        $where = array();
        $status = I('status', 2);
        $this->assign('status', $status);
        if ($status != 2) {
            $where['status'] = $status;
        } else {
            $where['status'] = array('neq', 2);
        }
        $rank =I('get.rankForm');
        if ($rank != '') {
            $where['rank'] = $rank;
            $this->assign('rankForm', $rank);
        }else{
            $this->assign('rankForm', '99991');
        }
        if(I('name') != ''){
            $where['name'] = array('like','%'.I('name').'%');
            $this->assign('name',  I('name'));
        }
	    if ( I( 'topic_type' ) != '' ) {
		    $where['topic_type'] = I( 'topic_type' );
		    $this->assign( 'topic_type', I( 'topic_type' ) );
	    }
        import('Common.Lib.Page');
        $count = M('topic')->where($where)->count(1);
        $page = new \Common\Page($count, 20);
        $Topic = M('topic')->alias('t')->where($where)
                        ->order('id desc')
                        ->limit($page->firstRow, $page->listRows)->select();
        foreach ($Topic as &$value) {
            $movies_list = M('topic_movies')->alias('t')
                            ->join('yy_movies as m on t.movies_id = m.id')
                            ->where('t.topic_id =' . $value['id'])->field('t.movies_id,m.name,m.org_name')->select();
            $value['movies_list'] = $movies_list;
        }
        $this->assign('list', $Topic);
        $this->assign('page', $page->show());
        $this->display();
    }

    /**
     * 增加专辑
     */
    public function add() {
        $data = I('post.');
        $movies_str = $data['movies_id'];
        $script_id = explode(',', $movies_str);
        $return_data['code'] = 0;
        $return_data['msg'] = '';
        foreach ($script_id as $movies_id) {
            if (!is_numeric($movies_id)) {
                $return_data['msg'] = '影片id错误';
                $this->ajaxReturn($return_data);
            }
        }
        unset($data['movies_id']);
        if ($data['Pushtarget'] == 'selectInput') {
            $author = M('author')->where(array('id' => $data['authorSelect']))->getField('nick_name');
            if (empty($author)) {
                $return_data['msg'] = '作者错误';
                $this->ajaxReturn($return_data);
            }
            $data['author'] = $author;
            $data['author_id'] = $data['authorSelect'];
        } else {
            $data['author'] = $data['author'];
        }
        if ($data['is_online'] == 1) {
            $data['online_time'] = strtotime($data['online_time']);
        }

        unset($data['Pushtarget']);
        unset($data['authorSelect']);
        $data['add_time'] = time();
        $data['lastupdate'] = time();
        $insert_id = M('topic')->add($data);

        // 批量添加数据
        $topic_movies = M('topic_movies');
        $dataList = array();
        $add_time = time();
        $num = count($script_id);
        foreach ($script_id as $values) {
            $rank = M('movies')->where('id =' . $values)->getField('rank');
            $dataList[] = array('movies_id' => $values, 'rank' => $rank, 'topic_id' => $insert_id, 'add_time' => $add_time,'order_num'=>$num);
            $num--;
        }
        $topic_movies->addAll($dataList);
        if ($insert_id && $topic_movies) {
            //统计专辑下影片数量
            $movies_sum = $topic_movies->where('topic_id = ' . $insert_id)->count(1);
            M('topic')->where('id = '.$insert_id)->save(['movies_count' => $movies_sum]);

            $return_data['code'] = 200;
            $return_data['msg'] = '添加成功';
            $this->ajaxReturn($return_data);
        } else {
            $return_data['msg'] = '添加失败';
        }
    }

    /**
     * 修改
     */
    public function edit() {
        $data = I('post.');
        if (empty($data['id'])) {
            $this->error('参数错误');
        }
        $movies_str = $data['movies_id'];
        $script_id = explode(',', $movies_str);
        $return_data['code'] = 0;
        $return_data['msg'] = '';
        if(empty($movies_str)){
            $return_data['msg'] = '影片不能为空';
            $this->ajaxReturn($return_data);
        }
        foreach ($script_id as $movies_id) {
            if (!is_numeric($movies_id)) {
                $return_data['msg'] = '影片id错误';
                $this->ajaxReturn($return_data);
            }
        }
        unset($data['movies_id']);

        if ($data['Pushtarget'] == 'selectInput') {
            $author = M('author')->where(array('id' => $data['authorSelect']))->getField('nick_name');
            $data['author'] = $author;
            $data['author_id'] = $data['authorSelect'];
        } else {
            $data['author'] = $data['author'];
            $data['author_id'] = 0;
        }
        if ($data['is_edit_online'] == 1) {
            $data['online_time'] = strtotime($data['online_time']);
        }else{
            $data['online_time'] = 0;
        }
        unset($data['Pushtarget']);
        unset($data['authorSelect']);
        $topic_movies = M('topic_movies');
        $movies_list = $topic_movies->where('topic_id = '.$data['id'])->field('movies_id')->select();
        $movies_id_arr = array(); //新增数据
        $movies_diff = array(); //删除数据
        $movies_intersect = array();
        foreach ($movies_list as $value) {
            if(!in_array($value['movies_id'], $script_id)){
                $movies_diff[] = $value['movies_id'];
            }
            $movies_id_arr[] = $value['movies_id'];
        }
        foreach ($script_id as $values) {
            if(!in_array($values, $movies_id_arr)){
                $movies_intersect[] = $values;
            }
        }
        $delect_res = false;
        if(!empty($movies_diff)){
            $delect['topic_id'] = $data['id'];
            $delect['movies_id'] = array('in',$movies_diff);
            $delect_res = $topic_movies->where($delect)->delete();
        }
        $movies_res = false;
        if(!empty($movies_intersect)){
            // 批量添加数据
            $dataList = array();
            $add_time = time();
            foreach ($movies_intersect as $values) {
                $rank = M('movies')->where('id =' . $values)->getField('rank');
                $dataList[] = array('movies_id' => $values, 'rank' => $rank, 'topic_id' => $data['id'], 'add_time' => $add_time);
            }
            $movies_res = $topic_movies->addAll($dataList);
        }
        //统计专辑下影片数量
        $movies_sum = $topic_movies->where('topic_id = ' . $data['id'])->count(1);
        $data['movies_count'] = $movies_sum;

        if (M('topic')->save($data) || $movies_res || $delect_res) {
            $return_data['code'] = 200;
            $return_data['msg'] = '修改成功';
            $this->ajaxReturn($return_data);
        } else {
            $return_data['msg'] = '修改失败';
            $this->ajaxReturn($return_data);
        }
    }

    //上下架
    public function setStatus() {
        if (!IS_AJAX) {
            exit('非法入口');
        }
        $id = I('post.id');
        $status = M('topic')->where('id ="' . $id . '"')->getField('status');
        if ($status == 0) {
            $status = 1;
        } else {
            $status = 0;
        }
        $online = time();
        $data = array();
        if ($status == 1) {
            $data = array('status' => $status, 'online_time' => $online);
        } else {
            $data = array('status' => $status);
        }
        if (M('topic')->where('id ="' . $id . '"')->save($data)) {
            $this->ajaxReturn(array('code' => 200, 'msg' => '操作成功', 'status' => $status));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '操作失败'));
        }
    }

    /**
     * 获取章节信息
     * return : 章节信息json字符串
     */
    public function changePage() {
        if (!IS_AJAX) {
            $this->ajaxReturn(array('code' => 0, 'res' => '非法访问'));
        }
        $id = I('post.id');
        if (empty($id)) {
            $this->ajaxReturn(array('code' => 0, 'res' => '章节id为空'));
        }

        $result = M('topic')->where('id =' . $id)->find();
        $movies_list = M('topic_movies')->where('topic_id ='.$result['id'])->field('movies_id')->select();
        $movies = '';
        foreach($movies_list as $movies_id){
            $movies .= $movies_id['movies_id'].',';
        }
        $moviesId = trim($movies,',');
        $result['movies_id'] = $moviesId;
        $result['online_time'] = $result['online_time'] != 0 ? date('Y-m-d H:i:s',$result['online_time']) : 0;
        $this->ajaxReturn($result);
    }

    /**
     * 专辑包含影片列表
     */
    public function changeMoviesOrder() {
        $id = I('get.id');
        if (!empty($id)) {
            $movies_list = M('topic_movies')->alias('t')
                ->join('yy_movies as m on t.movies_id = m.id')
                ->where('t.topic_id =' . $id)->field('t.id,t.movies_id,t.topic_id,m.name,m.org_name,t.order_num')
                ->order('order_num desc')
                ->select();
            $this->assign('list', $movies_list);
        }
        $this->display();
    }

    public function changeNumBer() {
        $returnData['code'] = 0;
        $returnData['msg'] = '发生错误';
        $data = $_POST['data'];
        if(empty($data)){
            $returnData['msg'] = '参数错误';
            $this->ajaxReturn($returnData);
        }
        $json = json_decode($data,TRUE);
        foreach($json as $value){
            M('topic_movies')->save($value);
        }
        $returnData['code'] = 200;
        $returnData['msg'] = '修改成功';
        $this->ajaxReturn($returnData);
    }

    //删除
    public function del() {
        if (!IS_AJAX) {
            exit('非法入口');
        }
        $id = I('post.id');

        if (M('topic')->where('id ="' . $id . '"')->save(array('status' => 2))) {//伪删除
            $this->ajaxReturn(array('code' => 200, 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    //删除专辑影片
    public function delMovies() {
        if (!IS_AJAX) {
            exit('非法入口');
        }
        $id = I('post.id');
        $topic_id = I('post.topic_id');
        if (M('topic_movies')->where('id ="' . $id . '"')->delete()) {//伪删除
            //统计专辑下影片数量
            $movies_sum = M('topic_movies')->where('topic_id = ' . $topic_id)->count(1);
            $data['movies_count'] = $movies_sum;
            M('topic')->where('id = '.$topic_id)->save($data);

            $this->ajaxReturn(array('code' => 200, 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '删除失败'));
        }
    }


    /**
     * 选择影片
     */
    public function selectMovies() {
        $name = I('name');
        $innerexpand_ids = I('data');
        $innerId = explode(',', $innerexpand_ids);
        $isEmpty = !empty($innerId) ? $innerId : '';
        if (!empty($name)) {
            $wName['name'] = array('like', '%' . $name . '%');
            $wName['org_name'] = array('like', '%' . $name . '%', 'or');
            $wName['_logic'] = 'or';
            $where['_complex'] = $wName;
            $this->assign('name', $name);
        }
        $count = M('movies')->where($where)->count(1);
        import('Common.Lib.Page');
        if (!empty($isEmpty)) {
            $wheres['id'] = array('in', $isEmpty);
            $moviesin = M('movies')->where($wheres)->field('id,name,org_name')->order('add_time desc')->select();
            $this->assign('listin', $moviesin);
            $where['id'] = array('not in', $isEmpty);
        }
        $where['status'] = 1;
        $p = new \Common\Page($count, 20);
        $data = M('movies')->where($where)->field('id,name,org_name')->order('add_time desc')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $data);
        $this->assign('page', $p->show());
        $this->assign('data', $innerexpand_ids);
        $this->assign('innerId', $isEmpty);
        $this->display('selectMovie');
    }

}

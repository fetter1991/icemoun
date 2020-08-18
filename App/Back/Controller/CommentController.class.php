<?php

/*
 * 审核功能
 * 
 */

namespace Back\Controller;

class CommentController extends CommonController {

    public $publicCommentArr = array();

    public function toExamine() {
        //按照处理状态搜索

        if (I('get.status') != '') {
            if (is_numeric(I('get.status'))) {
                if (I('get.status') == 0 || I('get.status') == 1 || I('get.status') == 2) {
                    $where['ck.status'] = I('get.status');
                }
                $this->assign('status', I('get.status'));
            }
        } else {
            $where['ck.status'] = 0;
            $this->assign('status', 0);
        }
        //按照时间区间查询
        if (!empty(I('get.start_time')) && !empty(I('get.end_time')) && I('get.end_time') > I('get.start_time')) {
            $start_time = I('get.start_time');
            $end_time = I('get.end_time');
            $where['ck.add_time'] = array(
                array('egt', strtotime($start_time)),
                array('elt', strtotime($end_time))
            );
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        }

        //模糊搜索
        if (!empty(trim(I('get.user_id')))) {
            $where['ck.user_id'] = trim(I('get.user_id'));
            $this->assign('user_id', trim(I('get.user_id')));
        }
        if (!empty(trim(I('get.movies_id')))) {
            $where['ck.movies_id'] = trim(I('get.movies_id'));
            $this->assign('movies_id', trim(I('get.movies_id')));
        }

        import('Common.Lib.Page');
        $page_size = C('PAGE_LIST_SIZE');
        $count = M('movies_comments_check as ck')->where($where)->count('1');
        $page = new \Common\Page($count, $page_size);
        $res = M('movies_comments_check as ck')->where($where)
                        ->join('left join yy_movies as m on m.id = ck.movies_id')
                        ->join('left join yy_movies_comments as c on c.id = ck.reply_comments_id')
                        ->field('ck.id,ck.user_id,ck.movies_id,ck.comments,ck.add_time,ck.status,ck.reason,ck.admin_id,ck.check_time,m.name,c.comments_id,c.id as check_id')
                        ->order('add_time desc')
                        ->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('data', $res);
        $this->assign('page', $page->show());
        $this->display();
    }

    /**
     * 获取评论详细信息
     */
    public function getInfo() {
        $id = I('get.id');
        $where['ck.id'] = $id;
        $res = M('movies_comments_check as ck')->where($where)
                ->join('left join yy_movies as m on m.id = ck.movies_id')
                ->join('left join yy_admin as d on d.id = ck.admin_id')
                ->join('left join yy_user_info as i on i.user_id = ck.user_id')
                ->field('ck.id,ck.user_id,ck.movies_id,ck.comments,FROM_UNIXTIME(ck.add_time,"%Y-%m-%d %H:%i:%s") as add_time,ck.status,ck.reason,FROM_UNIXTIME(ck.check_time,"%Y-%m-%d %H:%i:%s") as check_time,m.name,d.username,i.nick_name')
                ->find();
        $this->ajaxReturn($res);
    }

    /**
     * 审核功能
     */
    public function check() {
        $type = I('get.type');
        $id = I('get.id');
        if ($id == '') {
            $this->ajaxReturn(array('code' => 0, 'message' => 'id不能为空'));
        }
        if ($type == '') {
            $this->ajaxReturn(array('code' => 0, 'message' => '参数错误'));
        }
        $explodeId = explode(',', $id);
        $count = count($explodeId);
        $errorCount = 0;
        $comment_id_str = '';
        foreach ($explodeId as $comment_id) {
            $insertData = M('movies_comments_check')->where('id =' . $comment_id)->find();
            if ($insertData['status'] == 0) {
                $save['status'] = $type;
                $save['reason'] = I('get.reason') != '' ? I('get.reason') : '';
                $save['admin_id'] = session('user_id');
                $save['check_time'] = time();
                M('movies_comments_check')->where('id =' . $comment_id)->save($save);

                if ($type == 1) {
                    $addData['user_id'] = $insertData['user_id'];
                    if ($insertData['reply_comments_id'] != 0) {
                        $addData['to_user_id'] = M('movies_comments')->where('id =' . $insertData['reply_comments_id'])->getField('user_id');
                        $select_comments_id = M('movies_comments')->where('id =' . $insertData['reply_comments_id'])->getField('comments_id');
                        $addData['comments_id'] = $select_comments_id != 0 ? $select_comments_id : $insertData['reply_comments_id'];
                        M('movies_comments')->where('id = ' . $addData['comments_id'])->setInc('num_reply');
                    } else {
                        $addData['to_user_id'] = 0;
                        $addData['comments_id'] = 0;
                    }
                    M('movies')->where('id =' . $insertData['movies_id'])->setInc('comments_num'); //影片评论数加1
                    $addData['movies_id'] = $insertData['movies_id'];
                    $addData['reply_comments_id'] = $insertData['reply_comments_id'];
                    $addData['comments'] = $insertData['comments'];
                    $addData['status'] = 1;
                    $addData['add_time'] = time();
                    $add_comment_id = M('movies_comments')->add($addData);
                    $comment_id_str .= $add_comment_id . ',';
                }
            } else {
                $errorCount++;
            }
        }
        $crypt = new \Org\Encry\CryptAES();
        $crypt->set_key('a3fc338dcca1642037d3a56082fc5453');
        $push_arr = array('comment_id' => trim($comment_id_str, ','));
        $ciphertext = $crypt->encrypt(json_encode($push_arr));
        $url = 'http://wxapp-test.jiayoumei-tech.com/api/app/v1/movie/set_comment_count';
        $postdata = array('params' => $ciphertext);
        $res = http_request($url, $postdata);
        $jsonres = json_decode($res,true);
        if ($jsonres['status_code'] == 200) {
            $this->ajaxReturn(array('code' => 200, 'message' => '处理成功','data'=>$postdata));
        } else {
            $this->ajaxReturn(array('code' => 500, 'message' => '处理失败','data'=>$res,'res'=>$postdata));
        }
    }

    /**
     * 上线评论列表
     */
    public function index() {

        //按照处理状态搜索
        if (is_numeric(I('get.status'))) {
            if (I('get.status') == 0 || I('get.status') == 1 || I('get.status') == 2) {
                $where['ck.status'] = I('get.status');
            }
            $this->assign('status', I('get.status'));
        }
        //按照时间区间查询
        if (!empty(I('get.start_time')) && !empty(I('get.end_time')) && I('get.end_time') > I('get.start_time')) {
            $start_time = I('get.start_time');
            $end_time = I('get.end_time');
            $where['ck.add_time'] = array(
                array('egt', strtotime(date('Y-m-d 00:00:00', strtotime($start_time)))),
                array('elt', strtotime(date('Y-m-d 23:23:59', strtotime($end_time))))
            );
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        }

        //模糊搜索
        if (!empty(trim(I('get.user_id')))) {
            $where['ck.user_id'] = trim(I('get.user_id'));
            $this->assign('user_id', trim(I('get.user_id')));
        }
        if (!empty(trim(I('get.movies_id')))) {
            $where['ck.movies_id'] = trim(I('get.movies_id'));
            $this->assign('movies_id', trim(I('get.movies_id')));
        }
        if (!empty(trim(I('get.comment_id')))) {
            $where['ck.id'] = trim(I('get.comment_id'));
        }


        import('Common.Lib.Page');
        $page_size = C('PAGE_LIST_SIZE');
        $where['comments_id'] = 0;
        $count = M('movies_comments as ck')->where($where)->count('1');
        $page = new \Common\Page($count, $page_size);
        $res = M('movies_comments as ck')->where($where)
                        ->join('left join yy_movies as m on m.id = ck.movies_id')
                        ->field('ck.id,ck.user_id,ck.movies_id,ck.comments,ck.add_time,ck.status,ck.add_time,m.name,ck.num_oo,ck.num_xx,ck.num_reply')
                        ->order('add_time desc')
                        ->limit($page->firstRow . ',' . $page->listRows)->select();
        $this->assign('data', $res);
        $this->assign('page', $page->show());

        $this->display();
    }

    /**
     * 下架评论
     */
    public function changeStatus() {
        $id = I('post.id');
        $status = I('post.status');
        if (is_numeric($status) && is_numeric($id)) {
            $res = M('movies_comments')->where('id =' . $id)->save(array('status' => $status));
            $movies = M('movies_comments')->where('id = ' . $id)->field('movies_id,comments_id')->find();
            $this->getChildrenCount($id);
            $childrenCount = $this->childCount + 1;
            if ($status == 0) {
                if ($id != $movies['comments_id']) {
                    M('movies_comments')->where('id = ' . $movies['comments_id'])->setDec('num_reply', $childrenCount);
                }
                M('movies')->where('id =' . $movies['movies_id'])->setDec('comments_num', $childrenCount); //影片评论数-1
            } else {
                if ($id != $movies['comments_id']) {
                    M('movies_comments')->where('id = ' . $movies['comments_id'])->setInc('num_reply', $childrenCount);
                }
                M('movies')->where('id =' . $movies['movies_id'])->setInc('comments_num', $childrenCount); //影片评论数加1
            }
            if ($res) {
                $this->ajaxReturn(array('code' => 200, 'message' => ''));
            } else {
                $this->ajaxReturn(array('code' => 0, 'message' => '发生错误，请重试'));
            }
        }
    }

    public $childCount = 0;

    public function getChildrenCount($id = '') {
        $where['reply_comments_id'] = $id;
        $where['status'] = 1;
        $res = M('movies_comments')->where($where)->field('id,comments_id,comments')->select();
        if ($res) {
            foreach ($res as $value) {
                $this->childCount++;
                $this->getChildrenCount($value['id']);
            }
        }
    }

    /**
     * 获取子评论
     */
    public function getChildrenComment() {
        $id = I('get.id');
        $where['a.comments_id'] = $id;
        $index = M('movies_comments as a')->where(array('a.id' => $id))
                ->join('left join yy_user_info as i on i.user_id = a.user_id')
                ->field('a.id,a.user_id,a.to_user_id,a.movies_id,a.reply_comments_id,a.comments_id,a.comments,a.num_oo,a.num_xx,a.add_time,a.status,i.avatar,i.nick_name')
                ->find();
        $list = M('movies_comments as a')->where($where)
                ->join('left join yy_user_info as i on i.user_id = a.user_id')
                ->field('a.id,a.user_id,a.to_user_id,a.movies_id,a.reply_comments_id,a.comments_id,a.comments,a.num_oo,a.num_xx,a.add_time,a.status,i.avatar,i.nick_name')
                ->order('add_time desc')
                ->select();
        $data = array();
        $i = 0;
        foreach ($list as $value) {
            if ($value['reply_comments_id'] == $value['comments_id']) {
                $this->publicCommentArr = array();
                $data[$i] = $value;
                $data[$i]['children'] = $this->getChildren($list, $value['id']);
                $i++;
            }
        }
        $this->assign('index', $index);
        $this->assign('list', $data);
        $this->display();
    }

    /**
     * 遍历获取子评论
     * @param type $list 所有评论
     * @param type $id 父评论
     * @return type
     */
    public function getChildren($list, $id) {
        foreach ($list as $value) {
            if ($value['reply_comments_id'] == $id) {
                array_push($this->publicCommentArr, $value);
                $this->getChildren($list, $value['id']);
            }
        }
        return $this->publicCommentArr;
    }

    /**
     * 添加评论
     */
    public function add() {
        $postArr = I('post.array');
        $from = array();
        foreach ($postArr as $postarr) {
            $from[$postarr['name']] = $postarr['value'];
        }

        if ($from['comments'] == '') {
            $this->ajaxReturn(array('code' => 0, 'res' => '评论不能为空'));
        }
        $id = $from['id'];
        unset($from['id']);
        $from['user_id'] = $this->user_id_random();
        if ($id != '') {
            $mastr = M('movies_comments as a')->where(array('a.id' => $id))->find();
            $from['movies_id'] = $mastr['movies_id'];
            $from['status'] = 1;
            $from['reply_comments_id'] = $id;
            $from['to_user_id'] = $mastr['user_id'];
            $parentid = $mastr['comments_id'] != 0 ? $mastr['comments_id'] : $id;
            $from['comments_id'] = $parentid;
            $from['add_time'] = time();
            $res = M('movies_comments')->add($from);
            M('movies_comments')->where('id = ' . $parentid)->setInc('num_reply');
            M('movies')->where('id =' . $mastr['movies_id'])->setInc('comments_num'); //影片评论数加1
            if ($res) {
                $this->ajaxReturn(array('code' => 200, 'res' => '添加成功'));
            } else {
                $this->ajaxReturn(array('code' => 0, 'res' => '添加失败'));
            }
        } else {
            if ($from['movies_id'] == '') {
                $this->ajaxReturn(array('code' => 0, 'res' => '图解ID不能为空'));
            }
            $from['status'] = 1;
            $from['reply_comments_id'] = 0;
            $from['to_user_id'] = 0;
            $from['comments_id'] = 0;
            $from['add_time'] = time();
            M('movies')->where('id =' . $from['movies_id'])->setInc('comments_num'); //影片评论数加1
            $res = M('movies_comments')->add($from);
            if ($res) {
                $this->ajaxReturn(array('code' => 200, 'res' => '添加成功'));
            } else {
                $this->ajaxReturn(array('code' => 0, 'res' => '添加失败'));
            }
        }
    }

    /**
     * 随机获取用户ID
     */
    public function user_id_random() {
        $user_id_str = '11342,573915,21562959,32921077,1144137,15877,848536,2270273,1145419,456393,'
                . '2270216,573657,1172905,1217635,1169124,1169106,5011940,5011890,9496722,5248515,'
                . '5242636,15213471,19988314,26520389,15985380,23744974,27507254,28016714,25972231,'
                . '20108932,14173724,13642253,13304443,15211836,28559409,12950312,31004786,32921913,'
                . '15210977,13881911,15212349,32922082,15458680,18657191,18660031,18662379,20975669,'
                . '30425596,27758168,22484230,3330640,4637249,4637294,3861984,3863476,5718625,5126262,'
                . '12793174,12790729,12787901,18959084,58799393,18698678,20976600,23673585,25739279,'
                . '28869494,29904863,3949918,4636544,3860497,3857851,4636315,5387860,3718829,12345806,'
                . '12346359,18659713,18665827,20975443,25475090,23786409,23790563,455864,939080,848557,'
                . '32921409,32921774,837801,2940173,1286109,2928628,2015051,1994648,1995065,2015058,2015048,'
                . '2015054,10269263,32924677,24835968,17425802,24836039,24835819,31657958,23744814,22125546,'
                . '548951,1320138,947829,1831300,32921792,2263412,2701295,8630772,30098975,1133503,20403870,'
                . '32925030,32925141,32925175,32925244,31288472,16917830,16330308,15847217,20151799,16330344,'
                . '31296022,21502517,32332783,32926546,32926659,1195467,18310541,32926222,32926262,32926279,'
                . '28567018,28565949,28701053,28565182,28567174,32928084,23264185,24688545,32928393,5122850,'
                . '25709791,23634132,24401085,27509294,24188454,32929947,32930017,32930118,32930170,32930210';
        $user_id_arr = explode(',', $user_id_str);
        $user_id_count = count($user_id_arr);
        $index = rand(0, $user_id_count);
        return $user_id_arr[$index];
    }


}

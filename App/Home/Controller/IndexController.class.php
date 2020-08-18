<?php

namespace Home\Controller;

use Common\Lib\AjaxPage;
use Common\Page;

class IndexController extends CommonController
{
    private $table = 'ice_comic';

    public function index()
    {
        $this->display();
    }

    //列表页
    public function comic()
    {
        $this->display();
    }

    //详情页
    public function detail()
    {
        $id = I('id');
        $movies = M('comic_lists')->where('id = ' . $id)->find();
        $chapter = M('comic_chapter')->where('pid = ' . $movies['id'])->select();
        if ($movies['tags']) {
            $movies['tag_list'] = explode('|', $movies['tags']);
        }
        if (!$movies['cover']) {
            $movies['cover'] = '/Public/img/cover.jpg';
        }
        if (!$movies['banner']) {
//            $movies['banner'] = CDN_BOOKS . $movies['source'] . '/00001.jpg';
            $movies['banner'] = CDN_BOOKS . 'cover/' . $movies['source'] . '.jpg';
        }
        $this->assign('total_chapter', count($chapter));
        $this->assign('info', $movies);
        $this->assign('chapter', $chapter);
        $this->display();
    }

    //阅读页
    public function read()
    {
        $id = I('id');
        $sort = I('sort');

        $this->assign('id', $id);
        $this->assign('sort', $sort);
        $this->display();
    }

    //标签页面
    public function tags()
    {
        $tags = M('ice_tags')->where('count > 2')->select();

        $this->assign('tags', $tags);
        $this->display();
    }


    /**
     * 获取漫画列表
     */
    public function getMoviesData()
    {
        $getData = I('get.');
        //排序
        if ($getData['sort']) {
            $sort = array($getData['sort'] => 'desc');
        } else {
            $sort = array('title' => 'asc', 'id' => 'asc');
        }

        //标签
        if ($getData['tags']) {
            $map['name'] = array('like', '%' . $getData['tags'] . '%');
            $map['tags'] = array('like', '%' . $getData['tags'] . '%');
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }


        $where['status'] = 1;
        $count = M('comic_lists')->where($where)->count(1);

        import('Common.Lib.Page');
        $page_size = 18;
        $Page = new Page($count, $page_size);
        $list = M('comic_lists')->where($where)
            ->limit($Page->firstRow, $Page->listRows)
            ->order($sort)
            ->select();
        foreach ($list as $key => $item) {
            $list[$key]['cover'] = CDN_BOOKS . 'cover/' . $item['source'] . '.jpg';
//            $list[$key]['cover'] = CDN_BOOKS . $item['source'] . '/00001.jpg';
        }

        $returnData['page'] = $Page->show();
        $returnData['data'] = $list;
        $returnData['count'] = ceil($count / $page_size);
        $this->ajaxReturn(array('code' => 200, 'data' => $returnData));
    }

    public function like()
    {
        $this->display();
    }
    

    /**
     *
     */
    public function unlike()
    {
        $id = I('post.id');
        if ($id) {
            $res = M('comic_lists')->where('id = ' . $id)->save(array('status' => 3));
            if ($res){
                $this->ajaxReturn(array('code'=>200));
            }else{
                $this->ajaxReturn(array('code'=>0,'msg'=>'设置失败'));
            }
        }else{
            $this->ajaxReturn(array('code'=>0,'msg'=>'Id不能为空'));
        }
    }

    public function addLike()
    {
        $data = I('post.');
        $search = array(
            "https://18comic.biz/",
            "http://18comic.biz/",
            "https://www.18comic.biz/",
            "http://www.18comic.biz/",
            "album/",
            "标签： ",
            "叙述：",
            "叙述：",
            "作者： ",
            "页数：",
            "photo/",
            "Comics - 禁漫天堂",
            "\n",
            "\r",
            "\t",
            "　　",
            "?read_mode=read-by-full"
        );

        $saveData = array();
        foreach ($data['link'] as $item) {
            if (!empty($item)) {
                if (!is_numeric($item)) {
                    $str = str_replace($search, "", $item);
                    $ex = explode('/', $str);
                    $cid = $ex[0];
                } else {
                    $cid = $item;
                }

                $isExist = M('ice_achieve')->where('query_id = ' . $cid)->find();
                if (!$isExist) {
                    $saveData['query_id'] = $cid;
                    $saveData['status'] = 0;
                    $res = M('ice_achieve')->add($saveData);
                    if ($res) {
                        echo $cid . "插入成功<br/>";
                    } else {
                        echo $cid . "插入失敗<br/>";
                    }
                    echo "<a href='/home/index/add_like'>返回添加</a>";
                }

            }

        }
    }

    /**
     * 获取图片列表
     */
    public function getImg()
    {
        $id = I('id');
        $comic = M('comic_chapter')->where('source = ' . $id)->find();
        $imgLists = array();
        for ($i = 1; $i <= $comic['source_size']; $i++) {
            $page = str_pad($i, 5, "0", STR_PAD_LEFT);
            $imgLists[$i-1]['src'] = CDN_BOOKS . $comic['source'] . '/' . $page . '.jpg';
            $imgLists[$i-1]['files'] = $page . '.jpg';
        }
        $this->ajaxReturn(array('code' => 200, 'data' => $imgLists));
    }

    //下架
    public function updateStatus()
    {
        $id = I('id');
        $back = I('back');
        if ($back == '1') {
            $save['status'] = 1;
        } else {
            $save['status'] = 2;
        }

        $res = M('ice_comic')->where('comic_id = ' . $id)->save($save);
        print_r($res);
    }
}
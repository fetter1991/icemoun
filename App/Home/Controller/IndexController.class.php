<?php

namespace Home\Controller;

use Common\Page;
use QL\QueryList;

class IndexController extends CommonController
{
    private $table = 'ice_comic';
    private $server = 'https://www.18comic.life/';

    public function index()
    {
        $this->display();
    }

    public function article()
    {
        $id = I('id');
        $movies = M($this->table)->where('id = ' . $id)->find();
        if ($movies['tags']) {
            $movies['tag_list'] = explode('|', $movies['tags']);
        }
        $this->assign('info', $movies);
        $this->display();
    }


    public function comic()
    {
        $this->display();
    }

    public function comic_read()
    {
        $id = I('id');

        $this->assign('id', $id);
        $this->display();
    }

    public function getMoviesData()
    {

        $getData = I('get.');

        $where['movies_type'] = 2;
        $count = M($this->table)->where($where)->count(1);

        import('Common.Lib.Page');
        $Page = new Page($count, 15);

        $list = M($this->table)->where($where)->limit($Page->firstRow, $Page->listRows)->select();

        $returnData['page'] = $Page->show();
        $returnData['data'] = $list;
        $this->ajaxReturn(array('code' => 200, 'data' => $returnData));
    }

    public function like()
    {
        $this->display('add_like');
    }

    public function addLike()
    {
        $data = I('post.');

        $search = array(
            "https://18comic.life/",
            "http://18comic.life/",
            "https://www.18comic.life/",
            "http://www.18comic.life/",
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

                $isExist = M($this->table)->where('db_id = ' . $cid)->find();
                if (!$isExist) {
                    $saveData['link'] = 'album/' . $cid;
                    $saveData['db_id'] = $cid;
                    $saveData['status'] = 0;
                    $saveData['desc'] = 'myLike';
                    $saveData['add_time'] = time();
                    $res = M('query_search')->add($saveData);
                    if ($res) {
                        echo $cid . "插入成功<br/>";
                    } else {
                        echo $cid . "插入失敗<br/>";
                    }
                }
            }

        }
    }
}
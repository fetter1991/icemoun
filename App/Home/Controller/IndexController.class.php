<?php

namespace Home\Controller;

use Common\Page;

class IndexController extends CommonController
{
    private $table = 'movies';

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
            "album/",
            "photo/",
            "?read_mode=read-by-full"
        );
        $addData = array();
        foreach ($data['link'] as $item) {
            if ($item) {
                if (!is_numeric($item)) {
                    $str = str_replace($search, "", $item);
                    $ex = explode('/', $str);
                    $addData[]['link'] = 'albums/' . $ex[0];
                } else {
                    $addData[]['link'] = 'albums/' . $item;
                }
            }
        }
        if ($addData) {
            $res = M('query_search')->addAll($addData);
            if ($res) {
                $this->success('添加成功', '/Home/index/add_like');
            } else {
                $this->error('失败');
            }
        }
    }

    public function getImg()
    {
        $id = I('id');
        $comic = M($this->table)->where('id = ' . $id)->find();
        $dir = BOOK . $comic['name'] . '/';
        $path = iconv("utf-8", "gbk", $dir);
        $temp = scandir($path);
        unset($temp[0]);
        unset($temp[1]);
        foreach ($temp as $key => $item) {
            $temp[$key] = '/Public/book/' . trim($comic['name']) . '/' . $item;
        }
        $this->ajaxReturn(array('code' => 200, 'data' => $temp));
    }

    /**
     * 遍历文件夹
     *
     * @param $files
     */
    private function list_file($files)
    {
        //1、首先先读取文件夹
        $temp = scandir($files);
        //遍历文件夹
        foreach ($temp as $v) {
            $a = $files . '/' . $v;
            //如果是文件夹则执行
            if (is_dir($a)) {
                //判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
                if ($v == '.' || $v == '..') {
                    continue;
                }
                //把文件夹红名输出
                //echo "<font color='red'>$a</font>", "<br/>";

                //因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
                $this->list_file($a);
            } else {
                echo $a, "<br/>";
            }
        }
    }
}
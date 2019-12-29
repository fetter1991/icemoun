<?php

/**
 * 求片功能
 * @time         2019-5-27
 * @author       tsj
 * @version     1.0
 */

namespace Back\Controller;

use Common\Lib\Douban;

class SeekMoviesController extends CommonController
{

    public function __construct()
    {
        parent::__construct();
        import('Common.Lib.Page');
    }

    /**
     * 求片列表
     */
    public function index()
    {
        $count = M('query_search')->where('status = 2')->count('1');
        $page = new \Common\Page($count, 15);
        $list = M('query_search')->where('status = 2')->limit($page->firstRow, $page->listRows)->order('id desc')->select();
        foreach ($list as $key => $item) {
            $list[$key]['c_id'] = str_replace('album/', '', $item['link']);
        }
        $this->assign('list', $list);
        $this->assign('page', $page->show());
        $this->display();
    }

//    public function updateLink()
//    {
//        $list = M('query_search')->where('status = 2')->select();
//
//        foreach ($list as $item) {
//            $data = array();
//            $id = str_replace('album/', '', $item['link']);
//            $data['cover'] = 'https://cdn-ms.18comic.life/media/albums/' . $id . '.jpg';
//            $res = M('query_search')->where('id = ' . $item['id'])->save($data);
//
//        }
//    }
}

<?php

/**
 * 图解管理
 *
 *
 * @author      tsj 作者
 * @version     2.0 版本号
 */

namespace Back\Controller;

use AppAdmin\Controller\PushSettingController;
use Exception;
use Common\Lib\AjaxPage;

class ComicController extends CommonController
{
    private $table = 'ice_comic';

    public function __construct()
    {
        parent::__construct();
        import('Common.Lib.Page');
    }

    /**
     * 入口
     */
    public function index()
    {
        $keyword = I('keyword');
        $status = I('status');
        $order = I('order');
        $sort = I('sort');
        $where = array();
        if ($keyword && is_numeric($keyword)) {
            $map['comic_id'] = array('like', '%' . $keyword . '%');
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        } elseif ($keyword && !is_numeric($keyword)) {
            $map['name'] = array('like', '%' . $keyword . '%');
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
        //状态选择
        if (is_numeric($status)) {
            $where['status'] = $status;
        }

        $count = M($this->table)->where($where)->count(1);
        $page = new \Common\Page($count, 15);
        $list = M($this->table)->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order(array('comic_id' => 'asc'))
            ->select();

        $this->assign('list', $list);
        $this->assign('status', $status);
        $this->assign('order', $order);
        $this->assign('sort', $sort);
        $this->assign('keyword', $keyword);
        $this->assign('page', $page->show());
        $this->display();
    }
}

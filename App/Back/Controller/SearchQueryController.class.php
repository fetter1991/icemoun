<?php

namespace Back\Controller;

use QL\QueryList;

class SearchQueryController extends CommonController
{
    private $table = 'comic_download';

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
        if ($keyword && is_numeric($keyword)) {
            $map['db_id'] = array('like', '%' . $keyword . '%');
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

        $where['status'] = 0;


        $count = M($this->table)->where($where)->count('1');
        $page = new \Common\Page($count, 15);
        $list = M($this->table)->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order(array('page'=>'asc'))
            ->select();

        $this->assign('list', $list);
        $this->assign('status', $status);
        $this->assign('order', $order);
        $this->assign('sort', $sort);
        $this->assign('keyword', $keyword);
        $this->assign('page', $page->show());
        $this->display();
    }

    /**
     * 关键词采集
     */
    public function searchQuery()
    {
        $postData = I('post.');
        $keyword = $postData['keyword'];
        $page = $postData['page'] ? $postData['page'] : 1;
        $url = $this->serverQuery . $keyword . '&page=' . $page;

        $result = $this->httpRequest($url, '', '');

        $pattern = "<img data-original=\".*img-responsive \" />";
        preg_match_all($pattern, $result, $matches);

        foreach ($matches[0] as $key => $item) {
            $link_rule = '#albums/\d+#';
            preg_match($link_rule, $item, $link_match);

            $title_rule = '#title=".*" alt#';
            preg_match($title_rule, $item, $title_match);

            $search = array(
                'albums/',
                '/albums/',
                'title="',
                '" alt',
                "\n",
                "\r",
                "\t",
                "　　",
            );

            $val['link'] = str_replace($search, "", $link_match[0]);
            $val['name'] = str_replace($search, "", $title_match[0]);
            $addData[] = $val;
        }

        $res = M($this->table)->addAll($addData);
        if ($res) {
            $this->success('成功', '/Back/SearchQuery/index');
        } else {
            $this->error('失败');
        }
    }

    //开始采集数据
    public function startGet()
    {
        $info = M($this->table)->where('status = 0')->find();

        $isExist = M('ice_comic')->where('db_id = ' . $info['db_id'])->find();

        $status['status'] = 1;
        if (!$isExist) {
            $Query = new GetComicController();
            $result = $Query->getComic($info['db_id']);
            if ($result) {
                $save = M($this->table)->where('id = ' . $info['id'])->save($result);
                if ($save) {
                    $this->ajaxReturn(array('code' => 200, 'msg' => '', 'id' => $info['id']));
                }
            }
        } else {
            //数据存在，更新
            M($this->table)->where('id = ' . $info['id'])->save($status);
            $this->ajaxReturn(array('code' => 200, 'msg' => '数据已存在', 'id' => $info['id']));
        }
    }

    /**
     * 更新页数
     */
    public function updatePage()
    {
        $id = I('id');
        $Query = new GetComicController();
        $data = $Query->getPage($id);
        if ($data) {
            $page = M('query_search')->where('query_id = ' . $id)->getField('total_page');
            if ($page != $data['total_page']) {
                M('query_search')->where('query_id = ' . $id)->save(array('total_page' => $data['total_page']));
            }
            $this->ajaxReturn(array('code' => 200));
        }

    }

    /**
     * 获取信息
     */
    public function getInfo()
    {
        $db_id = I('id');
        $info = M($this->table)->where('did = ' . $db_id)->find();
        $str = '';
        for ($i = 1; $i <= $info['page']; $i++) {
            $p = str_pad($i, 5, "0", STR_PAD_LEFT);
            $str .= C('COMIC_SERVER.CDN') . $info['did'] . '/' . $p . ".jpg\n";
        }
        $info['str'] = $str;

        if ($info) {
            $this->ajaxReturn(array('code' => 200, 'data' => $info));
        } else {
            $this->ajaxReturn(array('code' => 0, 'data' => array()));
        }
    }

    /**
     * 编辑
     */
    public function doEdit()
    {
        $data = I('post.');

        $saveData['status'] = 1;

        $res = M('comic_download')->where('did = ' . $data['did'])->save($saveData);
        if ($res) {
            $this->ajaxReturn(array('code' => 200, 'msg' => '保存成功'));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '修改失败'));
        }
    }

    /**
     * curl post
     *
     * @param $sUrl
     * @param $aHeader
     * @param $aData
     *
     * @return bool|string
     */
    public function httpRequest($sUrl, $aHeader, $aData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aData));
        $sResult = curl_exec($ch);
        if ($sError = curl_error($ch)) {
            die($sError);
        }
        curl_close($ch);

        return $sResult;
    }
}
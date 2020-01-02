<?php

namespace Back\Controller;

use QL\QueryList;

class SearchQueryController extends CommonController
{
    private $server = 'http://www.18comic.life/';

    public function __construct()
    {
        parent::__construct();
        import('Common.Lib.Page');
    }

    public function index()
    {
        $keyword = I('keyword');
        if ($keyword && is_numeric($keyword)) {
            $map['link'] = array('like', '%' . $keyword . '%');
            $map['db_id'] = array('like', '%' . $keyword . '%');
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        } elseif ($keyword && !is_numeric($keyword)) {
            $map['name'] = array('like', '%' . $keyword . '%');
            $map['_logic'] = 'or';
            $where['_complex'] = $map;
        }
        $where['status'] = array('neq', 3);

        $count = M('query_search')->where($where)->count('1');
        $page = new \Common\Page($count, 15);
        $list = M('query_search')->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order(array('add_time' => 'desc'))
            ->select();
        foreach ($list as $key => $item) {
            $path = iconv("utf-8", "gbk", $item['name']);
            $res = file_exists(__BOOKS__ . $path);
            if ($res) {
                $list[$key]['cover'] = '/Books/' . $item['name'] . '/00001.jpg';
            } else {
                $list[$key]['cover'] = '';
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $page->show());
        $this->display();
    }

    public function searchQuery()
    {
        $postData = I('post.');
        $keyword = $postData['keyword'];
        $page = $postData['page'] ? $postData['page'] : 1;
        $url = 'https://18comic.life/search/photos?search_query=' . $keyword . '&page=' . $page;

        $result = $this->httpRequest($url);

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

        $res = M('query_search')->addAll($addData);
        if ($res) {
            $this->success('成功', '/Back/SearchQuery/index');
        } else {
            $this->error('失败');
        }
    }

    public function startGet()
    {
        $info = M('query_search')->where('status = 0')->find();

        $isExist = M('ice_comic')->where('db_id = ' . $info['db_id'])->find();

        $status['status'] = 1;
        if (!$isExist) {
            $Query = new GetComicController();
            $result = $Query->getComic($info['db_id']);
            if ($result) {
                $save = M('query_search')->where('id = ' . $info['id'])->save($result);
                if ($save) {
                    $this->ajaxReturn(array('code' => 200, 'msg' => '', 'id' => $info['id']));
                }
            }
        } else {
            //数据存在，更新
            M('query_search')->where('id = ' . $info['id'])->save($status);
            $this->ajaxReturn(array('code' => 200, 'msg' => '数据已存在', 'id' => $info['id']));
        }
    }


    public function getInfo()
    {
        $id = I('id');
        $info = M('query_search')->where('id = ' . $id)->find();
        $str = '';
        for ($i = 1; $i <= $info['total_page']; $i++) {
            $p = str_pad($i, 5, "0", STR_PAD_LEFT);
            $str .= "https://cdn-ms.18comic.life/media/photos/" . $info['db_id'] . '/' . $p . ".jpg\n";
        }
        $info['str'] = $str;

        if ($info) {
            $this->ajaxReturn(array('code' => 200, 'data' => $info));
        } else {
            $this->ajaxReturn(array('code' => 0, 'data' => array()));
        }
    }

    public function doEdit()
    {
        $data = I('post.');
        $saveData['name'] = trim($data['name']);
        $saveData['author'] = trim($data['author']);
        $saveData['org_name'] = trim($data['org_name']);
        $saveData['link'] = 'album/'.$data['db_id'];
        $saveData['tags'] = trim($data['tags']);
        $saveData['db_id'] = intval($data['db_id']);
        $saveData['total_page'] = intval($data['total_page']);
        $saveData['desc'] = trim($data['desc']);
        $saveData['add_time'] = time();

        $path = iconv("utf-8", "gbk", $saveData['org_name']);
        $isExist = file_exists(__BOOKS__ . $path);

        if ($isExist) {
            $saveData['status'] = 3;
        }

        $res = M('ice_comic')->add($saveData);
        if ($res) {
            $status['status'] = 3;
            $res = M('query_search')->where('db_id = '.$data['db_id'])->save($status);
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
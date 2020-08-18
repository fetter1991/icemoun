<?php


namespace Home\Controller;

use Think\Controller;
use QL\QueryList;

/**
 * 采集数据
 *
 * Class GetComicController
 * @package Back\Controller
 */
class GetComicController extends Controller
{
    private $server = 'https://www.18comic.biz/';
    private $album_server = 'https://18comic.biz/album/';
    private $photo_server = 'https://18comic.biz/photo/';
    private $search = array(
        "https://18comic.biz/",
        "http://18comic.biz/",
        "https://www.18comic.biz/",
        "http://www.18comic.biz/",
        "http://www.18comic.biz/",
        "album/",
        "标签： ",
        "叙述：",
        "叙述：",
        "作者： ",
        "页数：",
        "photo/",
        "Comics - 禁漫天堂",
        "观看次数",
        "\n",
        "\r",
        "\t",
        "　　",
        "?read_mode=read-by-full"
    );

    public function index()
    {
        phpinfo();
    }

    public function getComicData($id)
    {
        $url    = $this->album_server . $id;
        $search = $this->search;
        //采集规则
        $rules = [
            //标题
            'title' => ['title', 'text'],
            //链接
            'link' => ['link:eq(2)', 'href'],
            //作者
            'author' => ['.tag-block:eq(3)', 'text', 'a'],
            //图片数量
            'total_page' => ['.p-t-5:eq(1)', 'text'],
            //简介
            'summary' => ['.p-t-5:eq(0)', 'text'],
            //标签
            'tags' => ['.tag-block:eq(2)', 'text', 'a'],
            //阅读
            'hits' => ['.p-t-5:eq(2)>span:eq(1)', 'text'],
            //点赞
            'like_num' => ['#albim_likes_2094', 'text'],
            //
            'list' => ['.episode', 'html'],
        ];

        $result = QueryList::Query($url, $rules)->getData(function ($item) {
            $item['list'] = QueryList::Query($item['list'], array(
                'href' => array('a', 'href'),
                'title' => array('li', 'text', '-span')
            ))->data;
            return $item;
        });
        if ($result) {
            $data = $result[0];

            $author     = preg_replace('/<a.*">/', '', $data['author']);
            $author     = preg_replace('/<\/a>/', '|', $author);
            $author     = str_replace($search, "", $author);
            $tags       = preg_replace('/<a.*">/', '', $data['tags']);
            $tags       = preg_replace('/<\/a>/', '|', $tags);
            $tags       = str_replace($search, "", $tags);
            $title      = str_replace($search, '', $data['title']);
            $summary    = str_replace($search, '', $data['summary']);
            $total_page = intval(str_replace($search, '', $data['total_page']));
            $link       = str_replace($search, '', $data['link']);
            $hits       = str_replace($search, '', $data['hits']);
            $like_num   = str_replace($search, '', $data['like_num']);

            if ($data['list']) {
                foreach ($data['list'] as $key => $item) {
                    $ComicData[$key]['name'] = str_replace($search, ' ', $item['title']);;
                    $ComicData[$key]['query_id']    = intval(str_replace('/photo/', '', $item['href']));
                    $ComicData[$key]['author']      = $author ? $author : '-';
                    $ComicData[$key]['tags']        = $tags ? $tags : '-';
                    $ComicData[$key]['total_page']  = intval($total_page);
                    $ComicData[$key]['editor_note'] = $summary ? $summary : '-';;
                    $ComicData[$key]['desc']     = '初次获取数据';
                    $ComicData[$key]['status']   = 0;
                    $ComicData[$key]['add_time'] = time();
                }
            } else {
                $linkId                   = explode('/', $link);
                $ComicData['query_id']    = intval($linkId[0]);
                $ComicData['name']        = $title;
                $ComicData['author']      = $author;
                $ComicData['tags']        = $tags;
                $ComicData['total_page']  = intval($total_page);
                $ComicData['editor_note'] = $summary;
                $ComicData['desc']        = '初次获取数据';
                $ComicData['status']      = 0;
                $ComicData['add_time']    = time();
            }

            return array(
                'code' => 200,
                'data' => $ComicData
            );
        } else {
            return array(
                'code' => 0,
                'data' => ''
            );
        }
    }

    /**
     * 抓取数据
     *
     * @param $id
     *
     * @return mixed
     */
    public function getComic($id)
    {
        $url    = $this->album_server . $id;
        $search = $this->search;
        //采集规则
        $rules  = [
            //标题
            'title' => ['title', 'text'],
            //链接
            'link' => ['link:eq(2)', 'href'],
            //作者
            'author' => ['.tag-block:eq(3)', 'text', 'a'],
            //图片数量
            'total_page' => ['.p-t-5:eq(1)', 'text'],
            //简介
            'summary' => ['.p-t-5:eq(0)', 'text'],
            //标签
            'tags' => ['.tag-block:eq(2)', 'text', 'a'],
            //阅读
            'hits' => ['.p-t-5:eq(2)>span:eq(1)', 'text'],
            //点赞
            'like_num' => ['#albim_likes_2094', 'text'],
        ];
        $result = QueryList::Query($url, $rules)->data;
        if ($result) {
            $data       = $result[0];
            $author     = preg_replace('/<a.*">/', '', $data['author']);
            $author     = preg_replace('/<\/a>/', '|', $author);
            $author     = str_replace($search, "", $author);
            $tags       = preg_replace('/<a.*">/', '', $data['tags']);
            $tags       = preg_replace('/<\/a>/', '|', $tags);
            $tags       = str_replace($search, "", $tags);
            $title      = str_replace($search, '', $data['title']);
            $summary    = str_replace($search, '', $data['summary']);
            $total_page = intval(str_replace($search, '', $data['total_page']));
            $link       = str_replace($search, '', $data['link']);
            $hits       = str_replace($search, '', $data['hits']);
            $like_num   = str_replace($search, '', $data['like_num']);

            $linkId                   = explode('/', $link);
            $ComicData['name']        = $title;
            $ComicData['org_name']    = $title;
            $ComicData['author']      = $author;
            $ComicData['tags']        = $tags;
            $ComicData['link']        = 'album/' . $linkId[0];
            $ComicData['total_page']  = intval($total_page);
            $ComicData['editor_note'] = $summary;
            $ComicData['desc']        = '初次获取数据';
            $ComicData['query_id']    = intval($linkId[0]);
            $ComicData['status']      = 0;
            $ComicData['hits']        = intval($hits);
            $ComicData['like_num']    = intval($like_num);
            $ComicData['add_time']    = time();
            $ComicData['banner']      = 'https://cdn-ms.18comic.biz/media/albums/' . $linkId[0] . '.jpg';
            $ComicData['cover']       = 'https://cdn-ms.18comic.biz/media/photos/' . $linkId[0] . '/00001.jpg';

            return array(
                'code' => 200,
                'data' => $ComicData
            );
        } else {
            return array(
                'code' => 0,
                'data' => ''
            );
        }
    }

    //获取页数
    public function getPage($id)
    {
        $url    = $this->photo_server . $id;
        $search = array('|H漫內頁瀏覽 Comics - 禁漫天堂', "\n", "\r", "\t", "　　", " ");
        //采集规则
        $rules  = [
            //标题
            'title' => ['title', 'text'],
            //图片数量
            'total_page' => ['#page_0', 'text'],
        ];
        $result = QueryList::Query($url, $rules)->data;

        if ($result) {
            $data = $result[0];

            $title      = str_replace($search, '', $data['title']);
            $total_page = str_replace($search, '', $data['total_page']);
            $total_page = str_replace('1/', '', $total_page);

            return array(
                'name' => $title,
                'total_page' => $total_page,
            );
        } else {
            return array();
        }
    }
}
<?php


namespace Back\Controller;

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
    private $search = array(
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
        " ",
        "?read_mode=read-by-full"
    );

    public function index()
    {
        phpinfo();
    }

    /**
     * 获取数据包括列表
     * @param $id
     * @return array
     */
    public function getComicData($id)
    {
        $url = C('COMIC_SERVER.ALBUM') . $id;
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

            $author = preg_replace('/<a.*">/', '', $data['author']);
            $author = preg_replace('/<\/a>/', '|', $author);
            $author = str_replace($search, "", $author);
            $tags = preg_replace('/<a.*">/', '', $data['tags']);
            $tags = preg_replace('/<\/a>/', '|', $tags);
            $tags = str_replace($search, "", $tags);
            $title = str_replace($search, '', $data['title']);
            $summary = str_replace($search, '', $data['summary']);
            $total_page = intval(str_replace($search, '', $data['total_page']));

            if ($data['list']) {
                $ComicData['source'] = $id;
                $ComicData['name'] = $title;
                $ComicData['author'] = $author ? $author : '-';
                $ComicData['tags'] = $tags ? $tags : '-';
                $ComicData['editor_note'] = $summary ? $summary : '-';;
                $ComicData['desc'] = '初次获取数据';
                $ComicData['status'] = 0;
                $ComicData['add_time'] = time();
                foreach ($data['list'] as $key => $item) {
                    $ComicData['list'][$key]['title'] = str_replace($search, ' ', $item['title']);;
                    $ComicData['list'][$key]['source'] = intval(str_replace('/photo/', '', $item['href']));
                }
            } else {
                $ComicData['source'] = $id;
                $ComicData['name'] = $title;
                $ComicData['author'] = $author;
                $ComicData['tags'] = $tags;
                $ComicData['total_page'] = intval($total_page);
                $ComicData['editor_note'] = $summary;
                $ComicData['desc'] = '初次获取数据';
                $ComicData['status'] = 0;
                $ComicData['add_time'] = time();
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

    //获取页数
    public function getPage($id)
    {
        $url = C('COMIC_SERVER.PHOTO') . $id;
        $search = array('|H漫內頁瀏覽 Comics - 禁漫天堂', "\n", "\r", "\t", "　　", " ");
        //采集规则
        $rules = [
            //标题
            'title' => ['title', 'text'],
            //图片数量
            'total_page' => ['#page_0', 'text'],
        ];
        $result = QueryList::Query($url, $rules)->data;

        if ($result) {
            $data = $result[0];

            $title = str_replace($search, '', $data['title']);
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

    //获取图片链接
    public function getImgLink($id)
    {
        $url = C('COMIC_SERVER.PHOTO') . $id;
        //采集规则
        $rules = [
            'total_page' => ['.img-responsive-mw', 'id'],
        ];
        $range = '';
        $result = QueryList::Query($url, $rules)->data;
        if ($result) {
            return array('code' => 200, 'data' => $result);
        } else {
            return array('code' => 0, 'data' => '');
        }
    }


}
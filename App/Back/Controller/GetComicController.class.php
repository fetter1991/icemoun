<?php


namespace Back\Controller;

use Think\Controller;
use QL\QueryList;

/**
 * 单条采集数据
 *
 * Class GetComicController
 * @package Back\Controller
 */
class GetComicController extends Controller
{
    private $server = 'https://www.18comic.life/';
    private $album_server = 'https://www.18comic.life/album/';
    private $search = array(
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

    public function index()
    {
        phpinfo();
    }

    public function getComic($id)
    {
        $url = $this->album_server . $id;
        $search = $this->search;
        //采集规则
        $rules = [
            // 小说标题
            'title' => ['title', 'text'],
            //链接
            'link' => ['link:eq(2)', 'href'],
            // 小说作者
            'author' => ['.tag-block:eq(3)', 'text', 'a'],
            // 页数
            'total_page' => ['.p-t-5:eq(1)', 'text'],
            //简介
            'summary' => ['.p-t-5:eq(0)', 'text'],
            //标签
            'tags' => ['.tag-block:eq(2)', 'text', 'a']
        ];
        $result = QueryList::Query($url, $rules)->data;

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
            $link = str_replace($search, '', $data['link']);

            $linkId = explode('/', $link);
            $saveData['name'] = $title;
            $saveData['org_name'] = $title;
            $saveData['author'] = $author;
            $saveData['tags'] = $tags;
            $saveData['link'] = 'album/' . $linkId[0];
            $saveData['total_page'] = $total_page;
            $saveData['editor_note'] = $summary;
            $saveData['desc'] = '数据更新';
            $saveData['db_id'] = $linkId[0];
            $saveData['status'] = 1;
            $saveData['add_time'] = time();
            $saveData['banner'] = 'https://cdn-ms.18comic.life/media/albums/' . $linkId[0] . '.jpg';
            $saveData['cover'] = 'https://cdn-ms.18comic.life/media/photos/' . $linkId[0] . '/00001.jpg';

            return $saveData;
        }
    }
}
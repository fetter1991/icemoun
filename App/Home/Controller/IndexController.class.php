<?php

namespace Home\Controller;

use Common\Page;
use QL\QueryList;

class IndexController extends CommonController {
	private $table = 'movies';
	private $server = 'https://www.18comic.life/';

	public function index() {
		$this->display();
	}

	public function article() {
		$id     = I( 'id' );
		$movies = M( $this->table )->where( 'id = ' . $id )->find();
		if ( $movies['tags'] ) {
			$movies['tag_list'] = explode( '|', $movies['tags'] );
		}
		$this->assign( 'info', $movies );
		$this->display();
	}


	public function comic() {
		$this->display();
	}

	public function comic_read() {
		$id = I( 'id' );

		$this->assign( 'id', $id );
		$this->display();
	}

	public function getMoviesData() {

		$getData = I( 'get.' );

		$where['movies_type'] = 2;
		$count                = M( $this->table )->where( $where )->count( 1 );

		import( 'Common.Lib.Page' );
		$Page = new Page( $count, 15 );

		$list = M( $this->table )->where( $where )->limit( $Page->firstRow, $Page->listRows )->select();

		$returnData['page'] = $Page->show();
		$returnData['data'] = $list;
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	public function like() {
		$this->display( 'add_like' );
	}

	public function addLike() {
		$data = I( 'post.' );

		$search   = array(
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
		foreach ( $data['link'] as $item ) {
			if ( ! empty( $item ) ) {
				if ( ! is_numeric( $item ) ) {
					$str = str_replace( $search, "", $item );
					$ex  = explode( '/', $str );
					$cid = $ex[0];
				} else {
					$cid = $item;
				}

				$isExist = M( 'ice_comic_copy' )->where( 'db_id = ' . $cid )->find();
				if ( ! $isExist ) {
					$link = 'album/' . $cid;
					$url  = $this->server . $link;

					// 采集规则
					$rules = [
						// 小说标题
						'title'   => [ 'title', 'text' ],
						//链接
						'link'    => [ 'link:eq(2)', 'href' ],
						// 小说作者
						'author'  => [ '.tag-block:eq(3)', 'text', 'a' ],
						// 页数
						'maxpage' => [ '.p-t-5:eq(1)', 'text' ],
						//简介
						'summary' => [ '.p-t-5:eq(0)', 'text' ],
						//tag
						'tags'    => [ '.tag-block:eq(2)', 'text', 'a' ]
					];

					$result = QueryList::Query( $url, $rules )->data;
					if ( $result ) {
						$data    = $result[0];
						$author  = preg_replace( '/<a.*">/', '', $data['author'] );
						$author  = preg_replace( '/<\/a>/', '|', $author );
						$author  = str_replace( $search, "", $author );
						$tags    = preg_replace( '/<a.*">/', '', $data['tags'] );
						$tags    = preg_replace( '/<\/a>/', '|', $tags );
						$tags    = str_replace( $search, "", $tags );
						$title   = str_replace( $search, '', $data['title'] );
						$summary = str_replace( $search, '', $data['summary'] );
						$maxpage = intval( str_replace( $search, '', $data['maxpage'] ) );
						$link    = str_replace( $search, '', $data['link'] );

						$linkId                 = explode( '/', $link );
						$saveData['name']       = $title;
						$saveData['org_name']   = $title;
						$saveData['author']     = $author;
						$saveData['tags']       = $tags;
						$saveData['link']       = 'album/' . $linkId[0];
						$saveData['total_page'] = $maxpage;
						$saveData['desc']       = $summary;
						$saveData['db_id']      = $linkId[0];
						$saveData['status']     = 1;
						$saveData['add_time']   = time();
						$saveData['banner']     = 'https://cdn-ms.18comic.life/media/albums/' . $cid . '.jpg';
						$saveData['cover']      = 'https://cdn-ms.18comic.life/media/photos/' . $cid . '/00001.jpg';

						$res = M( 'ice_comic_copy' )->add( $saveData );
					}
					if ( $res ) {
						$this->success( '成功', '/Home/Index/like' );
					} else {
						$this->error( '失败' );
					}
				}
			}
		}
	}

	/**
	 * 遍历文件夹
	 *
	 * @param $files
	 */
	private function list_file( $files ) {
		//1、首先先读取文件夹
		$temp = scandir( $files );
		//遍历文件夹
		foreach ( $temp as $v ) {
			$a = $files . '/' . $v;
			//如果是文件夹则执行
			if ( is_dir( $a ) ) {
				//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
				if ( $v == '.' || $v == '..' ) {
					continue;
				}
				//把文件夹红名输出
				//echo "<font color='red'>$a</font>", "<br/>";

				//因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
				$this->list_file( $a );
			} else {
				echo $a, "<br/>";
			}
		}
	}
}
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
			"album/",
			"photo/",
			"?read_mode=read-by-full"
		);
		$saveData = array();
		foreach ( $data['link'] as $item ) {
			if ( $item ) {
				if ( ! is_numeric( $item ) ) {
					$str = str_replace( $search, "", $item );
					$ex  = explode( '/', $str );
					$cid = $ex[0];
				} else {
					$cid = $item;
				}
			}
		}
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

		$res    = QueryList::Query( $url, $rules )->data;
		$search = array( "\n", "\r", "\t", "　　" );
		print_r( $res );
		if ( $res ) {
			$data    = $res[0];
			$author  = preg_replace( '/<a.*">/', '', $data['author'] );
			$author  = preg_replace( '/<\/a>/', '|', $author );
			$author  = str_replace( $search, "", $author );
			$tags    = preg_replace( '/<a.*">/', '', $data['tags'] );
			$tags    = preg_replace( '/<\/a>/', '|', $tags );
			$tags    = str_replace( '标签： ', "", $tags );
			$title   = str_replace( 'Comics - 禁漫天堂', '', $data['title'] );
			$summary = str_replace( '叙述：', '', $data['summary'] );
			$maxpage = intval( str_replace( '页数：', '', $data['maxpage'] ) );
			$link    = str_replace( 'https://18comic.org', '', $data['link'] );

			$saveData['name']       = $title;
			$saveData['org_name']   = $title;
			$saveData['author']     = $author;
			$saveData['tags']       = $tags;
			$saveData['link']       = $link;
			$saveData['total_page'] = $maxpage;
			$saveData['desc']       = $summary;
			$saveData['db_id']      = $cid;
			$saveData['status']     = 1;
			$saveData['add_time']   = time();
			$saveData['banner']     = 'https://cdn-ms.18comic.life/media/albums/' . $cid . '.jpg';
			$saveData['cover']      = 'https://cdn-ms.18comic.life/media/photos/' . $cid . '/00001.jpg';


			$res = M( 'ice_comic_copy' )->add( $saveData );
			if ( $res ) {
				$this->success( '添加成功', '/Home/Index/like' );
			} else {
				$this->error( '添加失败' );
			}
		}

	}

	public function getImg() {
		$id    = I( 'id' );
		$comic = M( $this->table )->where( 'id = ' . $id )->find();
		$dir   = BOOK . $comic['name'] . '/';
		$path  = iconv( "utf-8", "gbk", $dir );
		$temp  = scandir( $path );
		unset( $temp[0] );
		unset( $temp[1] );
		foreach ( $temp as $key => $item ) {
			$temp[ $key ] = '/Public/book/' . trim( $comic['name'] ) . '/' . $item;
		}
		$this->ajaxReturn( array( 'code' => 200, 'data' => $temp ) );
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
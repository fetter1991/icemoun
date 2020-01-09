<?php

namespace Home\Controller;

use Common\Lib\AjaxPage;
use Common\Page;

class IndexController extends CommonController {
	private $table = 'ice_comic' . __COPY__;

	public function index() {
		$this->display();
	}

	//列表页
	public function comic() {
		$this->display();
	}

	//详情页
	public function detail() {
		$id      = I( 'id' );
		$movies  = M( $this->table )->where( 'db_id = ' . $id )->find();
		$chapter = M( 'ice_comic_chapter' )->where( 'movies_id = ' . $movies['db_id'] )->select();
		if ( $movies['tags'] ) {
			$movies['tag_list'] = explode( '|', $movies['tags'] );
		}
		if ( ! $movies['cover'] ) {
			$movies['cover'] = '/Public/img/cover.jpg';
		}
		if ( ! $movies['banner'] ) {
			$movies['banner'] = '/Public/img/banner.jpg';
		}
		$this->assign( 'total_chapter', count( $chapter ) );
		$this->assign( 'info', $movies );
		$this->assign( 'chapter', $chapter );
		$this->display();
	}

	//阅读页
	public function read() {
		$id   = I( 'id' );
		$sort = I( 'sort' );

		$this->assign( 'id', $id );
		$this->assign( 'sort', $sort );
		$this->display();
	}

	public function getMoviesData() {
		$getData = I( 'get.' );

		$where['status'] = 3;
		$count           = M( $this->table )->where( $where )->count( 1 );

		import( 'Common.Lib.Page' );

		$page_size = 6;
		$Page      = new Page( $count, $page_size );
		$list      = M( $this->table )->where( $where )
		                              ->limit( $Page->firstRow, $Page->listRows )
		                              ->order( array( 'add_time' => 'desc', 'id' => 'desc' ) )
		                              ->select();
		foreach ( $list as $key => $item ) {
			$list[ $key ]['cover'] = CDN_BOOKS . $item['db_id'] . '/00001.jpg';
		}

		$returnData['page']  = $Page->show();
		$returnData['data']  = $list;
		$returnData['count'] = ceil( $count / $page_size );
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	public function like() {
		$this->display();
	}

	public function addLike() {
		$data = I( 'post.' );

		$search = array(
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

				$isExist = M( $this->table )->where( 'db_id = ' . $cid )->find();
				if ( ! $isExist ) {
					$saveData['link']     = 'album/' . $cid;
					$saveData['db_id']    = $cid;
					$saveData['status']   = 0;
					$saveData['desc']     = 'myLike';
					$saveData['add_time'] = time();
					$res                  = M( 'ice_comic_query' )->add( $saveData );
					if ( $res ) {
						echo $cid . "插入成功<br/>";
					} else {
						echo $cid . "插入失敗<br/>";
					}
				}
			}

		}
	}

	public function getImg() {
		$id   = I( 'id' );
		$sort = I( 'sort' );

		$comic = M( $this->table )->where( 'db_id = ' . $id )->find();
		if ( ! $sort ) {
			for ( $i = 1; $i <= $comic['total_page']; $i ++ ) {
				$page   = str_pad( $i, 5, "0", STR_PAD_LEFT );
				$temp[] = CDN_BOOKS . $comic['db_id'] . '/' . $page . '.jpg';
			}
		} else {
			for ( $i = 1; $i <= $comic['total_page']; $i ++ ) {
				$page   = str_pad( $i, 5, "0", STR_PAD_LEFT );
				$temp[] = CDN_BOOKS . $comic['db_id'] . '/' . $sort . '/' . $page . '.jpg';
			}
		}
		$this->ajaxReturn( array( 'code' => 200, 'data' => $temp ) );
	}


}
<?php

/**
 * 求片功能
 * @time         2019-5-27
 * @author       tsj
 * @version     1.0
 */

namespace Back\Controller;

use QL\QueryList;

class SeekMoviesController extends CommonController {
	private $table = 'ice_comic_copy';
	private $server = 'https://www.18comic.life/';

	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	/**
	 * 求片列表
	 */
	public function index() {
		$count = M( $this->table )->where( 'status = 1' )->count( '1' );
		$page  = new \Common\Page( $count, 15 );
		$list  = M( $this->table )->where( 'status = 1' )
		                          ->limit( $page->firstRow, $page->listRows )
		                          ->order( 'id desc' )
		                          ->select();
		foreach ( $list as $key => $item ) {
			$path                    = iconv( "utf-8", "gbk", $item['name'] );
			$res                     = file_exists( __BOOKS__ . $path );
			$list[ $key ]['isExist'] = $res ? 1 : 0;
		}

		$this->assign( 'list', $list );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	public function getInfo() {
		$id   = I( 'id' );
		$info = M( $this->table )->where( 'id = ' . $id )->find();
		$str  = '';
		for ( $i = 1; $i <= $info['total_page']; $i ++ ) {
			$p   = str_pad( $i, 5, "0", STR_PAD_LEFT );
			$str .= "https://cdn-ms.18comic.life/media/photos/" . $info['db_id'] . '/' . $p . ".jpg\n";
		}
		$info['str'] = $str;

		if ( $info ) {
			$this->ajaxReturn( array( 'code' => 200, 'data' => $info ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'data' => array() ) );
		}
	}

}

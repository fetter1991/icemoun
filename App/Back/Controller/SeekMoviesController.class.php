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
	private $table = 'ice_comic' . __COPY__;

	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	/**
	 * 求片列表
	 */
	public function index() {
		$keyword = I( 'keyword' );
		if ( $keyword && is_numeric( $keyword ) ) {
			$map['link']       = array( 'like', '%' . $keyword . '%' );
			$map['db_id']      = array( 'like', '%' . $keyword . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
		} elseif ( $keyword && ! is_numeric( $keyword ) ) {
			$map['name']       = array( 'like', '%' . $keyword . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
		}
		$where['status'] = array( 'neq', 2 );

		$count = M( $this->table )->where( $where )->count( '1' );
		$page  = new \Common\Page( $count, 15 );
		$list  = M( $this->table )->where( $where )
		                          ->limit( $page->firstRow, $page->listRows )
		                          ->order( array( 'desc' => 'desc' ) )
		                          ->select();
		foreach ( $list as $key => $item ) {
			$path = iconv( "utf-8", "gbk", $item['db_id'] );
			$res  = file_exists( __BOOKS__ . $path );
			if ( $res ) {
				$list[ $key ]['cover'] = '/Books/' . $item['db_id'] . '/00001.jpg';
			} else {
				$list[ $key ]['cover'] = '/Public/img/cover.jpg';
			}
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

	public function doEdit() {
		$data = I( 'post.' );

		if ( ! $data['id'] ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => 'ID不能为0' ) );
		}
		$saveData['name']       = trim( $data['name'] );
		$saveData['org_name']   = trim( $data['org_name'] );
		$saveData['tags']       = trim( $data['tags'] );
		$saveData['total_page'] = intval( $data['total_page'] );
		$saveData['desc']       = trim( $data['desc'] );
		$saveData['add_time']   = time();

		$path    = iconv( "utf-8", "gbk", $saveData['org_name'] );
		$isExist = file_exists( __BOOKS__ . $path );

		if ( $isExist ) {
			$saveData['status'] = 3;
		}

		$res = M( $this->table )->where( 'id = ' . $data['id'] )->save( $saveData );
		if ( $res ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '保存成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '修改失败' ) );
		}
	}
}

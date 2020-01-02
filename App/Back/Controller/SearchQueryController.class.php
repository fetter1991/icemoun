<?php

namespace Back\Controller;

use QL\QueryList;

class SearchQueryController extends CommonController {
	private $server = 'https://www.18comic.life/';
	private $serverQuery = 'https://18comic.life/search/photos?search_query=';
	private $table = 'query_search';

	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	/**
	 * 入口
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
		$where['status'] = 1;

		$count = M( $this->table )->where( $where )->count( '1' );
		$page  = new \Common\Page( $count, 15 );
		$list  = M( $this->table )->where( $where )
		                          ->limit( $page->firstRow, $page->listRows )
		                          ->order( array( 'add_time' => 'desc' ) )
		                          ->select();
		foreach ( $list as $key => $item ) {
			$path = iconv( "utf-8", "gbk", $item['name'] );
			$res  = file_exists( __BOOKS__ . $path );
			if ( $res ) {
				$list[ $key ]['cover'] = '/Books/' . $item['name'] . '/00001.jpg';
			} else {
				$list[ $key ]['cover'] = '';
			}
		}

		$this->assign( 'list', $list );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	/**
	 * 关键词采集
	 */
	public function searchQuery() {
		$postData = I( 'post.' );
		$keyword  = $postData['keyword'];
		$page     = $postData['page'] ? $postData['page'] : 1;
		$url      = $this->serverQuery . $keyword . '&page=' . $page;

		$result = $this->httpRequest( $url, '', '' );

		$pattern = "<img data-original=\".*img-responsive \" />";
		preg_match_all( $pattern, $result, $matches );

		foreach ( $matches[0] as $key => $item ) {
			$link_rule = '#albums/\d+#';
			preg_match( $link_rule, $item, $link_match );

			$title_rule = '#title=".*" alt#';
			preg_match( $title_rule, $item, $title_match );

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

			$val['link'] = str_replace( $search, "", $link_match[0] );
			$val['name'] = str_replace( $search, "", $title_match[0] );
			$addData[]   = $val;
		}

		$res = M( $this->table )->addAll( $addData );
		if ( $res ) {
			$this->success( '成功', '/Back/SearchQuery/index' );
		} else {
			$this->error( '失败' );
		}
	}

	//开始采集数据
	public function startGet() {
		$info = M( $this->table )->where( 'status = 0' )->find();

		$isExist = M( 'ice_comic' )->where( 'db_id = ' . $info['db_id'] )->find();

		$status['status'] = 1;
		if ( ! $isExist ) {
			$Query  = new GetComicController();
			$result = $Query->getComic( $info['db_id'] );
			if ( $result ) {
				$save = M( $this->table )->where( 'id = ' . $info['id'] )->save( $result );
				if ( $save ) {
					$this->ajaxReturn( array( 'code' => 200, 'msg' => '', 'id' => $info['id'] ) );
				}
			}
		} else {
			//数据存在，更新
			M( $this->table )->where( 'id = ' . $info['id'] )->save( $status );
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '数据已存在', 'id' => $info['id'] ) );
		}
	}

	/**
	 * 获取信息
	 */
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

	/**
	 * 编辑
	 */
	public function doEdit() {
		$data                   = I( 'post.' );
		$saveData['name']       = trim( $data['name'] );
		$saveData['author']     = trim( $data['author'] );
		$saveData['org_name']   = trim( $data['org_name'] );
		$saveData['link']       = 'album/' . $data['db_id'];
		$saveData['tags']       = trim( $data['tags'] );
		$saveData['db_id']      = intval( $data['db_id'] );
		$saveData['total_page'] = intval( $data['total_page'] );
		$saveData['desc']       = trim( $data['desc'] );
		$saveData['add_time']   = time();

		$path    = iconv( "utf-8", "gbk", $saveData['name'] );
		$isExist = file_exists( __BOOKS__ . $path );

		if ( $isExist ) {
			$saveData['status'] = 3;
		}

		$res = M( 'ice_comic' )->add( $saveData );
		if ( $res ) {
			$status['status'] = 3;
			$res              = M( $this->table )->where( 'db_id = ' . $data['db_id'] )->save( $status );
			$this->makeFolder( $data['db_id'] );
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '保存成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '修改失败' ) );
		}
	}

	/**
	 * 创建文件
	 */
	public function makeFolder( $id ) {
		$info = M( $this->table )->where( 'db_id = ' . $id )->find();

		$path = __BOOKS__ . $id;
		mkdir( $path );

		$handel             = fopen( $path . '/' . $id . '.json', 'a+' );
		$data['db_id']      = $info['db_id'];
		$data['link']       = $info['link'];
		$data['name']       = $info['name'];
		$data['org_name']   = $info['org_name'];
		$data['author']     = $info['author'];
		$data['tags']       = $info['tags'];
		$data['total_page'] = $info['total_page'];
		$data['cover']      = $info['cover'];
		$data['banner']     = $info['banner'];
		$json               = json_encode( $data, JSON_UNESCAPED_UNICODE );

		fwrite( $handel, $json );
		fclose( $handel );
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
	public function httpRequest( $sUrl, $aHeader, $aData ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_URL, $sUrl );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $aHeader );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $aData ) );
		$sResult = curl_exec( $ch );
		if ( $sError = curl_error( $ch ) ) {
			die( $sError );
		}
		curl_close( $ch );

		return $sResult;
	}

	public function isExist() {
		$list = M( 'ice_comic' )->where( 'id < 704' )->select();
		foreach ( $list as $info ) {


			$path    = iconv( "utf-8", "gbk", $info['name'] );
			$isExist = file_exists( __BOOKS__ . $path );
			$handel             = fopen( __BOOKS__ . $path . '/' . $info['db_id'] . '.json', 'a+' );
			$data['db_id']      = $info['db_id'];
			$data['link']       = $info['link'];
			$data['name']       = $info['name'];
			$data['org_name']   = $info['org_name'];
			$data['author']     = $info['author'];
			$data['tags']       = $info['tags'];
			$data['total_page'] = $info['total_page'];
			$data['cover']      = $info['cover'];
			$data['banner']     = $info['banner'];
			$json               = json_encode( $data, JSON_UNESCAPED_UNICODE );

			fwrite( $handel, $json );
			fclose( $handel );

			if ( $isExist ) {
				$res = rename( __BOOKS__ . $path, __BOOKS__ . $info['db_id'] );
			}
		}
	}

}
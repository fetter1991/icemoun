<?php

namespace Back\Controller;

use QL\QueryList;

class SearchQueryController extends CommonController {
	private $server = 'https://www.18comic.life/';
	private $serverQuery = 'https://18comic.life/search/photos?search_query=';
	private $table = 'query_search_0103';

	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	/**
	 * 入口
	 */
	public function index() {
		$keyword = I( 'keyword' );
		$status  = I( 'status' );
		$order   = I( 'order' );
		$sort    = I( 'sort' );
		if ( $keyword && is_numeric( $keyword ) ) {
			$map['db_id']      = array( 'like', '%' . $keyword . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
		} elseif ( $keyword && ! is_numeric( $keyword ) ) {
			$map['name']       = array( 'like', '%' . $keyword . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
		}
		//状态选择
		if ( is_numeric( $status ) ) {
			$where['status'] = $status;
		}

		$where['status'] = 0;


		$count = M( $this->table )->where( $where )->count( '1' );
		$page  = new \Common\Page( $count, 15 );
		$list  = M( $this->table )->where( $where )
		                          ->limit( $page->firstRow, $page->listRows )
		                          ->order( array( 'add_time' => 'desc' ) )
		                          ->select();

		$this->assign( 'list', $list );
		$this->assign( 'status', $status );
		$this->assign( 'order', $order );
		$this->assign( 'sort', $sort );
		$this->assign( 'keyword', $keyword );
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
		$db_id = I( 'id' );
		$info  = M( $this->table )->where( 'db_id = ' . $db_id )->find();
		$str   = '';
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
		$data = I( 'post.' );

		$saveData['name']       = trim( $data['name'] );
		$saveData['author']     = trim( $data['author'] );
		$saveData['org_name']   = trim( $data['org_name'] );
		$saveData['link']       = 'album/' . $data['db_id'];
		$saveData['tags']       = trim( $data['tags'] );
		$saveData['db_id']      = intval( $data['db_id'] );
		$saveData['total_page'] = intval( $data['total_page'] );
		$saveData['desc']       = trim( $data['desc'] );
		$saveData['cover']      = $data['cover'];
		$saveData['banner']     = $data['banner'];
		$saveData['add_time']   = time();

		$res = M( 'query_search' )->where( 'db_id = ' . $data['db_id'] )->save( $saveData );
		if ( $res ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '保存成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '修改失败' ) );
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
}
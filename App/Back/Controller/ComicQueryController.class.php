<?php

namespace Back\Controller;

class ComicQueryController extends CommonController {
	private $server = 'https://www.18comic.biz/';
	private $serverQuery = 'https://18comic.biz/search/photos?search_query=';
	private $table = 'ice_comic' . __COPY__;

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
			$map['comic_id']      = array( 'like', '%' . $keyword . '%' );
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

		$count = M( $this->table )->where( $where )->count( 1 );
		$page  = new \Common\Page( $count, 15 );
		$list  = M( $this->table )->where( $where )
		                          ->limit( $page->firstRow, $page->listRows )
		                          ->order( array( 'comic_id' => 'asc' ) )
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

		$addData = array();
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
			$val['desc'] = 'add';
			$addData[]   = $val;
		}
		if ( $addData ) {
			$res = M( $this->table )->addAll( $addData );
			if ( $res ) {
				$this->success( '成功', '/Back/ComicQuery/index' );
			} else {
				$this->error( '失败' );
			}
		}
	}

	/**
	 * 获取信息
	 */
	public function getInfo() {
		$comic_id = I( 'id' );
		$info  = M( $this->table )->where( 'comic_id = ' . $comic_id )->find();
		$str   = "";
		for ( $i = 1; $i <= $info["total_page"]; $i ++ ) {
			$p   = str_pad( $i, 5, "0", STR_PAD_LEFT );
			$isExist = file_exists(__BKSER__.$info["comic_id"].'/'.$p.'.jpg');
			if (!$isExist){
                $str .= "https://cdn-ms.18comic.biz/media/photos/" . $info['comic_id'] . '/' . $p . ".jpg\n";
            }
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
		$saveData['link']       = 'album/' . $data['comic_id'];
		$saveData['tags']       = trim( $data['tags'] );
		$saveData['comic_id']      = intval( $data['comic_id'] );
		$saveData['total_page'] = intval( $data['total_page'] );
		$saveData['desc']       = trim( $data['desc'] );
		$saveData['cover']      = $data['cover'];
		$saveData['banner']     = $data['banner'];
		$saveData['add_time']   = time();

		$res = M( $this->table )->where( 'comic_id = ' . $data['comic_id'] )->save( $saveData );
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
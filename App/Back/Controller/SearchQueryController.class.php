<?php

namespace Back\Controller;

class SearchQueryController extends CommonController {

	public function index() {
		$this->display();
	}

	public function searchQuery() {
		$postData = I( 'post.' );
		$keyword  = $postData['keyword'];
		$page     = $postData['page'] ? $postData['page'] : 1;
		$url      = 'https://18comic.life/search/photos?search_query=' . $keyword . '&page=' . $page;

		$result = $this->httpRequest( $url );

		$pattern = "<img data-original=\".*img-responsive \" />";
		preg_match_all( $pattern, $result, $matches );

		foreach ( $matches[0] as $key => $item ) {
			$link_rule = '#albums/\d+#';
			preg_match( $link_rule, $item, $link_match );

			$title_rule = '#title=".*" alt#';
			preg_match( $title_rule, $item, $title_match );

			$val['link'] = $link_match[0];
			$val['name'] = $title_match[0];
			$addData[]   = $val;
		}

		$res = M( 'query_search' )->addAll( $addData );
		if ( $res ) {
			$this->success( '成功', '/Back/Comic/queryIndex' );
		} else {
			$this->error( '失败' );
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
<?php

namespace Common\Lib;

class MoxiangReading {
	//1.1书籍列表接口
	//http://www.moxiangreading.com/api/v1/novel            $sign
	//1.2章节列表
	//http://www.moxiangreading.com/api/v1/chapters         $sign    $book_id(书籍id)
	//1.3章节内容
	//http://www.moxiangreading.com/api/v1/chapter/info     $sign    $chapter_id(章节id)
	//1.4书籍详情
	//http://www.moxiangreading.com/api/v1/book             $sign    $book_id(书籍id)
	private $sign = 'c42653c7ddfc44b1dffaf72628311be9';

	/**
	 * @param $keyworld
	 * @param $id
	 *
	 * @return bool|string
	 */
	public function getResult( $keyworld, $id ) {
		//请求Header头
		$header = array( 'sign:' . $this->sign );
		$url    = $this->getUrlType( $keyworld, $id );
		$result = $this->curl_get_https( $url, $header );

		return $result;
	}

	/**
	 * 调用并解析接口内容
	 *
	 * @param $keyworld
	 * @param $id
	 *
	 * @return array
	 */
	public function getInfo( $keyworld, $id ) {
		//请求Header头
		$header = array( 'sign:' . $this->sign );
		$url    = $this->getUrlType( $keyworld, $id );
		$result = $this->curl_get_https( $url, $header );

		$result = json_decode( $result, true );
		if ( $result['errorCode'] == 0 ) {
			return array( 'code' => 200, 'data' => $result['data'], 'msg' => '获取信息成功' );
		} else {
			return array( 'code' => $result['errorCode'], 'data' => $result['data'], 'msg' => $result['message'] );
		}
	}

	/**
	 * 获取url
	 *
	 * @param $key
	 * @param $id
	 *
	 * @return mixed
	 */
	private function getUrlType( $key, $id ) {
		$list = array(
			'novel'    => 'http://www.moxiangreading.com/api/v1/novel',                             //1.1书籍列表接口
			'chapters' => 'http://www.moxiangreading.com/api/v1/chapters?book_id=' . $id,           //1.2章节列表
			'info'     => 'http://www.moxiangreading.com/api/v1/chapter/info?chapter_id=' . $id,    //1.3章节内容
			'book'     => 'http://www.moxiangreading.com/api/v1/book?book_id=' . $id,               //1.4书籍详情
		);

		return $list[ $key ];
	}


	/**
	 * curl get
	 *
	 * @param $url
	 * @param $header
	 *
	 * @return bool|string
	 */
	private function curl_get_https( $url, $header ) {
		// 初始化一个新会话
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		// 执行CURL请求
		$output = curl_exec( $ch );
		// 关闭CURL资源
		curl_close( $ch );

		// 输出返回信息
		return $output;
	}
}
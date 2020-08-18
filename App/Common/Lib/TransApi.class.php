<?php
/**
 * 翻译Class
 */

namespace Common\Lib;

define( "CURL_TIMEOUT", 10 );
define( "URL", "http://api.fanyi.baidu.com/api/trans/vip/translate" );
define( "APP_ID", "20190801000323078" ); //替换为您的APPID
define( "SEC_KEY", "7DnClqR_MwfUv9cCYBxS" );//替换为您的密钥
class TransApi {
	//翻译入口
	public function translate( $query, $from, $to ) {
		$args         = array(
			'q'     => $query,
			'appid' => APP_ID,
			'salt'  => rand( 10000, 99999 ),
			'from'  => $from,
			'to'    => $to,

		);
		$args['sign'] = $this->buildSign( $query, APP_ID, $args['salt'], SEC_KEY );
		$ret          = $this->call( URL, $args );
		$ret          = json_decode( $ret, true );

		return $ret;
	}

	//加密
	private function buildSign( $query, $appID, $salt, $secKey ) {
		$str = $appID . $query . $salt . $secKey;
		$ret = md5( $str );

		return $ret;
	}

	//发起网络请求
	private function call(
		$url,
		$args = null,
		$method = "post",
		$testflag = 0,
		$timeout = CURL_TIMEOUT,
		$headers = array()
	) {
		$ret = false;
		$i   = 0;
		while ( $ret === false ) {
			if ( $i > 1 ) {
				break;
			}
			if ( $i > 0 ) {
				usleep( 500  );
			}
			$ret = $this->callOnce( $url, $args, $method, false, $timeout, $headers );
			$i ++;
		}

		return $ret;
	}

	private function callOnce(
		$url,
		$args = null,
		$method = "post",
		$withCookie = false,
		$timeout = CURL_TIMEOUT,
		$headers = array()
	) {
		$ch = curl_init();
		if ( $method == "post" ) {
			$data = $this->convert( $args );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $ch, CURLOPT_POST, 1 );
		} else {
			$data = $this->convert( $args );
			if ( $data ) {
				if ( stripos( $url, "?" ) > 0 ) {
					$url .= "&$data";
				} else {
					$url .= "?$data";
				}
			}
		}
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		if ( ! empty( $headers ) ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}
		if ( $withCookie ) {
			curl_setopt( $ch, CURLOPT_COOKIEJAR, $_COOKIE );
		}
		$r = curl_exec( $ch );
		curl_close( $ch );

		return $r;
	}

	private function convert( &$args ) {
		$data = '';
		if ( is_array( $args ) ) {
			foreach ( $args as $key => $val ) {
				if ( is_array( $val ) ) {
					foreach ( $val as $k => $v ) {
						$data .= $key . '[' . $k . ']=' . rawurlencode( $v ) . '&';
					}
				} else {
					$data .= "$key=" . rawurlencode( $val ) . "&";
				}
			}

			return trim( $data, "&" );
		}

		return $args;
	}
}
<?php

namespace Common\Lib;

define( "CURL_TIMEOUT", 2000 );
define( "URL", "http://openapi.youdao.com/api" );
define( "APP_KEY", "03fcba3d8dee65fc" ); // 替换为您的应用ID
define( "SEC_KEY", "6guGoPRWu68NPoVyfOChsqFDOMxHDVa4" ); // 替换为您的密钥
class YouDaoApi {
	/**
	 * 翻译
	 *
	 * @param $q
	 *
	 * @return bool|string
	 */
	public function do_request( $q ) {
		$salt             = $this->create_guid();
		$args             = array(
			'q'      => $q,
			'appKey' => APP_KEY,
			'salt'   => $salt,
		);
		$args['from']     = 'zh-CHS';
		$args['to']       = 'EN';
		$args['signType'] = 'v3';
		$curtime          = strtotime( "now" );
		$args['curtime']  = $curtime;
		$signStr          = APP_KEY . $this->truncate( $q ) . $salt . $curtime . SEC_KEY;
		$args['sign']     = hash( "sha256", $signStr );
		$ret              = $this->call( URL, $args );

		return $ret;
	}

	/**
	 * 发起网络请求
	 *
	 * @param $url
	 * @param null $args
	 * @param string $method
	 * @param int $testflag
	 * @param int $timeout
	 * @param array $headers
	 *
	 * @return bool|string
	 */
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
				sleep( 1 );
			}
			$ret = $this->callOnce( $url, $args, $method, false, $timeout, $headers );
			$i ++;
		}

		return $ret;
	}

	/**
	 * callOnce
	 *
	 * @param $url
	 * @param null $args
	 * @param string $method
	 * @param bool $withCookie
	 * @param int $timeout
	 * @param array $headers
	 *
	 * @return bool|string
	 */
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

	/**
	 * convert
	 *
	 * @param $args
	 *
	 * @return string
	 */
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

	/**
	 * uuid generator
	 * @return string
	 */
	private function create_guid() {
		$microTime = microtime();
		list( $a_dec, $a_sec ) = explode( " ", $microTime );
		$dec_hex = dechex( $a_dec * 1000000 );
		$sec_hex = dechex( $a_sec );
		$this->ensure_length( $dec_hex, 5 );
		$this->ensure_length( $sec_hex, 6 );
		$guid = "";
		$guid .= $dec_hex;
		$guid .= $this->create_guid_section( 3 );
		$guid .= '-';
		$guid .= $this->create_guid_section( 4 );
		$guid .= '-';
		$guid .= $this->create_guid_section( 4 );
		$guid .= '-';
		$guid .= $this->create_guid_section( 4 );
		$guid .= '-';
		$guid .= $sec_hex;
		$guid .= $this->create_guid_section( 6 );

		return $guid;
	}

	/**
	 * create_guid_section
	 *
	 * @param $characters
	 *
	 * @return string
	 */
	private function create_guid_section( $characters ) {
		$return = "";
		for ( $i = 0; $i < $characters; $i ++ ) {
			$return .= dechex( mt_rand( 0, 15 ) );
		}

		return $return;
	}

	/**
	 * truncate
	 *
	 * @param $q
	 *
	 * @return string
	 */
	private function truncate( $q ) {
		$len = $this->abslength( $q );

		return $len <= 20 ? $q : ( mb_substr( $q, 0, 10 ) . $len . mb_substr( $q, $len - 10, $len ) );
	}

	/**
	 * @param $string
	 * @param $length
	 */
	private function ensure_length( &$string, $length ) {
		$strlen = strlen( $string );
		if ( $strlen < $length ) {
			$string = str_pad( $string, $length, "0" );
		} elseif ( $strlen > $length ) {
			$string = substr( $string, 0, $length );
		}
	}

	/**
	 * abslength
	 *
	 * @param $str
	 *
	 * @return int
	 */
	private function abslength( $str ) {
		if ( empty( $str ) ) {
			return 0;
		}
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $str, 'utf-8' );
		} else {
			preg_match_all( "/./u", $str, $ar );

			return count( $ar[0] );
		}
	}

}
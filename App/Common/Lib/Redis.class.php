<?php

/**
 * title : redis 公共方法
 * auth: Tsj
 * date 2018/7/3
 */

namespace Common\Lib;


class Redis {

	/**
	 * Redis的ip
	 * @var string
	 */
//    const REDISHOSTNAME = "172.16.0.9";
	const REDISHOSTNAME = "127.0.0.1";

	/**
	 * Redis的port
	 * @var int
	 */
	const REDISPORT = 6379;
//    const REDISPORT = 6380;

	/**
	 * Redis的超时时间
	 * @var int
	 */
	const REDISTIMEOUT = 0;

	/**
	 * Redis的password
	 * @var unknown_type
	 */
//    const REDISPASSWORD = "crs-hrxl79ic:tujie888#@!";
	const REDISPASSWORD = "tsj";

	/**
	 * Redis的DBname
	 * @var int
	 */
	const REDISDBNAME = 1;

	/**
	 * 类单例
	 * @var object
	 */
	private static $instance;

	/**
	 * Redis的连接句柄
	 * @var object
	 */
	private $redis;

	/**
	 * 私有化构造函数，防止类外实例化
	 *
	 * @param unknown_type $selectDb
	 */
	public function __construct( $selectDb = '' ) {

		// 连接数据库
		$this->redis = new \Redis();
		$this->redis->connect( self::REDISHOSTNAME, self::REDISPORT, self::REDISTIMEOUT );
		$this->redis->auth( self::REDISPASSWORD );
		if ( $selectDb == '' ) {
			$this->redis->select( self::REDISDBNAME );
		} else {
			$this->redis->select( $selectDb );
		}
	}

	/**
	 * 获取设置为 1 的比特位的数量
	 *
	 * @param type $KEY 下标
	 * @param type $START 开始位数
	 * @param type $END 结束位数
	 */
	public function bitcount( $KEY, $START = '0', $END = '-1' ) {
		try {
			$count      = $this->redis->bitcount( $KEY, $START, $END );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取集合数据
	 *
	 * @param type $key
	 * @param type $start 开始下标
	 * @param type $end 结束下标
	 */
	public function zget( $key = '', $start = '0', $end = '-1' ) {
		try {
			$count      = $this->redis->zRange( $key, $start, $end, true );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取指定score 值
	 *
	 * @param type $key
	 * @param type $val $val
	 */
	public function zScore( $key, $val ) {
		try {
			$count      = $this->redis->zScore( $key, $val );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取指定score 值
	 *
	 * @param type $key
	 * @param type $val $val
	 */
	public function sAdd( $key, $val ) {
		try {
			$count      = $this->redis->sAdd( $key, $val);
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取集合数据 按照score从高到低的顺序进行排列
	 *
	 * @param type $key
	 * @param type $start 开始下标
	 * @param type $end 结束下标
	 */
	public function zRevRange( $key = '', $start = '0', $end = '-1' ) {
		try {
			$count      = $this->redis->zRevRange( $key, $start, $end, true );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取字符串类型数据
	 *
	 * @param type $string
	 */
	public function stringGet( $string ) {
		try {
			$count      = $this->redis->get( $string );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 指定key生存时间
	 *
	 * @param type $string
	 */
	public function expire( $key, $time ) {
		try {
			$count  = $this->redis->expire( $key, $time );
			$result = $count ? $count : 0;
			if ( $result ) {
				$successArr = array( 200, $count );

				return $successArr;
			} else {
				$successArr = array( 0, $count );

				return $successArr;
			}
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 插入字符串类型数据
	 *
	 * @param type $string
	 */
	public function stringSet( $rediskey, $string ) {
		try {
			$count = $this->redis->set( $rediskey, $string );
			if ( $count ) {
				$successArr = array( 200, $count );

				return $successArr;
			} else {
				$successArr = array( 0, $count );

				return $successArr;
			}
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 删除字符串类型
	 *
	 * @param type $key
	 *
	 * @return type
	 */
	public function delectString( $key ) {
		try {
			$res        = $this->redis->del( $key );
			$successArr = array( 200, $res );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}

	}


	/**
	 * 获取指定bit 值
	 *
	 * @param type $string
	 */
	public function getbit( $string, $key ) {
		try {
			$count      = $this->redis->getBit( $string, $key );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 插入hash 数据
	 *
	 * @param type $key
	 * @param type $value
	 */
	public function Hset( $table, $key, $value ) {
		try {
			$count      = $this->redis->hSet( $table, $key, $value );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取指定key哈希表数据
	 *
	 * @param type $table
	 * @param type $key
	 *
	 * @return type
	 */
	public function hGet( $table, $key ) {
		try {
			$count      = $this->redis->hGet( $table, $key );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取指定表所有数据
	 *
	 * @param type $table
	 *
	 * @return type
	 */
	public function hgetAll( $table ) {
		try {
			$count      = $this->redis->hGetAll( $table );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 获取zert 列长度
	 *
	 * @param type $listKey 集合
	 *
	 * @return type
	 */
	public function zCard( $listKey ) {
		try {
			$rwo        = $this->redis->zCard( $listKey );
			$successArr = array( 200, $rwo );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 将制定列合并
	 *
	 * @param type $key 合并为制定列
	 * @param type $listKey 列集合
	 *
	 * @return type
	 */
	public function zunionstore( $key, $listKey ) {
		try {
			$res        = $this->redis->zunionstore( $key, $listKey );
			$successArr = array( 200, $res );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	public function scard( $key ) {
		try {
			$count      = $this->redis->scard( $key );
			$result     = $count ? $count : 0;
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	public function smembers( $key ) {
		try {
			$count      = $this->redis->smembers( $key );
			$result     = $count ? $count : array();
			$successArr = array( 200, $result );

			return $successArr;
		} catch ( Exception $exc ) {
			$errorArr = array( 0, $exc->getMessage() );

			return $errorArr;
		}
	}

	/**
	 * 私有化克隆函数，防止类外克隆对象
	 */
	private function __clone() {
	}

	/**
	 * 需要在单例切换的时候做清理工作
	 */
	public function __destruct() {
		$this->redis->close();
	}

}

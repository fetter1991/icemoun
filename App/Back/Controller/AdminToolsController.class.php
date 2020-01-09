<?php


namespace Back\Controller;

use Common\Lib\Redis;
use QL\QueryList;

/**
 * 管理员工具
 *
 * Class AdminTools
 * @package Back\Controller
 */
class AdminToolsController extends CommonController {
	public function __construct() {
		parent::__construct();
	}

	public function index() {
		phpinfo();
	}


	/**
	 * 遍历文件夹
	 *
	 * @param $files
	 */
	private function list_file( $files ) {
		//1、首先先读取文件夹
		$temp = scandir( $files );
		//遍历文件夹
		foreach ( $temp as $v ) {
			$a = $files . '/' . $v;
			//如果是文件夹则执行
			if ( is_dir( $a ) ) {
				//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
				if ( $v == '.' || $v == '..' ) {
					continue;
				}
				//把文件夹红名输出
				//echo "<font color='red'>$a</font>", "<br/>";

				//因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
				$this->list_file( $a );
			} else {
				echo $a, "<br/>";
			}
		}
	}


	/**
	 * redis 测试工具
	 */
	public function redisTest() {
		$formData = I( 'get.' );

		$val    = $formData['val'];                         //方法的值
		$key    = $formData['key'];                         //方法的Key
		$action = $formData['action'];                      //redis方法名
		$db     = $formData['db'] ? $formData['db'] : 0;    //数据库编号

		$res   = '';
		$redis = new Redis( $db );
		switch ( $action ) {
			case 'zScore';
				$res = $redis->zScore( $key, $val );
				break;
			case 'scard';
				$res = $redis->scard( $key );
				break;
			case 'zget';
				$res = $redis->zget( $key );
				break;
			case 'zRevRange';
				$res = $redis->zRevRange( $key );
				break;
			case 'stringGet';
				$res = $redis->stringGet( $val );
				break;
			case 'hGet';
				$res = $redis->hGet( $key, $val );
				break;
			case 'smembers';
				$res = $redis->smembers( $key );
				break;
		}

		$return['val']    = $val;
		$return['key']    = 'Redis Key值为：' . $key;
		$return['action'] = 'Redis 方法：' . $action;
		$return['res']    = $res;

		$this->ajaxReturn( $return );
	}

	/**
	 * 下载电影数据
	 *
	 * @throws \PHPExcel_Exception
	 * @throws \PHPExcel_Writer_Exception
	 */
	public function downToExcel() {
		$userInfo = session( 'user_info' );
		if ( $userInfo['id'] && $userInfo['username'] != 'admin' ) {
			$this->redirect( '/Back/Comic' );
		}

		$getData  = I( 'get.' );
		$moviesId = $getData['id'];
		//章节列表
		$chapter = M( 'chapter' )->where( 'movies_id = ' . $moviesId )
		                         ->field( 'id,movies_id,name,sortrank,add_time' )
		                         ->select();

		//解说列表
		$images = array();
		foreach ( $chapter as $item ) {
			$imagesData = M( 'chapter_image' )->where( 'chapter_id = ' . $item['id'] )
			                                  ->field( 'id,chapter_id,reading,url,sortrank,add_time' )
			                                  ->select();
			$images     = array_merge( $images, $imagesData );
		}

		Vendor( "phpexcel.Classes.PHPExcel" );
		Vendor( "phpexcel.Classes.PHPExcel.Writer.Excel2007" );

		$objExcel  = new \PHPExcel();
		$objWriter = new \PHPExcel_Writer_Excel5( $objExcel );

		for ( $i = 0; $i < 2; $i ++ ) {
			if ( $i > 0 ) {
				$objExcel->createSheet();
			}
			$objExcel->setactivesheetindex( $i );
		}

		$objExcel->setActiveSheetIndex( 0 );
		$objExcel->getActiveSheet()->setTitle( '章节列表' );
		$objExcel->getActiveSheet()->setCellValue( 'A1', "id" );
		$objExcel->getActiveSheet()->setCellValue( 'B1', "movies_id" );
		$objExcel->getActiveSheet()->setCellValue( 'C1', "name" );
		$objExcel->getActiveSheet()->setCellValue( 'D1', "sortrank" );
		$objExcel->getActiveSheet()->setCellValue( 'E1', "add_time" );
		$objExcel->getActiveSheet()->getColumnDimension( 'A' )->setWidth( 20 );
		$objExcel->getActiveSheet()->getColumnDimension( 'B' )->setWidth( 10 );
		$objExcel->getActiveSheet()->getColumnDimension( 'C' )->setWidth( 20 );
		$objExcel->getActiveSheet()->getColumnDimension( 'D' )->setWidth( 20 );
		$objExcel->getActiveSheet()->getColumnDimension( 'E' )->setWidth( 10 );
		$i = 2;
		foreach ( $chapter as $key => $val ) {
			$objExcel->getActiveSheet()->setCellValue( 'A' . $i, $val['id'] );
			$objExcel->getActiveSheet()->setCellValue( 'B' . $i, $val['movies_id'] );
			$objExcel->getActiveSheet()->setCellValue( 'C' . $i, $val['name'] );
			$objExcel->getActiveSheet()->setCellValue( 'D' . $i, $val['sortrank'] );
			$objExcel->getActiveSheet()->setCellValue( 'E' . $i, $val['add_time'] );
			$i ++;
		}

		$objExcel->setActiveSheetIndex( 1 );
		$objExcel->getActiveSheet()->setTitle( '解说列表' );
		$objExcel->getActiveSheet()->setCellValue( 'A1', "id" );
		$objExcel->getActiveSheet()->setCellValue( 'B1', "chapter_id" );
		$objExcel->getActiveSheet()->setCellValue( 'C1', "sortrank" );
		$objExcel->getActiveSheet()->setCellValue( 'D1', "url" );
		$objExcel->getActiveSheet()->setCellValue( 'E1', "reading" );
		$objExcel->getActiveSheet()->setCellValue( 'F1', "status" );
		$objExcel->getActiveSheet()->setCellValue( 'G1', "add_time" );
		$objExcel->getActiveSheet()->getColumnDimension( 'A' )->setWidth( 20 );
		$objExcel->getActiveSheet()->getColumnDimension( 'B' )->setWidth( 10 );
		$objExcel->getActiveSheet()->getColumnDimension( 'C' )->setWidth( 20 );
		$objExcel->getActiveSheet()->getColumnDimension( 'D' )->setWidth( 20 );
		$objExcel->getActiveSheet()->getColumnDimension( 'E' )->setWidth( 10 );
		$objExcel->getActiveSheet()->getColumnDimension( 'F' )->setWidth( 10 );
		$objExcel->getActiveSheet()->getColumnDimension( 'G' )->setWidth( 10 );
		$j = 2;
		foreach ( $images as $vo ) {
			$objExcel->getActiveSheet()->setCellValue( 'A' . $j, $vo['id'] );
			$objExcel->getActiveSheet()->setCellValue( 'B' . $j, $vo['chapter_id'] );
			$objExcel->getActiveSheet()->setCellValue( 'C' . $j, $vo['sortrank'] );
			$objExcel->getActiveSheet()->setCellValue( 'D' . $j, $vo['url'] );
			$objExcel->getActiveSheet()->setCellValue( 'E' . $j, $vo['reading'] );
			$objExcel->getActiveSheet()->setCellValue( 'F' . $j, $vo['status'] );
			$objExcel->getActiveSheet()->setCellValue( 'G' . $j, $vo['add_time'] );
			$j ++;
		}

		header( "Pragma: public" );
		header( "Expires: 0" );
		header( "Cache-Control:must-revalidate, post-check=0, pre-check=0" );
		header( "Content-Type:application/force-download" );
		header( "Content-Type:application/vnd.ms-execl" );
		header( "Content-Type:application/octet-stream" );
		header( "Content-Type:application/download" );
		header( 'Content-Disposition:attachment;filename="ID：' . $moviesId . '电影数据.xls"' );
		header( "Content-Transfer-Encoding:binary" );

		$objWriter->save( 'php://output' );
	}


	/**
	 * 加入检测表
	 */
	public function addToCheck() {
		$tableList = array(
			'ice_comic_check_copy',
			'ice_comic_copy',
			'ice_comic_copy1',
			'ice_comic_copy2',
			'query_search_1',
			'query_search_2',
			'query_search_3'
		);
		$this->_addCheck( $tableList );
	}

	/**
	 * 入库
	 *
	 * @param $tableList
	 *
	 * @return array|string
	 */
	private function _addCheck( $tableList ) {
		if ( ! $tableList ) {
			return '';
		}

		$comicList = array();
		foreach ( $tableList as $table ) {
			$list      = M( $table )->where( 'db_id > 0' )->select();
			$comicList = array_merge( $comicList, $list );
		}


		$msg = '';
		foreach ( $comicList as $item ) {
			$res1 = M( 'ice_comic' . __COPY__ )->where( 'db_id = ' . $item['db_id'] )->find();
			$res2 = M( 'ice_comic_check_error' . __COPY__ )->where( 'db_id = ' . $item['db_id'] )->find();
			if ( ! $res1 && ! $res2 ) {
				unset( $item['id'] );
				$save             = $item;
				$save['desc']     = "";
				$save['status']   = 0;
				$save['add_time'] = 0;

				$addRes = M( 'ice_comic_check_new' . __COPY__ )->add( $save );
				if ( $addRes ) {
					echo $item['db_id'] . "添加成功<br/>";
				} else {
					echo $item['db_id'] . "添加失败<br/>";
				}
			} else {
				echo $item['db_id'] . "已存在<br/>";
			}
		}
	}

	/**
	 * 检测图片数量
	 *
	 * @param $status
	 */
	public function checkImgStatus() {
		$msg = '';
		//0：待检测 1:已检测，数据未下载 2:已检测,数据有异常 3:数据正常，可入库
		$list = M( 'ice_comic_check_new' . __COPY__ )->where( 'db_id > 0' )->select();
		//检测文件夹及文件数量
		foreach ( $list as $key => $item ) {
			echo $key . ":正在检测ID：" . $item['db_id'] . "<br/>";
			$path    = __BKSER__ . $item['db_id'];
			$isExist = file_exists( $path );
			if ( $isExist ) {
				echo $key . ":ID：" . $item['db_id'] . "已下载，检测图片数量<br/>";
				//计算文件数量，去掉"./"和"../"
				$count = intval( count( scandir( $path ) ) ) - 2;
				if ( $count != $item['total_page'] ) {
					$saveData['desc']       = '图片数量异常';
					$saveData['img_status'] = $count < $item['total_page'] ? '2' : '3';
					$saveData['status']     = 2;
					echo $key . ":ID：" . $item['db_id'] . "图片数量异常，状态码" . $saveData['img_status'] . "<hr/>";
				} else {
					$saveData['desc']       = '处理完成';
					$saveData['img_status'] = 1;
					$saveData['status']     = 3;
					echo $key . ":ID：" . $item['db_id'] . "图片正常<hr/>";
				}
			} else {
				$saveData['desc']       = '未下载';
				$saveData['img_status'] = 0;
				$saveData['status']     = 1;
				echo $key . ":ID：" . $item['db_id'] . "未下载<hr/>";
			}
			$saveData['add_time'] = time();

			$res = M( 'ice_comic_check_new' . __COPY__ )->where( 'db_id = ' . $item['db_id'] )->save( $saveData );
			if ( ! $res ) {
				$msg = $item['db_id'] . "更新失败\n";
			}
		}
	}


	/**
	 * 入库操作
	 */
	public function addComic() {
		$msg  = '';
		$list = M( 'ice_comic_check_new' . __COPY__ )->where( 'status = 3' )->order( array( 'db_id' => 'asc' ) )->select();
		foreach ( $list as $item ) {
			$isExist = M( 'ice_comic' . __COPY__ )->where( 'db_id = ' . $item['db_id'] )->find();
			if ( ! $isExist ) {
				unset( $item['id'] );
				$saveData               = $item;
				$saveData['img_status'] = 1;
				$saveData['status']     = 3;
				$saveData['desc']       = '';
				$saveData['add_time']   = time();
				$res                    = M( 'ice_comic' . __COPY__ )->add( $saveData );
				if ( ! $res ) {
					echo $item['db_id'] . "入库失败<br/>";
				}
			}
		}
	}

	/**
	 * 抓取数据并更新
	 */
	public function updateCheckDate() {
		//采集方法
		$Comic = new GetComicController();
		//逐个更新，由前端控制
		$info = M( 'ice_comic_check_new' . __COPY__ )->where( 'status = 4' )->find();
		if ( ! $info ) {
			$this->ajaxReturn( array( 'code' => 500, 'msg' => '无数据' ) );
		}

		$result = $Comic->getComic( $info['db_id'] );
		if ( $result['code'] == 200 ) {
			$saveData               = $result['data'];
			$saveData['status']     = 1;
			$saveData['img_status'] = 0;
			$saveData['add_time']   = time();

			$res = M( 'ice_comic_check_new' . __COPY__ )->where( 'db_id = ' . $info['db_id'] )->save( $saveData );
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'id' => $info['db_id'], 'msg' => '更新成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'id' => $info['db_id'], 'msg' => '更新失败' ) );
			}
		} else {
			$errData['status']    = 4;
			$errData['desc']      = '数据抓取失败';
			$saveData['add_time'] = time();

			$res = M( 'ice_comic_check_new' . __COPY__ )->where( 'db_id = ' . $info['db_id'] )->save( $errData );
			$this->ajaxReturn( array( 'code' => 200, 'id' => $info['db_id'], 'msg' => '抓取数据失败' ) );
		}
	}

	public function checkNo() {
		$list = scandir( __BOOKS__ );
		unset( $list[0] );
		unset( $list[1] );
		foreach ( $list as $item ) {
			$res = M( 'ice_comic' )->where( 'db_id = ' . $item )->find();
			if ( ! $res ) {
				print_r( $item );
				echo "<br/>";
			}
		}
	}

	//重命名
	private function _rename( $path ) {
//		$path = './';
		$list = scandir( $path );

		foreach ( $list as $k => $dir ) {
			if ( $dir == '.' || $dir == '..' || $dir == 'index.php' ) {
				continue;
			}
			if ( is_dir( $dir ) ) {
				$sec_dir = scandir( $dir );
				foreach ( $sec_dir as $key => $item ) {
					if ( $item == '.' || $item == '..' || $item == 'index.php' ) {
						continue;
					}
					$page = str_pad( ( $key - 1 ), 5, 0, STR_PAD_LEFT );

					$old = "./" . $dir . "/" . $item;
					$new = "./" . $dir . "/" . $page . '.jpg';
					print_r( rename( $old, $new ) );
					echo '<br/>';
				}
			} else {
				$page = str_pad( ( $k - 1 ), 5, 0, STR_PAD_LEFT );
				$old  = "./" . $dir;
				$new  = "./" . $page . '.jpg';
				print_r( rename( $old, $new ) );
				echo '<br/>';
			}
		}
	}
}
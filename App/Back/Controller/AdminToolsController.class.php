<?php


namespace Back\Controller;

use Common\Lib\Redis;
use AppAdmin\Controller\PushSettingController;

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

	public function pushTest() {
		$user_id          = I( 'id' );
		$userInfo         = M( 'app_user_info as app' )->where( 'app.user_id = ' . $user_id )
		                                               ->join( 'left join yy_user_info as ui on app.user_id = ui.user_id' )
		                                               ->field( 'app.xg_token,app.platform,ui.nick_name' )
		                                               ->find();
		$data['yy2c']     = '{"a":6, "v": 1, "p":{}}';
		$data['title']    = '你的金币余额不足';
		$data['content']  = '尊敬的{user_name}，您的金币余额不足100金币，限时特惠，首充9.9元，可得7天VIP。';
		$data['platform'] = $userInfo['platform'];
		$data['data']     = array(
			array(
				'token'     => $userInfo['xg_token'],
				'user_name' => $userInfo['nick_name']
			)
		);
		$res              = $this->push( $data );
		$this->ajaxReturn( $res );
	}

	public function push( $data ) {
		if ( $data['data'] ) {
			$code  = json_encode( $data, JSON_UNESCAPED_UNICODE );
			$crypt = new \Org\Encry\CryptAES();
			$crypt->set_key( 'a3fc338dcca1642037d3a56082fc5453' );
			$crypt->require_pkcs5();
			$decrypt_code = $crypt->encrypt( $code );

			$url      = 'http://wxapp-test.jiayoumei-tech.com/api/app/v1/push';
			$postdata = array( 'params' => $decrypt_code );
			$res      = http_request( $url, $postdata );
			$jsonres  = json_decode( $res, true );

			return $jsonres;
		}
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


		//日期
		$val    = $formData['val'];
		$key    = $formData['key'];
		$action = $formData['action'];
		$db     = $formData['db'] ? $formData['db'] : 0;

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

	public function addTags() {
		$Model = M( 'comic' );
		$p     = I( 'get.page' );
		$left  = $p * 1000;
		$right = ( $p + 1 ) * 1000;
		$list  = $Model->where( "id > " . $left . " and id < " . $right . " and tags !=''" )->select();

		foreach ( $list as $item ) {
			$arr = explode( '|', $item['tags'] );
			if ( $arr ) {
				foreach ( $arr as $val ) {
					if ( $val ) {
						$val     = trim( $val );
						$isExist = M( 'tags' )->where( "name = '" . $val . "'" )->find();
						if ( ! $isExist ) {
							$data['tag_type'] = 2;
							$data['name']     = trim( $val );
							$res              = M( 'tags' )->add( $data );
						} else {
							$res = M( 'tags' )->where( "name = '" . $val . "'" )->setInc( 'order_num' );
						}
						echo $res . '<br/>';
					}
				}
			}
		}
	}
}
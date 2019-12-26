<?php

/**
 * H5求片功能
 * @time         2019-07-18
 * @author       pyt
 * @version     1.0
 */

namespace Back\Controller;

class WantSeeController extends CommonController {

	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	/**
	 * 求片列表
	 */
	public function index() {
		$status = I( 'get.status' );

		$where      = array();
		$returnData = array();

		if ( empty( $status ) ) {
			$where = array( 'status' => 0 );
		} elseif ( $status != 'all' ) {
			$where = array( 'status' => $status );
		}
		$returnData['status'] = $status;

		$count      = M( 'user_want_see as w' )->where( $where )->count( 1 );
		$PageObj    = new \Common\Page( $count, 20 );
		$searchData = M( 'user_want_see' )
			->where( $where )
			->limit( $PageObj->firstRow, $PageObj->listRows )
			->order( 'add_time asc' )
			->select();

		$returnData['list'] = $searchData;
		$returnData['page'] = $PageObj->show();
		$this->assign( $returnData );
		$this->display();
	}

	/**
	 * 数据处理
	 */
	public function edit() {
		$data = I( 'post.' );
		if ( empty( $data['id'] ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => 'ID不能为空' ) );
		}

		$data['update_time'] = time();
		$wantSee             = M( 'user_want_see' );
		if ( $data['gold'] > 0 ) {
			$totalGold = $wantSee->where( 'user_id = ' . $data['user_id'] )->sum( 'gold' );
			if ( $totalGold >= 500 ) {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '已获得' . $totalGold . '个金币' ) );
			}
			if ( intval( $totalGold + $data['gold'] ) > 500 ) {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '已获得' . $totalGold . '个金币' ) );
			}
		}

		$res = $wantSee->where( 'id = ' . $data['id'] )->save( $data );
		if ( ! $res ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}

		if ( $res && $data['gold'] <= 0 ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功' ) );
		} else {
			$bool = $this->doEditGold( $data['user_id'], $data['gold'] );
			if ( $bool ) {
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '处理完成，更新用户金币数出错' ) );
			}
		}
	}


	/**
	 * @param $user_id      用户user_id
	 * @param $addGold     添加金币数
	 *
	 * @return bool       操作结果
	 */
	public function doEditGold( $user_id, $addGold ) {
		$adminId = session( 'user_id' );

		//获取原阅读币数量
		$old_gold = M( 'user_info' )->where( array( 'user_id' => $user_id ) )->getField( 'gold' );
		//记录日志
		//$saveGold = $old_gold + $addGold;
		//$this->log( $adminId, $user_id, $old_gold, $saveGold );
		$bool = M( 'user_info' )->where( array( 'user_id' => $user_id ) )->setInc( 'gold', $addGold );
		if ( $bool ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * log操作
	 *
	 * @param $adminId      操作员id
	 * @param $user_id      用户id
	 * @param $old_gold     原本金币数
	 * @param $saveGold     修改后金币数
	 */
	public function log( $adminId, $user_id, $old_gold, $saveGold ) {
		$day      = date( "Ymd" );
		$filename = 'log/goldChange/' . $day;
		if ( ! file_exists( $filename ) ) {
			mkdir( $filename, 0777, true );
			$fileLiu           = fopen( $filename . '/goldChange.txt', "w" );
			$array             = array();
			$array['time']     = date( 'Y-m-d H:i:s' );
			$array['admin_id'] = $adminId;
			$array['user_id']  = $user_id;
			$array['old_gold'] = $old_gold;
			$array['new_gold'] = $saveGold;
			$json              = json_encode( $array );
			$json              .= "\r\n";
			fwrite( $fileLiu, $json );
		} else {
			$myfile = fopen( $filename . '/goldChange.txt', "a" ) or die( "Unable to open file!" );  //w  重写  a追加
			$array             = array();
			$array['time']     = date( 'Y-m-d H:i:s' );
			$array['admin_id'] = $adminId;
			$array['user_id']  = $user_id;
			$array['old_gold'] = $old_gold;
			$array['new_gold'] = $saveGold;
			$json              = json_encode( $array );
			$json              .= "\r\n";
			fwrite( $myfile, $json );
		}
	}

}

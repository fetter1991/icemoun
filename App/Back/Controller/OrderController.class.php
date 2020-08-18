<?php


namespace Back\Controller;


class OrderController extends CommonController {
	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	public function index() {
		$keyword = I( 'keyword' );
		$order   = array( 'add_time' => 'desc' );

		if ( ! empty( $keyword ) ) {
			$wName['tee.trade_no'] = array( 'like', '%' . $keyword . '%' );
			$wName['_logic']       = 'or';
			$where['_complex']     = $wName;
		}

		$where['t.pay_status'] = 1;
		//支付状态为1的订单
		$Model       = M( 'user_tee as tee' );
		$countNumber = $Model->join( 'left join yy_trade as t on t.trade_no = tee.trade_no ' )
		                     ->join( 'left join yy_user_address as us on us.id = tee.address_id ' )
		                     ->where( $where )
		                     ->count( 1 );

		$page   = new \Common\Page( $countNumber );
		$result = $Model->join( 'left join yy_trade as t on t.trade_no = tee.trade_no ' )
		                ->join( 'left join yy_user_address as us on us.id = tee.address_id ' )
		                ->field( 'tee.*,t.pay_status,t.pay_time,t.pay_channel,us.id as add_id,us.name as add_user,us.province,us.city,us.county,us.region,us.address,us.phone' )
		                ->where( $where )
		                ->order( $order )
		                ->limit( $page->firstRow, $page->listRows )
		                ->select();
		foreach ( $result as $key => $value ) {
			$buyInfo = $giftInfo = '';
			//购买详情
			$goods_info = json_decode( $value['goods_info'], true );
			foreach ( $goods_info['detail'] as $item ) {
				$buyInfo .= $item['name'] . '：' . $item['num'] . "<br/>";
			}
			$result[ $key ]['buyInfo'] = $buyInfo;
			//赠品详情
			$gift_info = json_decode( $value['gift_info'], true );
			foreach ( $gift_info as $item ) {
				$giftInfo .= $item . "<br/>";
			}
			$result[ $key ]['giftInfo'] = $giftInfo;
			//金额 两位小数
			$result[ $key ]['pay'] = number_format( $value['pay'] / 100, 2 );
			//收货地址
			if ( $value['add_id'] ) {
				$result[ $key ]['address'] = '收件人：' . $value['add_user'] . '<br/>联系方式：' . $value['phone'] .
				                             '<br/>详细地址：' . $value['province'] . $value['city'] . $value['county'] . $value['region'] . $value['address'];
			}
		}
		$returnData['list']    = $result;
		$returnData['keyword'] = $keyword;
		$returnData['page']    = $page->show();
		$this->assign( $returnData );
		$this->display();
	}

	/**
	 * 新增
	 */
	public function add() {
		$Model = M( 'user_tee' );
		$data  = I( 'post.' );

		if ( ! $data['id'] ) {
			$this->_ajaxReturn( 0, 'id不能为空', '' );
		}

		$res = $Model->add( $data );

		if ( $res ) {
			$this->_ajaxReturn( 200, '修改成功', $res );
		} else {
			$this->_ajaxReturn( 0, '修改失败', '' );
		}
	}

	/**
	 * 编辑
	 */
	public function edit() {
		$Model = M( 'user_tee' );
		$data  = I( 'post.' );

		if ( ! $data['id'] ) {
			$this->_ajaxReturn( 0, 'id不能为空', '' );
		}

		$where['id'] = $data['id'];
		$res         = $Model->where( $where )->save( $data );

		if ( $res ) {
			$this->_ajaxReturn( 200, '修改成功', $res );
		} else {
			$this->_ajaxReturn( 0, '修改失败', '' );
		}
	}

	/**
	 * 获取编辑信息
	 */
	public function getEditInfo() {
		$Model = M( 'user_tee' );
		$id    = I( 'id' );
		if ( ! $id ) {
			$this->_ajaxReturn( 0, 'id不能为空', '' );
		}

		$where['id'] = $id;
		$editInfo    = $Model->where( $where )->find();

		if ( $editInfo ) {
			$this->_ajaxReturn( 200, '', $editInfo );
		} else {
			$this->_ajaxReturn( 0, '获取信息失败', '' );
		}
	}

	/**
	 * 设置状态
	 */
	public function setStatus() {
		$Model = M( 'user_tee' );
		$data  = I( 'post.' );
		if ( ! $data['id'] ) {
			$this->_ajaxReturn( 0, 'id不能为空', '' );
		}
		if ( $data['status'] == 0 ) {
			$data['tracking_com'] = '';
			$data['tracking_no']  = '';
		}
		$res = $Model->save( $data );
		if ( $res ) {
			$this->success( '修改成功', '/Back/Order/index' );
		} else {
			$this->error( '修改失败', '/Back/Order/index' );
		}
	}

	/**
	 * 返回数据
	 *
	 * @param $code
	 * @param $msg
	 * @param $data
	 */
	private function _ajaxReturn( $code, $msg, $data ) {
		$this->ajaxReturn(
			array( 'code' => $code, 'msg' => $msg, 'data' => $data )
		);
	}
}
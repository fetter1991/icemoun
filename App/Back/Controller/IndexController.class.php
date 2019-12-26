<?php

namespace Back\Controller;

use Think\Controller;
use Common\Lib\Redis;

class IndexController extends CommonController {
	public function index() {
		$user_id = session( 'user_id' );
		$one     = M( 'admin as a ' )
			->join( 'left join yy_auth_group_access as b on a.id=b.uid ' )
			->join( 'left join yy_auth_group as c on c.id=b.group_id' )
			->where( 'a.id = ' . $user_id )
			->field( 'a.id,a.password,a.username,a.session_time,c.id as group_id' )
			->find();

		$show = 1;
		if ( $one['group_id'] == 9 ) {
			$show = 0;
		}

		$this->assign( 'show', $show );
		$this->display();
	}


	public function getCont() {
		$type = I( 'post.type' );

		switch ( $type ) {
			case 'day': //今日充值
				$tradeWhere['tra.pay_time']   = array(
					array( 'egt', strtotime( date( 'Y-m-d', time() ) ) ),
					array( 'lt', strtotime( date( 'Y-m-d', time() ) . '+1 day' ) )
				);
				$tradeWhere['tra.pay_status'] = 1;
				$day                          = M( 'trade as tra' )->where( $tradeWhere )->sum( 'tra.pay' ); //按天充值
				$result                       = empty( $day ) ? 0 : getReadMoney( $day );
				break;
			case 'month': //本月充值
				$map                 = array(
					'a.date' => array( 'like', date( 'Y-m', time() ) . '%' )
				);
				$map['c.is_youying'] = array( 'neq', 1 );
				$channelmonth        = M( 'pay_data as a' )
					->join( 'yy_channel as c on c.id = a.channel_id' )->where( $map )->sum( 'a.pay' );
				$map['c.is_youying'] = 1;
				$is_youying_month    = M( 'pay_data as a' )
					->join( 'yy_channel as c on c.id = a.channel_id' )
					->where( $map )->sum( 'a.pay' );
				$result['sum']       = getReadMoney( $channelmonth + $is_youying_month );
				$result['channel']   = getReadMoney( $channelmonth );
				$result['youying']   = getReadMoney( $is_youying_month );
				break;
			case 'paysum': //累积充值
				$map['c.is_youying'] = array( 'neq', 1 );
				$channelmonth        = M( 'pay_data as a' )
					->join( 'yy_channel as c on c.id = a.channel_id' )->where( $map )->sum( 'a.pay' );
				$map['c.is_youying'] = 1;
				$is_youying_month    = M( 'pay_data as a' )
					->join( 'yy_channel as c on c.id = a.channel_id' )
					->where( $map )->sum( 'a.pay' );
				$result['sum']       = getReadMoney( $channelmonth + $is_youying_month );
				$result['channel']   = getReadMoney( $channelmonth );
				$result['youying']   = getReadMoney( $is_youying_month );
				break;
			case 'userCount': //总引流人数
				$result = M( 'user' )->count( 1 );
				break;
			case 'drainageNum': //今日引流人数
				$redis     = new Redis(); //redis 封装方法
				$maxUserId = M( 'user' )->order( 'id desc' )->getField( 'id' ); //最大用户id
				$oneDay    = date( "Ymd" );
				$onedayKey = "total:min-uid:" . $oneDay;
				list( $code, $res ) = $redis->stringGet( $onedayKey );
				$result = $code == 200 && $res != '' ? $maxUserId - $res : 0;
				break;
			case 'drainageNumMonth': //本月引流人数
				$redis     = new Redis(); //redis 封装方法
				$maxUserId = M( 'user' )->order( 'id desc' )->getField( 'id' ); //最大用户id
				$monthDay  = date( "Ym01" );
				$onedayKey = "total:min-uid:" . $monthDay;
				list( $codeMonth, $resMonth ) = $redis->stringGet( $onedayKey );
				$result = $codeMonth == 200 && $resMonth != '' ? $maxUserId - $resMonth : 0;
				break;
			case 'todayFollow': //今日关注人数
				$where2['add_time']  = array(
					array(
						'egt',
						strtotime( date( 'Y-m-d', time() ) ),
						array( 'lt', strtotime( date( 'Y-m-d', time() ) ) . '+1 day' )
					)
				);
				$where2['is_follow'] = 1;
				$result              = M( 'user' )->where( $where2 )->count( 1 );
				break;
			case 'sumCost': //总成本
				$map['c.is_youying'] = array( 'neq', 1 );
				$channelmonth        = M( 'Expand as a' )
					->join( 'yy_channel as c on c.id = a.channel_id' )->where( $map )->sum( 'a.cost' );
				$map['c.is_youying'] = 1;
				$is_youying_month    = M( 'Expand as a' )
					->join( 'yy_channel as c on c.id = a.channel_id' )
					->where( $map )->sum( 'a.cost' );
				$result['sum']       = getReadMoney( $channelmonth + $is_youying_month );
				$result['channel']   = getReadMoney( $channelmonth );
				$result['youying']   = getReadMoney( $is_youying_month );


				break;
			case 'followCount': //总关注数
				$result = M( 'user' )->where( 'is_follow = 1' )->count( 1 );
				break;
			default:
				$result = 0;
				break;
		}
		$res = array(
			'code' => 200,
			'res'  => $result
		);
		$this->ajaxReturn( $res );
	}


	public function personal_data() {
		if ( IS_POST ) {
			$post        = I( 'post.' );
			$where['id'] = $post['id'];
			$npwd        = $post['new_pwd'];
			$rpwd        = $post['r_pwd'];
			if ( $npwd != $rpwd ) {
				$this->error( '新密码与确认密码不一致' );
			}
			$where['password'] = D( 'admin' )->getPwd( $post['old_pwd'] );
			if ( ! $ret = D( 'admin' )->where( $where )->count() ) {
				$this->error( '当前密码错误' );
			}
			$data['username'] = $post['username'];
			$data['password'] = D( 'admin' )->getPwd( $post['new_pwd'] );

			$res = D( 'admin' )->where( $where )->save( $data );
			if ( $res ) {
				$this->success( '保存成功' );
				die;
			} else {
				$this->error( '保存失败!' );
				die;
			}
		}
		$where['id'] = session( 'user_id' );
		$data        = D( 'admin' )->where( $where )->field( 'id,username' )->find();
		$this->assign( 'data', $data );
		$this->display();
	}

}
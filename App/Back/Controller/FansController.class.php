<?php

namespace Back\Controller;

use Common\Page;
use Think\Controller;
use Common\Lib\Wethird\Weixin;
use Think\Model;

class FansController extends CommonController {
	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	public function index() {
		//关键字搜索用户
		$where = array();
		if ( ! empty( I( 'get.nick_name' ) ) ) {
			$keywords = trim( I( 'get.nick_name' ) );
			if ( is_numeric( $keywords ) ) {
				$where['id'] = $keywords;
			}
		}
		$val = I( 'get.val' );
		if ( ! empty( $val ) ) {
			$where['channel_id'] = $val;
		}
		$pay = I( 'get.pay' );
		if ( $pay == 1 ) {
			$where['total'] = array( 'gt', 0 );

		} elseif ( $pay == 2 ) {
			$where['total'] = array( 'eq', 0 );
			$this->assign( 'val', I( 'get.pay' ) );
		}
		//expandID
		$expand_id = I( 'get.expand_id' );
		if ( $expand_id ) {
			$where['expand_id'] = $expand_id;
			$this->assign( 'expand_id', I( 'get.expand_id' ) );
		}
		$this->assign( 'pay', $pay );
		//所有渠道
		$channellist = M( 'channel' )->field( 'id,nick_name' )->select();
		$this->assign( 'channellist', $channellist );
		//数据翻页
		$UserView = D( 'UserView' );
		$rst      = $UserView->query( "show table status like 'yy_user';" );
		$count    = $rst[0]['auto_increment'];
		$count    -= 10013;
		if ( I( 'get.p' ) > 50 ) {
			$this->error( '不允许查看50页以后的数据' );
		}
		$p = new Page( $count, 20 );

		$userlist = $UserView->where( $where )
		                     ->limit( $p->firstRow . ',' . $p->listRows )
		                     ->order( 'id desc' )
		                     ->select();
		$this->assign( 'val', $val );
		if ( empty( $userlist ) ) {
			$this->assign( 'flag', 0 );
		} else {
			$this->assign( 'flag', 1 );
		}
		$this->assign( 'userlist', $userlist );

		$this->assign( 'page', $p->show( false ) );
		$this->display();
	}

	//修改阅读币页面
	public function editGold() {
		$id   = I( 'id' );
		$gold = M( 'user_info' )->where( 'user_id = ' . $id )->getField( 'gold' );
		$this->assign( 'gold', $gold );
		$this->assign( 'user_id', $id );
		$this->display();
	}

	//修改阅读币
	public function doEditGold() {
		$user_id    = I( 'post.user_id' );
		$adminId    = session( 'user_id' );
		$goldplus   = I( 'post.plus' ); //加金币
		$goldreduce = I( 'post.reduce' ); //减金币
		$goldtype   = I( 'post.type' ); //减金币
		$gold       = M( 'user_info' )->where( 'user_id = ' . $user_id )->getField( 'gold' );
		if ( $goldtype == 1 ) {
			$indexGold = $gold + $goldplus;
		} else {
			$indexGold = $gold - $goldreduce;
		}
		$saveGold = $indexGold >= 0 ? $indexGold : 0;
		$this->log( $adminId, $user_id, $gold, $saveGold );
		$bool = M( 'user_info' )->where( 'user_id = ' . $user_id )->save( array( 'gold' => $saveGold ) );
		if ( $bool ) {
			$this->success( '修改成功' );
		} else {
			$this->error( '修改失败' );
		}
	}

	/**
	 *
	 * @param type $goldtype 加减操作
	 * @param type $adminId 操作员id
	 * @param type $user_id 用户id
	 * @param type $gold 原本金币数
	 * @param type $saveGold 修改后金币数
	 */

	public function log( $adminId, $user_id, $gold, $saveGold ) {
		$day      = date( "Ymd" );
		$filename = 'log/goldChange/' . $day;
		if ( ! file_exists( $filename ) ) {
			mkdir( $filename, 0777, true );
			$fileLiu           = fopen( $filename . '/goldChange.txt', "w" );
			$array             = array();
			$array['time']     = date( 'Y-m-d H:i:s' );
			$array['admin_id'] = $adminId;
			$array['user_id']  = $user_id;
			$array['old_gold'] = $gold;
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
			$array['old_gold'] = $gold;
			$array['new_gold'] = $saveGold;
			$json              = json_encode( $array );
			$json              .= "\r\n";
			fwrite( $myfile, $json );
		}
	}

	//粉丝消费记录
	public function record() {
		if ( IS_AJAX ) {
			$id   = I( 'get.id' );
			$data = M( 'user_chapter as uc' )
				->field( 'uc.price,c.name,ch.name chapter,uc.add_time,FROM_UNIXTIME(uc.add_time,"%Y-%m-%d %H:%i:%s") as add_time' )
				->join( 'yy_movies as c on uc.movies_id=c.id' )
				->join( 'yy_chapter as ch on uc.chapter_id=ch.id' )
				->where( 'uc.user_id=' . $id )
				->order( 'uc.add_time desc' )
				->select();
			if ( ! empty( $data ) ) {
				$res['code'] = 0;
				$res['msg']  = 'ok';
				$res['data'] = $data;
			} else {
				$res['code'] = 1;
				$res['msg']  = 'error';
			}
			$this->ajaxReturn( $res );
		}
	}

	//粉丝充值记录
	public function getConsume() {
		if ( IS_AJAX ) {
			$id   = I( 'get.id' );
			$data = M( 'trade' )->where( 'user_id=' . $id . ' and pay_status=1' )->order( 'pay_time desc' )->select();
			foreach ( $data as $k => $v ) {
				$data[ $k ]['pay']      = getReadMoney( $v['pay'] );
				$data[ $k ]['pay_time'] = date( 'Y-m-d H:i:s', $v['pay_time'] );
			}
			if ( $data ) {
				$res['code'] = 0;
				$res['msg']  = 'ok';
				$res['data'] = $data;
			} else {
				$res['code'] = 1;
				$res['msg']  = 'error';
			}
			$this->ajaxReturn( $res );
		}
	}

	//发送文本消息
	public function sendText() {
		$user_id = I( 'user_id' );
//        $str = I('str','');
		$str = ! empty( $_POST['str'] ) ? $_POST['str'] : '';
		if ( empty( $user_id ) || empty( $str ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'errmsg' => '缺少参数' ) );
		}
		$user   = M( 'user' )->where( 'id=' . $user_id )->field( 'open_id,channel_id' )->find();
		$config = M( 'channel' )->where( 'id =' . $user['channel_id'] )->field( 'appid,nick_name' )->find();
		if ( empty( $config['appid'] ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'errmsg' => 'appid', 'nick_name' => $config['nick_name'] ) );
		}
		$wx = new Weixin( $config['appid'] );
		$wx->send_custom_message( $user['open_id'], 'text', $str );
		$this->sendLog( $user_id, $str );
		$this->ajaxReturn( array( 'code' => 200 ) );
	}

	/**
	 * 发送文本消息记录
	 */
	public function sendLog( $user_id, $str ) {
		$day      = date( "Ymd" );
		$adminId  = session( 'user_id' );
		$filename = 'log/sendMassage/' . $day;
		if ( ! file_exists( $filename ) ) {
			mkdir( $filename, 0777, true );
			$fileLiu               = fopen( $filename . '/sendMassage.txt', "w" );
			$array                 = array();
			$array['time']         = date( 'Y-m-d H:i:s' );
			$array['admin_id']     = $adminId;
			$array['user_name']    = session( 'user_info.username' );
			$array['ip']           = get_client_ip();
			$array['send_user_id'] = $user_id;
			$array['str']          = $str;
			$json                  = json_encode( $array );
			$json                  .= "\r\n";
			fwrite( $fileLiu, $json );
		} else {
			$myfile = fopen( $filename . '/sendMassage.txt', "a" ) or die( "Unable to open file!" );  //w  重写  a追加
			$array                 = array();
			$array['time']         = date( 'Y-m-d H:i:s' );
			$array['admin_id']     = $adminId;
			$array['user_name']    = session( 'user_info.username' );
			$array['ip']           = get_client_ip();
			$array['send_user_id'] = $user_id;
			$array['str']          = $str;
			$json                  = json_encode( $array );
			$json                  .= "\r\n";
			fwrite( $myfile, $json );
		}
	}

	/**
	 * 获取影片信息
	 */
	function getMoveisInfo() {
		$getInfo      = I( 'get.' );
		$movies_name  = '';
		$chapter_name = '';
		if ( $getInfo['movies_id'] ) {
			$movies_name = M( 'movies' )->where( 'id =' . $getInfo['movies_id'] )->getField( 'name' );
		}
		if ( $getInfo['chapter_id'] ) {
			$chapter_name = M( 'chapter' )->where( 'id =' . $getInfo['chapter_id'] )->getField( 'name' );
		}
		$this->ajaxReturn( array( 'movies_name' => $movies_name, 'chapter_name' => $chapter_name ) );
	}

	/**
	 * SPM Test
	 */
	public function spmtest() {
		$getData = I( 'get.' );
		$action  = $getData['action'];
		$deal    = $getData['deal'];
		$content = array(
			'user_id'     => $getData['user_id'],
			'platform_id' => isset( $getData['platform_id'] ) ? $getData['platform_id'] : 0,
			'page'        => $getData['p'] <= 0 ? 1 : $getData['p'],
			'date'        => $getData['date'] ? date( 'Ymd', strtotime( $getData['date'] ) ) : date( 'Ymd', time() ),
			'foo_id'      => isset( $getData['foo_id'] ) ? $getData['foo_id'] : 0,
		);

		$res = $this->_SPM( $action, $content );
		if ( $deal && $deal == 1 ) {
			$list = $res['data']['list'];
			$list = $this->_SPMDataDeal( $list );
			$this->ajaxReturn( $list );
		} else {
			$this->ajaxReturn( $res );
		}
	}

	/**
	 * 用户轨迹
	 */
	public function UserTrajectory() {
		$getData = I( 'get.' );
		$assign  = array( 'code' => 0 );
		if ( ! $getData['user_id'] ) {
			$assign['code'] = 0;
			$assign['msg']  = 'userID不能为空';
		}
		//轨迹数据
		$content = array(
			'user_id'     => $getData['user_id'],
			'platform_id' => isset( $getData['platform_id'] ) ? $getData['platform_id'] : 0,
			'page'        => $getData['p'] <= 0 ? 1 : $getData['p'],
			'date'        => $getData['date'] ? date( 'Ymd', strtotime( $getData['date'] ) ) : date( 'Ymd', time() ),
			'foo_id'      => isset( $getData['foo_id'] ) ? $getData['foo_id'] : 0,
		);
		$res     = $this->_SPM( 'user', $content );
		if ( $res['code'] == 200 ) {
			$list            = $res['data']['list'];
			$list            = $this->_SPMDataDeal( $list );
			$assign['code']  = 200;
			$assign['list']  = $list;
			$page            = new \Common\Page( $res['data']['count'], $res['data']['page_size'] );
			$assign['pages'] = $page->show();
		} else {
			$assign['code'] = 0;
			$assign['list'] = '';
		}
		//平台列表
		$pConten = array(
			'platform_id' => 0
		);
		$pRes    = $this->_SPM( 'platformlist', $pConten );
		if ( $pRes['code'] == 200 ) {
			$assign['code']         = 200;
			$assign['platformList'] = $pRes['data'];
		} else {
			$assign['code']         = 0;
			$assign['platformList'] = '';
		}
		//页面功能表
		$fConten = array(
			'platform_id' => $content['platform_id']
		);
		$fRes    = $this->_SPM( 'foolist', $fConten );
		if ( $fRes['code'] == 200 ) {
			$assign['code']    = 200;
			$assign['fooList'] = $fRes['data'];
		} else {
			$assign['code']    = 0;
			$assign['fooList'] = '';
		}

		$assign['foo_id']      = $getData['foo_id'];
		$assign['user_id']     = $getData['user_id'];
		$assign['platform_id'] = $getData['platform_id'] ? $getData['platform_id'] : 0;
		$assign['date']        = date( 'Y-m-d', strtotime( $content['date'] ) );

		$this->assign( $assign );
		$this->display();
	}

	/**
	 * SPM接口
	 *
	 * @param $action
	 * @param $data
	 *
	 * @return bool
	 */
	private function _SPM( $action, $data ) {
		if ( empty( $action ) || empty( $data ) ) {
			return false;
		}
		$Token = 'LihM7JRRRjCP0AiRjXKPWXuFrieSMpzwNgSvkZYHXonRtRs0a8vxGiwqz2La';
		//$url   = 'https://test-spm.yymedias.com/';  //测试版
		$url = 'https://spm.yymedias.com/';       //正式版
		$url = $url . $action;
		//header头
		$Header = array( 'Token:' . $Token );
		//curl post请求
		$Result = $this->httpRequest( $url, $Header, $data );
		$res    = json_decode( $Result, true );

		if ( $res['err_code'] == 200 ) {
			$data['data'] = $res['data'];
			$data['code'] = 200;
		} else {
			$data['code'] = $res['err_code'];
			$data['msg']  = '';
		}

		return $data;
	}

	/**
	 * 数据处理
	 *
	 * @param $data
	 *
	 * @return array
	 */
	private function _SPMDataDeal( $data ) {
		if ( empty( $data ) ) {
			return [];
		}
		//Data ID为0
		$fool_zero = array(
			10100,
			10200,
			10300,
			10400,
			10401,
			10402,
			10403,
			10404,
			10406,
			10407,
			10408,
			10409,
			10410,
			10500,
			10501,
			10502,
			10800,
			10802,
			10803,
			10804
		);
		//影片
		$fool_movies = array(
			10301,
			10302,
			10304,
			10305,
			10600,
			10601,
			10602,
			10603,
			10604,
			10605,
			10606,
			10607,
			10608,
			10609,
			10700,
			10701,
			10702,
			10703,
			10704,
			10705,
			10706,
			10707,
			10708,
			10709,
			10710,
			10711,
			10712,
			10713,
			10714,
			10715,
			10801,
			10805,
			10806,
			1003
		);
		//Banner、icon
		$fool_img = array( 10101, 10102, 1002 );
		//弹窗ID
		$fool_win = array( 10104, 10105, 10106, 10107 );
		//特殊
		$fool_special = array( 10103, 10201, 10303, 1004 );

		foreach ( $data as $key => $v ) {
			$output  = '-';
			$channel = '';
			//渠道名
			if ( $v['platform_id'] == 110 || $v['platform_id'] == 111 ) {
				$Model                         = M( 'app_channel' );
				$data[ $key ]['platform_name'] = $v['platform_name'] . "<br/>版本号：" . $v['ver'];
			} else {
				$Model = M( 'channel' );
			}
			$channel                      = $Model->where( 'id = ' . $v['channel_id'] )->field( 'id,nick_name' )->find();
			$data[ $key ]['channel_name'] = $channel['nick_name'] ? $channel['nick_name'] : '';

			//foo_id判断
			if ( in_array( $v['foo_id'], $fool_zero ) ) {
				$data[ $key ]['expand'] = array( 'keyName' => '', 'output' => $output );
			} elseif ( in_array( $v['foo_id'], $fool_movies ) ) {
				//影片小说
				$movies = M( 'movies' )->where( 'status = 1 and id = ' . $v['data_id'] )->field( 'id,name,org_name,movies_type' )->find();
				if ( $movies ) {
					if ( $movies['org_name'] ) {
						$output = ( $movies['movies_type'] == 2 ? '小说名称：' : '影片名称：' ) . $movies['name'] . ' 【原名：' . $movies['org_name'] . '】';
					} else {
						$output = ( $movies['movies_type'] == 2 ? '小说名称：' : '影片名称：' ) . $movies['name'];
					}
					if ( intval( $v['exts'] ) > 0 ) {
						$chapter = M( 'chapter' )->where( 'status = 1 and id = ' . $v['exts'] )->field( 'id,name' )->find();
						if ( $chapter ) {
							$output .= "<br/>章节：" . $chapter['name'];
						}
					}
				}
				$data[ $key ]['expand'] = array(
					'keyName' => $movies['movies_type'] == 2 ? '小说' : '影片',
					'output'  => $output
				);
			} elseif ( in_array( $v['foo_id'], $fool_img ) ) {
				//图标Banner
				$output                 = ( $v['foo_id'] == 10101 ? 'Banner  ID：' : 'ICON  ID：' ) . $v['data_id'];
				$data[ $key ]['expand'] = array(
					'keyName' => $v['foo_id'] == 10101 ? 'Banner' : 'ICON',
					'output'  => $output
				);
			} elseif ( in_array( $v['foo_id'], $fool_win ) ) {
				//弹窗
				$data[ $key ]['expand'] = array(
					'keyName' => '弹窗',
					'output'  => $output
				);
			} elseif ( in_array( $v['foo_id'], $fool_special ) ) {
				//独立处理
				switch ( $v['foo_id'] ) {
					case 10103:
						$modular = array(
							'1000' => '典藏电影专辑',
							'1001' => '大家都在看',
							'1002' => '主编推荐',
							'1003' => '本周热门',
							'1004' => '猜你喜欢',
							'1005' => '发现更多感兴趣的作者',
							'1006' => '限时免费',
							'1007' => '电影专辑',
							'1008' => '影人系列',
							'1009' => '影视原著抢先看'
						);
						$output  = '首页 - ' . $modular[ $v['data_id'] ] . "<br/>";
						switch ( $v['data_id'] ) {
							case 1000:
							case 1008:
								$topic = M( 'topic' )->where( 'status != 2 and id = ' . $v['exts'] )->field( 'id,name' )->find();
								if ( $topic ) {
									$output .= "作者：" . $topic['author'] . "  专辑名称：《" . $topic['name'] . "》 ";
								}
								break;
							case 1001:
							case 1002:
							case 1003:
							case 1004:
							case 1006:
							case 1009:
								if ( $v['exts'] === "0" ) {
									$output .= "更多";
								} elseif ( $v['exts'] === "0.0" ) {
									$output .= "换一换";
								} else {
									$movies = M( 'movies' )->where( 'status =1 and id = ' . $v['exts'] )->field( 'id,name,org_name,movies_type' )->find();
									if ( $movies ) {
										if ( $movies['org_name'] ) {
											$output .= ( $movies['movies_type'] == 2 ? '小说名称：' : '影片名称：' ) . $movies['name'] . ' 【原名：' . $movies['org_name'] . '】';
										} else {
											$output .= ( $movies['movies_type'] == 2 ? '小说名称：' : '影片名称：' ) . $movies['name'];
										}
									}
								}
								break;
							case 1005:
								$author = M( 'author' )->where( 'status != 2 and id = ' . $v['exts'] )->field( 'id,nick_name' )->find();
								if ( $author ) {
									$output .= "作者名称：" . $author['nick_name'];
								}
								break;

						}
						$data[ $key ]['expand'] = array(
							'keyName' => '首页模块' - $modular[ $v['data_id'] ],
							'output'  => $output
						);
						break;
					case 10201:
						$type                   = M( 'form' )->where( 'id = ' . $v['data_id'] )->find();
						$data[ $key ]['expand'] = array(
							'keyName' => '分类点击',
							'output'  => '分类：' . $type['name']
						);
						break;
					case 10303:
						$topic = M( 'topic' )->where( 'status = 1 and id = ' . $v['foo_id'] )->find();
						if ( $topic ) {
							$output = '名称：' . $topic['name'] . "<br/>作者：" . $topic['author'] . "<br/>专辑类型:" . ( $topic['topic_type'] == 1 ? "一般专辑" : "影人专辑" );
						}
						$data[ $key ]['expand'] = array(
							'keyName' => '专辑',
							'output'  => $output
						);
						break;
					case 1004:
						$chapter = M( 'chapter' )->where( 'status = 1 and id = ' . $v['data_id'] )->field( 'id,name,movies_id' )->find();
						if ( $chapter ) {
							$output = '章节：' . $chapter['name'];
							$movies = M( 'movies' )->where( 'status = 1 and id = ' . $chapter['movies_id'] )->field( 'id,name' )->find();
							if ( $movies ) {
								$output .= "<br/>影片：" . $movies['name'];
							}
						}
						$data[ $key ]['expand'] = array(
							'keyName' => '专辑',
							'output'  => $output
						);
				}   //end special array
			} else {
				$data[ $key ]['expand'] = array(
					'keyName' => '',
					'output'  => $output
				);
			}
		}

		return $data;
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

<?php

/**
 * 图解管理
 *
 *
 * @author      tsj 作者
 * @version     2.0 版本号
 */

namespace Back\Controller;

use AppAdmin\Controller\PushSettingController;
use Exception;
use Common\Lib\AjaxPage;

class ComicController extends CommonController {

	//图解列表
	public function index() {
		$this->display();
	}

	/**
	 * 获取图解数据
	 */
	public function getMoviesData() {
		$selCategory = I( 'get.' );
		$where_str   = array();
		if ( is_numeric( $selCategory['name'] ) && ! empty( $selCategory['name'] ) ) {
			$where_str['mo.id'] = $selCategory['name'];
		} elseif ( ! empty( $selCategory['name'] ) ) {
			$movies_name_where['mo.name']     = array( 'like', '%' . $selCategory['name'] . '%' );
			$movies_name_where['mo.org_name'] = array( 'like', '%' . $selCategory['name'] . '%', 'or' );
			$movies_name_where['_logic']      = 'or';
			$where_str['_complex']            = $movies_name_where;
		}
		if ( ! empty( $selCategory['author_name'] ) ) {
			if ( is_numeric( $selCategory['author_name'] ) ) {
				$where_str['mo.author_id'] = $selCategory['author_name'];
			} else {
				$where_str['mo.author'] = array( 'like', '%' . $selCategory['author_name'] . '%' );
			}
		} elseif ( $selCategory['is_author'] != '' ) {
			$where_str['mo.author_id'] = $selCategory['is_author'] == 1 ? array( 'neq', 0 ) : 0;
		}
		if ( ! empty( $selCategory['id'] ) ) {
			$where_str['mo.id'] = $selCategory['id'];
		}
		if ( ! empty( $selCategory['mold'] ) ) {
			$where_str['mo.mold'] = $selCategory['mold'];
		}
		if ( $selCategory['status'] != '' ) {
			if ( $selCategory['status'] == 2 ) {
				$where_str['mo.status']      = 1;
				$where_str['mo.online_time'] = [ 'egt', time() ];
			} elseif ( $selCategory['status'] == 1 ) {
				$where_str['mo.status']      = 1;
				$where_str['mo.online_time'] = [ 'lt', time() ];
			} else {
				$where_str['mo.status'] = 0;
			}
		} else {
			$where_str['mo.status'] = [ 'neq', 2 ];
		}

		if ( ! empty( $selCategory['form'] ) ) {
			$where_str['mo.form'] = array( 'like', '%"' . $selCategory['form'] . '"%' );
		}
		if ( $selCategory['rank'] != '' ) {
			$where_str['mo.rank'] = $selCategory['rank'];
		}
		if ( $selCategory['is_tui'] == '1' ) {
			$is_tui['mo.expand_num']      = array( 'gt', 0 );
			$is_tui['mo.innerexpand_num'] = array( 'gt', 0, 'or' );
			$is_tui['_logic']             = 'or';
			$where_str['_complex']        = $is_tui;
		} elseif ( $selCategory['is_tui'] == '2' ) {
			$where_str['mo.expand_num']      = array( 'eq', 0 );
			$where_str['mo.innerexpand_num'] = array( 'eq', 0 );
		}

		if ( $selCategory['movies_type'] != '' ) {
			$where_str['mo.movies_type'] = $selCategory['movies_type'];
		}
		$movies_count = M( 'movies' )->alias( 'mo' )->where( $where_str )->count( 1 );
		$page         = new AjaxPage( $movies_count );
		$list         = m( 'movies' )->alias( 'mo' )->where( $where_str )
		                             ->join( 'left join yy_movies_free as fe on fe.start_time <= ' . time() . ' and fe.end_time >= ' . time() . ' and fe.movies_id = mo.id' )
		                             ->field( 'mo.id,mo.name,mo.org_name,mo.status,mo.rank,mo.author,mo.cover,mo.banner,mo.sex,mo.hot,mo.badge,mo.total_chapters,mo.movies_type,'
		                                      . 'mo.level,mo.price,mo.img_status,mo.hunt,fe.id as is_free,mo.author_id,mo.comments_num,mo.online_time,mo.db_id' )
		                             ->limit( $page->firstRow, $page->listRows )->order( 'mo.id desc' )
		                             ->select();
		foreach ( $list as &$value ) {
			$value['online_time_type'] = $value['online_time'] >= time() ? 1 : 2;
			$value['online_time']      = $value['online_time'] ? date( 'Y-m-d H:i:s', $value['online_time'] ) : '';
			if ( ! empty( $value['badge'] ) ) {
				$badge              = json_decode( $value['badge'], true );
				$value['badge_txt'] = $badge['txt'];
				$value['badge_bg']  = $badge['bg'];
			}
		}
		$returnData['page'] = $page->show();
		$returnData['data'] = $list;
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	/**
	 * 图解列表获取页面所需数据
	 */
	public function pageInfo() {
		$form     = M( 'form' )->field( 'id,name' )->select(); //类别
		$author   = M( 'author' )->where( array( 'status' => 1 ) )->field( 'id,nick_name' )->select(); //作者
		$zone     = M( 'zone' )->select(); //地区
		$showtime = M( 'showtime' )->select(); //上映年份
		//自动跳转工具后台所需密匙
		$user_id = session( 'user_id' );
		$account = M( 'admin' )->where( 'id = ' . $user_id )->find();
		import( 'Common.Lib.JoDES' );
		$des   = new \Des\JoDES();
		$login = $des->encode( json_encode( array(
			'account'  => $account['account'],
			'password' => $account['password']
		) ), C( 'APPC_KEY.LOGIN' ) );
		//end
		$returnData['admininfo'] = urlencode( $login );
		$returnData['form']      = $form;
		$returnData['author']    = $author;
		$returnData['zone']      = $zone;
		$returnData['showtime']  = $showtime;
		$this->ajaxReturn( $returnData );
	}

	/**
	 * 获取推广渠道数
	 */
	public function getInnerChannelNumber() {
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 500, 'msg' => '非法请求' ) );
		}
		$id = I( 'post.id' );
		if ( empty( $id ) ) {
			$this->ajaxReturn( array( 'code' => 500, 'msg' => '参数错误' ) );
		}
		$returnData = [];
		foreach ( $id as $k => $value ) {
			$id                                  = $value;
			$expand_arr                          = M( 'expand' )->where( 'movies_id =' . $id )->field( 'channel_id' )->group( 'channel_id' )->select();
			$innerexpand_arr                     = M( 'innerexpand' )->where( 'movies_id =' . $id )->field( 'channel_id' )->group( 'channel_id' )->select();
			$countInner                          = array_merge( $expand_arr, $innerexpand_arr );
			$inner_number                        = count( $countInner );
			$returnData[ $k ]['channel_tNumber'] = $inner_number;
			$returnData[ $k ]['id']              = $id;
		}
		$this->ajaxReturn( array( 'code' => 200, 'msg' => $returnData ) );
	}

	/**
	 * 获取图解信息
	 * return : 章节信息json字符串
	 */
	public function changePage() {
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 0, 'data' => '非法访问' ) );
		}
		$id = I( 'post.id' );
		if ( empty( $id ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'data' => '章节id为空' ) );
		}
		$result                  = M( 'Movies' )->where( 'id =' . $id )->find();
		$result['is_over']       = $result['overdate'] == 0 ? 0 : 1;
		$result['overdate']      = $result['overdate'] != 0 ? date( 'Y-m-d H:i:s', $result['overdate'] ) : '';
		$result['charging_time'] = $result['charging_time'] != 0 ? date( 'Y-m-d', $result['charging_time'] ) : '';
		$result['author_type']   = $result['author_id'] != 0 ? 2 : 1;
		$trim_from               = trim( $result['form'], '[' );
		$trim_from_you           = trim( $trim_from, ']' );
		$split_from              = explode( ',', $trim_from_you );
		$from_arr                = array();
		foreach ( $split_from as $from_str ) {
			$from_arr[] = trim( $from_str, '"' );
		}
		$result['form'] = $from_arr;
		unset( $result['badge'] );
		$this->ajaxReturn( array( 'code' => 200, 'data' => $result ) );
	}

	/**
	 * 添加图解
	 */
	public function add() {
		$Movies = D( "Movies" ); // 实例化对象
		if ( ! $Movies->create() ) {
			// 如果创建失败 表示验证没有通过 输出错误提示信息
			$this->ajaxReturn( array( 'code' => 500, 'msg' => $Movies->getError() ) );
		} else {
			$res = $Movies->add();

			//更新作者影片数
			$data       = I( 'post.' );
			$author_id  = $data['author_type'] == 2 ? $data['author_id'] : 0;
			$authorData = M( 'author' )->where( 'id = ' . $author_id )->find();
			if ( $authorData ) {
				$moviesSum = M( 'movies' )->where( 'status != 2 and author_id =' . $author_id )->count();
				M( 'author' )->where( 'id = ' . $data['author_id'] )->save( [ 'movies_count' => $moviesSum ] );
				$this->updateAuthorForm( $authorData['id'] );
			}

			$db_id = I( 'post.db_id' );
			if ( $db_id ) {
				M( 'movie_search' )->where( 'db_id =' . $db_id )->save( [ 'movie_id' => $res ] );
			}
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '添加成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 500, 'msg' => '添加失败' ) );
			}
		}
	}

	/**
	 * 修改图解
	 */
	public function edit() {
		$Movies = D( "Movies" ); // 实例化对象
		if ( ! $Movies->create() ) {
			// 如果创建失败 表示验证没有通过 输出错误提示信息
			$this->ajaxReturn( array( 'code' => 500, 'msg' => $Movies->getError() ) );
		} else {
			$res = $Movies->save();
			if ( $res ) {
				$data = I( 'post.' );
				//修改专辑影片等级
				$rank['rank'] = $data['rank'];
				M( 'topic_movies' )->where( 'movies_id =' . $data['id'] )->save( $rank );
				$resMoveis = M( 'movies' )->where( 'id =' . $data['id'] )->field( 'order_num,hot' )->find();
				if ( $data['hot'] != $resMoveis['hot'] ) {
					M( 'movies' )->where( 'hot >=' . $data['hot'] )->setInc( 'hot', 1 );
				}
				if ( $data['order_num'] != $resMoveis['order_num'] ) {
					M( 'movies' )->where( 'order_num >=' . $data['order_num'] )->setInc( 'order_num', 1 );
				}
				$db_id = $data['db_id'];
				if ( $db_id ) {
					M( 'movie_search' )->where( 'db_id =' . $db_id )->save( [ 'movie_id' => $data['id'] ] );
				}
				//修改作者影片数
				$authorData = M( 'author' )->where( 'id = ' . $data['author_id'] )->find();
				if ( $authorData ) {
					$moviesSum = M( 'movies' )->where( 'status != 2 and author_id =' . $data['author_id'] )->count();
					M( 'author' )->where( 'id = ' . $data['author_id'] )->save( [ 'movies_count' => $moviesSum ] );
					$this->updateAuthorForm( $data['author_id'] );
				}
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '修改成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 500, 'msg' => '修改失败' ) );
			}
		}
	}

	//上下架
	public function setStatus() {
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '非法入口' ) );
		}
		$id          = I( 'post.id' );
		$online_time = I( 'post.online_time' );
		$movies_info = M( 'movies' )->where( 'id ="' . $id . '"' )->field( 'author_id,form,status,lastupdate' )->find();

		$status_get = $movies_info['status'];
		$status     = $status_get == 0 ? 1 : 0;
		$online     = time();
		$data       = array();
		if ( $status == 1 ) {
			$data['status']      = $status;
			$data['online_time'] = $online_time ? strtotime( $online_time ) : $online;
			if ( $movies_info['lastupdate'] == 0 ) {
				$data['lastupdate'] = $online_time ? strtotime( $online_time ) : $online;
			}
			$data['total_chapters'] = M( 'chapter' )->where( 'status != 2 and movies_id = ' . $id )->count( 1 );
		} else {
			$data = array( 'status' => $status );
		}
		if ( M( 'movies' )->where( 'id ="' . $id . '"' )->save( $data ) ) {
			//更新标签数据
			if ( $movies_info['author_id'] ) {
				$this->updateAuthorForm( $movies_info['author_id'] );
			}
			//上架Push
			if ($data['status'] == 1){
				$pushSever = new PushSettingController();
				$pushSever->handlePush( '1006', $id );
			}
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功', 'status' => $status ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}
	}

	/**
	 * 更新作者标签数
	 *
	 * @param $author_id
	 *
	 * @return bool
	 */
	public function updateAuthorForm( $author_id ) {
		//作者标签统计
		$formlist = M( 'form' )->select();
		foreach ( $formlist as $item ) {
			$where = $saveData = array();

			$where['status']    = 1;
			$where['author_id'] = $author_id;
			$where['form']      = array( 'like', '%"' . $item['id'] . '"%' );
			$count              = M( 'movies' )->where( $where )->count( 1 );

			$isClose = M( 'author_form' )->where( 'author_id = ' . $author_id . ' and form_id = ' . $item['id'] )->find();
			if ( $isClose ) {
				$saveData['number']   = $count;
				$saveData['add_time'] = time();
				M( 'author_form' )->where( 'id = ' . $isClose['id'] )->save( $saveData );
			} else {
				$saveData['author_id'] = $author_id;
				$saveData['form_id']   = $item['id'];
				$saveData['number']    = $count;
				$saveData['add_time']  = time();
				M( 'author_form' )->add( $saveData );
			}
		}

		return true;
	}

	//删除
	public function del() {
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '非法入口' ) );
		}

		$id = I( 'post.id' );
		if ( M( 'movies' )->where( 'id ="' . $id . '"' )->save( array( 'status' => 2 ) ) ) {//伪删除
			//更新作者影片数
			$data       = I( 'post.' );
			$authorData = M( 'author' )->where( 'id = ' . $data['author_id'] )->find();
			if ( $authorData ) {
				$moviesSum = M( 'movies' )->where( 'status != 2 and author_id =' . $data['author_id'] )->count();
				M( 'author' )->where( 'id = ' . $data['author_id'] )->save( [ 'movies_count' => $moviesSum ] );
			}

			$this->ajaxReturn( array( 'code' => 200, 'msg' => '删除成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '删除失败' ) );
		}
	}
	//end图解功能
	//图解数据
	public function comicData() {
		$order = I( 'order' );
		$sort  = I( 'sort' );
		if ( ! empty( $sort ) || ! empty( $order ) ) {//排序
			if ( $sort == 0 ) {
				$order = $order . ' asc';
			} else {
				$order = $order . ' desc';
			}
		} else {
			$order = 'm.add_time desc';
		}
		$this->assign( 'sort', $sort );

		$name = I( 'name' ); //按名称查询
		$map  = array();
		if ( ! empty( $name ) ) {
			$where['m.name']     = array( 'like', '%' . $name . '%' );
			$where['m.org_name'] = array( 'like', '%' . $name . '%', 'or' );
			$where['_logic']     = 'or';
			$map['_complex']     = $where;
		}
		$status = I( 'status', 2 ); //按状态查询
		$this->assign( 'status', $status );
		if ( $status != 2 ) {
			$map['status'] = $status;
		} else {
			$map['status'] = array( 'neq', 2 );
		}

		$form_id = I( 'form', 0 );
		$this->assign( 'form_id', $form_id );
		if ( $form_id != 0 ) {
			$map['form'] = array( "like", '%"' . $form_id . '"%' );
		}
		$rank = I( 'get.rank' );
		if ( $rank != '' ) {
			$map['rank'] = $rank;
			$this->assign( 'rankForm', $rank );
		} else {
			$this->assign( 'rankForm', 99 );
		}


//        $like = M('like')->alias('l')->where('m.id=l.movies_id')->field('count(1)')->buildSql(); //点赞
//        $consume = M('user_chapter')->alias('uc')->where('m.id=uc.movies_id')->field('sum(price)')->buildSql();//消费金币
//        $hits = M('stat_data')->alias('sd')->where('m.id=sd.main_id and main_type=1 and data_type = 1')->field('sum(num)')->buildSql();//浏览
//        $collect = M('stat_data')->alias('sd')->where('m.id=sd.main_id and main_type=1 and data_type = 3')->field('sum(num)')->buildSql();//收藏
		$chapterCount = M( 'chapter' )->where( 'movies_id = m.id and status=1' )->field( 'count(1)' )->buildSql();
//        $list = M('movies')->alias('m')->where($map)->field('id,name,hunt,'.$like.' `like`,'.$consume.' consume,'.$hits.' hits,'.$collect.' collect,'.$consume.'/'.$hits.' hot,m.price price,begin_pay,'.$chapterCount.' chapter_count,mold')->limit($page->firstRow,$page->listRows)->order($order)->select();

		import( 'Common.Lib.Page' );
		$count = M( 'movies_data' )->alias( 'md' )->join( 'left join yy_movies as m on m.id=md.movies_id' )->where( $map )->field( 'm.id id,m.name name,m.org_name org_name,m.hunt hunt,' . $like . ' `like`,md.consume consume,md.hits hits,md.collect collect,md.consume/md.hits hot,m.price price,m.begin_pay begin_pay,' . $chapterCount . ' chapter_count,m.mold mold,m.form form' )->count( 1 );
		$page  = new \Common\Page( $count, 20 );
		$list  = M( 'movies_data' )->alias( 'md' )
		                           ->join( 'left join yy_movies as m on m.id=md.movies_id' )->where( $map )
		                           ->field( 'm.id id,m.name name,m.org_name org_name,m.hunt hunt,' . 0 . ' `like`,md.consume consume,md.hits hits,md.collect collect,md.consume/md.hits hot,m.price price,m.begin_pay begin_pay,' . $chapterCount . ' chapter_count,m.mold mold,m.form form' )
		                           ->limit( $page->firstRow, $page->listRows )->order( $order )->select();

		$form = M( 'form' )->field( 'id,name' )->select();

		foreach ( $list as $k => &$v ) {
			if ( ! empty( $v['org_name'] ) ) {
				$v['org_name'] = "[原名:" . $v['org_name'] . ']';
			}
			$json = json_decode( $v['form'], true );
			$str  = '';
			foreach ( $form as $m ) {
				if ( in_array( $m['id'], $json ) ) {
					$str .= ' ' . $m['name'];
				}
			}
			$list[ $k ]['form'] = $str;
		}
		$this->assign( 'list', $list );

		$this->assign( 'form', $form );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	/**
	 * 影片上架发送
	 */
	public function sendMessage() {
		$db_id     = I( 'db_id' );
		$movies_id = I( 'movies_id' );
		$user_info = M( 'user_movie_search' )->where( 'db_id =' . $db_id )->field( 'user_id' )->select();
		if ( $user_info ) {
			$app_push     = new \AppAdmin\Controller\AppPushController();
			$successCount = 0;
			foreach ( $user_info as $user_id ) {
				$xgtoken = M( 'app_user_info' )->where( 'user_id =' . $user_id['user_id'] )->field( 'xg_token,platform,imei' )->find();
				if ( $xgtoken['imei'] && $xgtoken['xg_token'] ) {
					if ( $xgtoken['platform'] == 'iOS' ) {
						$request['title']        = '您求片的图解已经上架哦！';
						$request['introduction'] = '点击查看';
						$request['push_target']  = 1;
						$request['user_id']      = $user_id['user_id'];
						$request['push_type']    = 1;
						$request['content']      = $movies_id;
						$res                     = $app_push->pushIos( $request );
						if ( $res['code'] == 200 ) {
							$successCount ++;
						}
					} else {
						$request['title']        = '您求片的图解已经上架哦！';
						$request['introduction'] = '点击查看';
						$request['push_target']  = 1;
						$request['user_id']      = $user_id['user_id'];
						$request['push_type']    = 1;
						$request['content']      = $movies_id;
						$res                     = $app_push->pushAndroid( $request );
						if ( $res['code'] == 200 ) {
							$successCount ++;
						}
					}
				}
			}
			$totalUser = count( $user_info );
			$msg       = '成功' . $successCount . '条,失败' . ( $totalUser - $successCount ) . '条。';
			$this->ajaxReturn( [ 'code' => 200, 'msg' => $msg ] );
		} else {
			$this->ajaxReturn( [ 'code' => 0, 'msg' => '无用户求片记录' ] );
		}

	}

	//章节列表
	public function chapter() {
		$movies_id        = I( 'get.movies_id' );
		$map              = array();
		$map['movies_id'] = $movies_id;
		$map['status']    = array( 'neq', 2 );
		$chapter          = M( 'chapter' )->where( $map )->field( 'id,name,source_url,source_time,desc,price_type' )->order( 'sortrank asc' )->select(); //章节列表
		//默认价格
		$moviesData = M( 'movies' )->where( 'id ="' . $movies_id . '"' )->field( 'id,name,movies_type,price,mold,begin_pay' )->find();
		$Model      = M( 'chapter' );
		$count      = $Model->where( $map )->count( '1' );
		if ( $count > 0 ) {
			import( 'Common.Lib.Page' );
			$page   = new \Common\Page( $count, 20 );
			$voList = $Model->where( $map )->order( 'sortrank desc' )->limit( $page->firstRow, $page->listRows )
			                ->select();
			foreach ( $voList as $key => &$val ) {
				$seletArr['main_id']   = $val['id'];
				$seletArr['main_type'] = 2;
				$seletArr['data_type'] = 1;
				$clickNum              = M( 'stat_data' )->where( $seletArr )->field( 'num' )->find();
				$val['click_num']      = $clickNum['num'];
				if ( $moviesData['mold'] == 0 ) {
					$thePrice                     = $val['price_type'] ? '￥' . ( $val['price'] / 100 ) : '金币' . $val['price'];
					$voList[ $key ]['price_tips'] = $val['sortrank'] < $moviesData['begin_pay'] ? '免费章节' : '按章收费：价格（' . $thePrice . '）';
				} elseif ( $moviesData['mold'] == 1 ) {
					$voList[ $key ]['price_tips'] = '整本收费：价格(￥' . ( $moviesData['price'] / 100 ) . ')';
				} elseif ( $moviesData['mold'] == 2 ) {
					$voList[ $key ]['price_tips'] = '整本免费';
				}
			}
			$this->assign( 'list', $voList );
			$this->assign( 'page', $page->show() );
		}
		$this->assign( 'chapter', $chapter );
		$this->assign( 'movies_id', $movies_id );
		$map['status'] = array( 'eq', 1 );
		$is_ok         = M( 'chapter' )->where( $map )->count( 1 );

		$this->assign( 'movies', $moviesData );
		$this->assign( 'price', $moviesData['price'] );
		$this->assign( 'UpperNumber', $is_ok );
		$this->display();
	}

	//插入章节
	public function chapterAdd() {
		$data = I( 'post.' );

		$data['add_time'] = time();
		try {

			if ( $data['chapter_id'] == 0 ) {
				$data['sortrank'] = 1;
			} else {
				if ( $data['before_or_after'] == 1 ) {
					$sortrank = M( 'chapter' )->where( 'id = "' . $data['chapter_id'] . '"' )->getField( 'sortrank' );
				} else {
					$sortrank = M( 'chapter' )->where( 'id = "' . $data['chapter_id'] . '"' )->getField( 'sortrank' );
					$sortrank ++;
				}
				$data['sortrank'] = $sortrank;
				$where            = array(
					'movies_id' => $data['movies_id'],
					'sortrank'  => array( 'egt', $sortrank )
				);
				M( 'chapter' )->where( $where )->setInc( 'sortrank' );
			}
			$insert_id = M( 'chapter' )->add( $data );
//            M('movies')->where('id ="' . $data['movies_id'] . '"')->save(array('lastupdate' => $data['add_time']));
			if ( $insert_id ) {
				$this->success( '添加成功', '/back/Comic/chapter?movies_id=' . $data['movies_id'] );
			} else {
				$this->error( '添加失败' );
			}
		} catch ( Exception $exception ) {
			$this->error( '添加失败' );
		}
	}

	//图解章节上下架
	public function chapterSetStatus() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$id     = I( 'post.id' );
		$status = M( 'chapter' )->where( 'id ="' . $id . '"' )->getField( 'status' );
		if ( $status == 0 ) {
			$status = 1;
		} else {
			$status = 0;
		}
		if ( M( 'chapter' )->where( 'id ="' . $id . '"' )->save( array( 'status' => $status ) ) ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功', 'status' => $status ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}
	}

	//有影章节删除
	public function chapterDel() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$id = I( 'post.id' );

		$movies_id = M( 'chapter' )->where( 'id = "' . $id . '"' )->getField( 'movies_id' );
		$sortrank  = M( 'chapter' )->where( 'id = "' . $id . '"' )->getField( 'sortrank' );
		$where     = array(
			'movies_id' => $movies_id,
			'sortrank'  => array( 'gt', $sortrank )
		);
		M( 'chapter' )->where( $where )->setDec( 'sortrank' );

		if ( M( 'chapter' )->where( 'id ="' . $id . '"' )->save( array( 'status' => 2 ) ) ) {//伪删除
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '删除成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '删除失败' ) );
		}
	}

	//修改章节
	public function chapterEdit() {
		$data = I( 'post.' );

		if ( empty( $data['id'] ) ) {
			$this->error( '参数错误' );
		}

		try {
			if ( $data['id'] != $data['chapter_id'] ) {

				$sortrank = M( 'chapter' )->where( 'id = "' . $data['chapter_id'] . '"' )->getField( 'sortrank' );


				if ( $sortrank > $data['sortrank'] ) {
					if ( $data['before_or_after'] == 1 ) {
						$sortrank --;
					}
					$map = array(
						'movies_id' => $data['movies_id'],
						'sortrank'  => array( array( 'gt', $data['sortrank'] ), array( 'elt', $sortrank ) )
					);
					M( 'chapter' )->where( $map )->setDec( 'sortrank' );
				} else {
					if ( $data['before_or_after'] == 2 ) {
						$sortrank ++;
					}

					$map = array(
						'movies_id' => $data['movies_id'],
						'sortrank'  => array( array( 'lt', $data['sortrank'] ), array( 'egt', $sortrank ) )
					);
					M( 'chapter' )->where( $map )->setInc( 'sortrank' );
				}
				$data['sortrank'] = $sortrank;
			}
			M( 'chapter' )->save( $data );
			$this->success( '修改成功', '/back/Comic/chapter?movies_id=' . $data['movies_id'] );
		} catch ( Exception $exception ) {
			$this->error( '修改失败' );
		}
	}

	//章节图片
	public function chapterImg() {
		$chapter_id = I( 'get.chapter_id' );

		$map['chapter_id'] = $chapter_id;
		$map['status']     = array( 'neq', 2 );

		$chapterImg = M( 'chapterImage' )->where( $map )->order( 'sortrank asc' )->field( 'sortrank,id' )->select(); //章节列表
		$this->_list( 'chapterImage', $map, 'sortrank asc' );
		$this->assign( 'chapterImg', $chapterImg );
		$this->assign( 'chapter_id', $chapter_id );
		$ZiD             = M( 'chapter' )->where( 'id ="' . $chapter_id . '"' )->field( 'movies_id,sortrank,name' )->select();
		$movid           = ! empty( $ZiD[0]['movies_id'] ) ? $ZiD[0]['movies_id'] : "";
		$sortrank        = ! empty( $ZiD[0]['sortrank'] ) ? $ZiD[0]['sortrank'] : "";
		$chapterName     = ! empty( $ZiD[0]['name'] ) ? $ZiD[0]['name'] : "";
		$map             = array( 'movies_id' => $movid, 'sortrank' => array( 'lt', $sortrank ) );
		$preChapter      = M( 'Chapter' )->where( $map )->order( 'sortrank desc' )->find();
		$map['sortrank'] = array( 'gt', $sortrank );
		$nextChapter     = M( 'Chapter' )->where( $map )->order( 'sortrank asc' )->find();
		$this->assign( "maxpage", $nextChapter['id'] );
		$this->assign( "minpage", $preChapter['id'] );
		if ( count( $chapterImg ) >= 10 ) {
			$endId   = $chapterImg[ count( $chapterImg ) - 1 ]['id'];
			$beginId = $chapterImg[0]['id'];
		} else {
			$endId   = 0;
			$beginId = 0;
		}
		$maps['chapter_id'] = $chapter_id;
		$maps['status']     = 1;
		$chapterImgNum      = M( 'chapterImage' )->where( $maps )->count( 1 ); //章节列表
		$this->assign( 'chapter_name', $chapterName );
		$this->assign( 'endId', $endId );
		$this->assign( 'beginId', $beginId );
		$this->assign( 'UpperNumber', $chapterImgNum );
		$movies_id = M( 'chapter' )->where( 'id="' . $chapter_id . '"' )->getField( 'movies_id' ); //图解id
		$this->assign( 'movies_id', $movies_id );
		$this->display();
	}

	//插入章节图片
	public function chapterImgAdd() {
		$data = I( 'post.' );

		$data['add_time'] = time();
		try {

			if ( $data['chapter_id'] == 0 ) {
				$data['sortrank'] = 1;
			} else {
				if ( $data['before_or_after'] == 2 ) {
					$data['sortrank'] ++;
				}

				$where = array(
					'chapter_id' => $data['chapter_id'],
					'sortrank'   => array( 'egt', $data['sortrank'] )
				);
				M( 'chapterImage' )->where( $where )->setInc( 'sortrank' );
			}
			$insert_id = M( 'chapterImage' )->add( $data );
			if ( $insert_id ) {
				$this->success( '添加成功' );
			} else {
				$this->success( '添加失败' );
			}
		} catch ( Exception $exception ) {
			$this->error( '添加失败' );
		}
	}

	//图解章节图片上下架
	public function chapterImgSetStatus() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$id     = I( 'post.id' );
		$status = M( 'chapterImage' )->where( 'id ="' . $id . '"' )->getField( 'status' );
		if ( $status == 0 ) {
			$status = 1;
		} else {
			$status = 0;
		}
		if ( M( 'chapterImage' )->where( 'id ="' . $id . '"' )->save( array( 'status' => $status ) ) ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功', 'status' => $status ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}
	}

	//图解章节图片删除
	public function chapterImgDel() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$id = I( 'post.id' );

		$chapter_id = M( 'chapterImage' )->where( 'id = "' . $id . '"' )->getField( 'chapter_id' );
		$sortrank   = M( 'chapterImage' )->where( 'id = "' . $id . '"' )->getField( 'sortrank' );
		$where      = array(
			'chapter_id' => $chapter_id,
			'sortrank'   => array( 'gt', $sortrank )
		);
		M( 'chapterImage' )->where( $where )->setDec( 'sortrank' );

		if ( M( 'chapterImage' )->where( 'id ="' . $id . '"' )->save( array( 'status' => 2 ) ) ) {//伪删除
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '删除成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '删除失败' ) );
		}
	}

	//移动图片
	public function moveImg() {
		$before_or_after = I( 'before_or_after' );
		$id              = I( 'id' );
		$chapter_id      = M( 'chapterImage' )->where( 'id = "' . $id . '"' )->getField( 'chapter_id' ); //当前章节id
		$sortrank        = M( 'chapterImage' )->where( 'id = "' . $id . '"' )->getField( 'sortrank' ); //章节顺序
		$movies_id       = M( 'chapter' )->where( 'id = "' . $chapter_id . '"' )->getField( 'movies_id' ); //图解id

		$chapterImg = M( 'chapterImage' )->where( array(
			'chapter_id' => $chapter_id,
			'status'     => array( 'neq', 2 )
		) )->order( 'sortrank asc' )->field( 'id,sortrank' )->select(); //章节列表
		$endId      = $chapterImg[ count( $chapterImg ) - 1 ]['id'];
		$beginId    = $chapterImg[0]['id'];

		$chapterList   = M( 'chapter' )->where( array(
			'movies_id' => $movies_id,
			'status'    => array( 'neq', 2 )
		) )->field( 'id,sortrank' )->order( 'sortrank asc' )->select(); //列出该图解报有章节
		$chapterListId = array_column( $chapterList, 'id' ); //查找该章节所在位置
		$num           = array_search( $chapter_id, $chapterListId, true );
		if ( $before_or_after == 1 ) {
			$where = array(
				'chapter_id' => $chapter_id,
				'sortrank'   => array( 'gt', $sortrank )
			);
			M( 'chapterImage' )->where( $where )->setDec( 'sortrank' );
			if ( isset( $chapterList[ $num - 1 ] ) && $id == $beginId ) {
				$newChapterId = $chapterList[ $num - 1 ]['id'];
				$newSortrank  = M( 'chapterImage' )->where( array(
					'chapter_id' => $newChapterId,
					'status'     => array( 'neq', 2 )
				) )->order( 'sortrank desc' )->limit( '1' )->getField( 'sortrank' );
				$newSortrank ++;
			}
		} elseif ( $before_or_after == 2 ) {
			$where = array(
				'chapter_id' => $chapter_id,
				'sortrank'   => array( 'gt', $sortrank )
			);
			M( 'chapterImage' )->where( $where )->setDec( 'sortrank' );
			if ( isset( $chapterList[ $num + 1 ] ) && $id == $endId ) {
				$newChapterId = $chapterList[ $num + 1 ]['id'];
				$newSortrank  = 1;
				$where        = array(
					'chapter_id' => $newChapterId,
				);
				M( 'chapterImage' )->where( $where )->setInc( 'sortrank' );
			}
		} elseif ( $before_or_after == 3 ) {
			$newChapterId              = $chapter_id;
			$removeWhere['chapter_id'] = $chapter_id;
			$removeWhere['sortrank']   = array( 'lt', $sortrank );
			$Last                      = M( 'chapterImage' )->where( $removeWhere )->order( 'sortrank desc' )->find();
			if ( ! $Last ) {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '移动失败' ) );
			}
			$newSortrank = $Last['sortrank'];
			M( 'chapterImage' )->where( 'id =' . $Last['id'] )->save( array( 'sortrank' => $sortrank ) );
		} elseif ( $before_or_after == 4 ) {
			$newChapterId              = $chapter_id;
			$removeWhere['chapter_id'] = $chapter_id;
			$removeWhere['sortrank']   = array( 'gt', $sortrank );
			$next                      = M( 'chapterImage' )->where( $removeWhere )->order( 'sortrank asc' )->find();
			if ( ! $next ) {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '移动失败' ) );
			}
			$newSortrank = $next['sortrank'];
			M( 'chapterImage' )->where( 'id =' . $next['id'] )->save( array( 'sortrank' => $sortrank ) );
		}

		if ( ! empty( $newChapterId ) && ! empty( $newSortrank ) ) {
			M( 'chapterImage' )->where( 'id = "' . $id . '"' )->save( array(
				'chapter_id' => $newChapterId,
				'sortrank'   => $newSortrank
			) );
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '移动成功' ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '移动失败' ) );
		}
	}

	//修改章节
	public function chapterImgEdit() {
		$data = I( 'post.' );
		if ( empty( $data['id'] ) ) {
			$this->error( '参数错误' );
		}
		$oldSortrank = M( 'chapterImage' )->where( 'id = "' . $data['id'] . '"' )->getField( 'sortrank' );
		try {
			if ( $oldSortrank != $data['sortrank'] ) {

				if ( $data['sortrank'] > $oldSortrank ) {
					if ( $data['before_or_after'] == 1 ) {
						$data['sortrank'] --;
					}
					$map = array(
						'chapter_id' => $data['chapter_id'],
						'sortrank'   => array( array( 'gt', $oldSortrank ), array( 'elt', $data['sortrank'] ) )
					);
					M( 'chapterImage' )->where( $map )->setDec( 'sortrank' );
				} else {
					if ( $data['before_or_after'] == 2 ) {
						$data['sortrank'] ++;
					}

					$map = array(
						'chapter_id' => $data['chapter_id'],
						'sortrank'   => array( array( 'lt', $oldSortrank ), array( 'egt', $data['sortrank'] ) )
					);
					M( 'chapterImage' )->where( $map )->setInc( 'sortrank' );
				}
			}
			M( 'chapterImage' )->save( $data );
			$this->success( '修改成功' );
		} catch ( Exception $exception ) {
			$this->error( '修改失败' );
		}
	}

	//获取内容
	public function getContent() {
		$chapter_id  = I( 'id' );
		$map         = array(
			'chapter_id' => $chapter_id,
			'status'     => '1'
		);
		$where['id'] = $chapter_id;
		$movies_id   = M( 'chapter' )->where( $where )->field( 'movies_id,sortrank,name' )->find();

		$chapterFires['movies_id'] = $movies_id['movies_id'];
		$chapterFires['status']    = 1;
//        $chapterFires['id'] = $chapter_id;
		$chapterFires['sortrank'] = array( 'lt', $movies_id['sortrank'] );

		$chapter_first            = M( 'chapter' )->where( $chapterFires )->order( 'sortrank desc' )->getField( 'id' );
		$chapterFires['sortrank'] = array( 'gt', $movies_id['sortrank'] );
		$chapter_last             = M( 'chapter' )->where( $chapterFires )->order( 'sortrank asc' )->getField( 'id' );

		$h_chapter_first = $chapter_first ? $chapter_first : '';
		$h_chapter_last  = $chapter_last ? $chapter_last : '';
		$content         = M( 'chapterImage' )->where( $map )->order( 'sortrank asc' )->field( 'url,reading' )->select();
		if ( empty( $content ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '错误' ) );
		} else {
			$this->ajaxReturn( array(
				'code'     => 200,
				'content'  => $content,
				'last_id'  => $h_chapter_last,
				'first_id' => $h_chapter_first,
				'title'    => $movies_id['name']
			) );
		}
	}

	//批量修改图片格式
	public function editImages() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$movies_id = I( 'movies_id' );
		$chapter   = M( 'chapter' )->where( 'movies_id = "' . $movies_id . '"' )->field( 'id' )->select();
		$chapter   = array_column( $chapter, 'id' );
		$ci        = M( 'chapter_image' )->where( array( 'chapter_id' => array( 'in', $chapter ) ) )->select();
		foreach ( $ci as $v ) {
			$letter = strtolower( strrchr( $v['url'], '.' ) );
			if ( ! strrpos( $v['url'], '-inpainted' ) && $letter != '.gif' ) {
				$url          = str_replace( 'https://r2yp.h5youx.com', 'https://cdn-yp.' . C( 'DOMAIN' ), $v['url'] );
				$suffix       = basename( $url ); //获取文件名
				$fileName     = str_replace( $suffix, '', $url ); //获取文件储存位置
				$removeSuffix = explode( '.', $suffix ); //将文件名 和后缀名放入数组
				$hSuffix      = isset( $removeSuffix[1] ) ? '.' . $removeSuffix[1] : ''; //获取文件后缀
				$res          = $fileName . $removeSuffix[0] . '-inpainted' . $hSuffix;
				M( 'chapter_image' )->where( 'id = "' . $v['id'] . '"' )->save( array( 'url' => $res ) );
			}
		}
		$this->ajaxReturn( array( 'code' => 200, 'msg' => '批量修改成功' ) );
	}

	public function delectSuffix() {
		set_time_limit( 0 );
		if ( ! IS_AJAX ) {
			$res = array( 'code' => 201, 'res' => '非法入口' );
			$this->ajaxReturn( $res );
			exit();
		}
		$movies_id = I( 'movies_id' );
		if ( ! is_numeric( $movies_id ) ) {
			$res = array( 'code' => 201, 'res' => '参数错误' );
			$this->ajaxReturn( $res );
			exit();
		}
		try {
			$chapter    = M( 'chapter' )->where( 'movies_id = "' . $movies_id . '"' )->field( 'id' )->select();
			$chapterArr = array_column( $chapter, 'id' );
			$ci         = M( 'chapter_image' )->where( array( 'chapter_id' => array( 'in', $chapterArr ) ) )->select();
			$url        = 'http://resources.' . C( 'ADMIN_URL' ) . '/copy.php';
			$result     = true;
			$errorarr   = "";
			foreach ( $chapterArr as $chapterId ) {
				$data['movies_id']  = $movies_id;
				$data['chapter_id'] = $chapterId;
				$res                = http_request( $url, $data );
				$jsonArr            = json_decode( $res, true );
				if ( $jsonArr['code'] == 405 ) {
					$result     = false;
					$errorarr[] = $chapterId . ':没有写入的权限，请手动修改';
				} elseif ( $jsonArr['code'] == 406 ) {
					$result     = false;
					$errorarr[] = $chapterId . ':发生错误，请重试';
				} elseif ( $jsonArr['code'] != 200 ) {
					$result     = false;
					$errorarr[] = $chapterId . ':-' . $jsonArr['res'];
				}
			}
			if ( ! $result ) {
				$res = array( 'code' => 201, 'res' => $errorarr );
				$this->ajaxReturn( $res );
				exit();
			}
			foreach ( $ci as $v ) {
				$url          = $v['url'];
				$suffix       = basename( $url ); //获取文件名
				$fileName     = str_replace( $suffix, '', $url ); //获取文件储存位置
				$removeSuffix = explode( '.', $suffix ); //将文件名 和后缀名放入数组
				$res          = $fileName . $removeSuffix[0];
				M( 'chapter_image' )->where( 'id = "' . $v['id'] . '"' )->save( array( 'url' => $res ) );
			}
			M( 'movies' )->where( 'id =' . $movies_id )->save( array( 'img_status' => 1 ) ); //修改脚本图片状态
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '修改后缀成功' ) );
		} catch ( Exception $ex ) {
			$res = array( 'code' => 201, 'res' => $ex->getMessage() );
			$this->ajaxReturn( $res );
			exit();
		}
	}

	/**
	 *
	 */
	public function checkImages() {
		$id = I( 'movies_id' );
		if ( empty( $id ) || ! is_numeric( $id ) ) {
			$this->error( '图解ID错误' );
		}
		$movies     = M( 'movies' )->where( array( 'id' => $id ) )->field( 'id,name' )->find();
		$chapterIds = M( 'chapter' )->where( array( 'movies_id' => $id ) )->getField( 'id', true );
		if ( empty( $chapterIds ) ) {
			$this->error( '图解章节列表为空' );
		}
		$imageList = M( 'chapter_image' )->where( array(
			'chapter_id' => array( 'in', $chapterIds ),
			'status'     => 1
		) )->field( 'id,url,chapter_id' )->select();
		$list      = array();
		foreach ( $imageList as $key => $item ) {
			$item['url']  = strstr( $item['url'], "movies/" );
			$list[ $key ] = $item;
		}
		$this->assign( 'movies', $movies );
		$this->assign( 'imgs', $list );
		$this->display();
	}

	/**
	 * 增加限时免费时间
	 */
	public function addFree() {
		$post               = I( 'post.' );
		$start              = $post['movies_id'];
		$where['movies_id'] = $start;
		$res                = M( 'movies_free' )->where( $where )->count( 1 );
		if ( $res > 0 ) {
			$data['start_time'] = strtotime( $post['start_time'] );
			$data['end_time']   = strtotime( $post['end_time'] );
			$data['add_time']   = time();
			$res                = M( 'movies_free' )->where( 'movies_id = ' . $start )->save( $data );
			if ( ! $res ) {
				$this->ajaxReturn( [ 'code' => 0, 'data' => '修改失败' ] );
			} else {
				$this->ajaxReturn( [ 'code' => 200, 'data' => '修改成功' ] );
			}
		} else {
			$data['movies_id']  = $post['movies_id'];
			$data['start_time'] = strtotime( $post['start_time'] );
			$data['end_time']   = strtotime( $post['end_time'] );
			$data['add_time']   = time();
			$res                = M( 'movies_free' )->add( $data );
			if ( ! $res ) {
				$this->ajaxReturn( [ 'code' => 0, 'data' => '添加失败' ] );
			} else {
				$this->ajaxReturn( [ 'code' => 200, 'data' => '添加成功' ] );
			}
		}
	}

	/**
	 * 获取影片免费信息
	 */
	public function getMovies() {
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 0, 'mess' => '非法访问' ) );
		} else {
			$moviesId = I( 'post.moviesId' );
			if ( empty( $moviesId ) ) {
				$this->ajaxReturn( array( 'code' => 0, 'mess' => '电影id为空' ) );
			}
			$res = M( 'movies_free' )->where( 'movies_id = ' . $moviesId )->field( 'start_time,end_time' )->find();
			if ( empty( $res ) ) {
				$res['code'] = 201;
			} else {
				$res['start_time'] = date( "Y-m-d H:i:s", $res['start_time'] );
				$res['end_time']   = date( "Y-m-d H:i:s", $res['end_time'] );
				$res['code']       = 200;
			}
			$this->ajaxReturn( $res );
		}
	}

	/**
	 * 限时免费列表
	 *
	 * @param type
	 */
	public function freeIndex() {
		$movies_name = I( 'get.name' );
		$type        = I( 'get.type' );
		$select      = [];
		if ( ! empty( $type ) ) {
			if ( $type == 1 ) {
				$select['start_time'] = array( 'elt', time() );
				$select['end_time']   = array( 'egt', time() );
			} elseif ( $type == 2 ) {
				$select['start_time'] = array( 'gt', time() );
				$select['end_time']   = array( 'gt', time() );
			} elseif ( 3 ) {
				$select['start_time'] = array( 'lt', time() );
				$select['end_time']   = array( 'lt', time() );
			}
			$this->assign( 'type', $type );
		}
		//图解名称和原名搜索
		if ( ! empty( $movies_name ) ) {
			$where['name']     = array( 'like', '%' . $movies_name . '%' );
			$where['org_name'] = array( 'like', '%' . $movies_name . '%' );
			$where['_logic']   = 'or';
			$map['_complex']   = $where;
			$sqlSelect         = M( 'movies' )->where( $map )->field( 'id' )->select();
			$moviesIdArr       = '';
			if ( ! empty( $sqlSelect ) ) {
				foreach ( $sqlSelect as $value ) {
					$moviesIdArr .= $value['id'] . ',';
				}
				$moviesIdArr         = trim( $moviesIdArr, ',' );
				$select['movies_id'] = array( 'in', $moviesIdArr );
			}
			$this->assign( 'name', $movies_name );
		}
		$Model       = D( 'movies_free' );
		$sql         = M( 'movies as b' )->where( 'a.movies_id = b.id ' )->field( 'name' )->select( false );
		$sqlNickname = M( 'movies as c' )->where( 'a.movies_id = c.id ' )->field( 'org_name' )->select( false );
		$sqlHists    = M( 'movies_data as d' )->where( 'a.movies_id = d.movies_id ' )->field( 'hits' )->select( false );
		//取得满足条件的记录数
		$count = $Model->alias( 'a' )->where( $select )->count( '1' );
		if ( $count > 0 ) {
			import( 'Common.Lib.Page' );
			$page   = new \Common\Page( $count, 20 );
			$voList = $Model->alias( 'a' )->where( $select )->field( '(' . $sql . ') as movies_name,(' . $sqlHists . ') as hits ,a.id,(' . $sqlNickname . ') as org_name,a.start_time,a.end_time,a.add_time,a.movies_id' )->order( 'a.end_time DESC' )->limit( $page->firstRow,
				$page->listRows )->select();
			$this->assign( 'list', $voList );
			$this->assign( 'page', $page->show() );
		}
		$this->display();
	}

	/**
	 * 删除限时数据
	 */
	public function delFree() {
		if ( IS_AJAX ) {
			$freeId = I( 'post.id' );
			if ( empty( $freeId ) ) {
				$this->ajaxReturn( array( 'code' => 0, 'mess' => 'ID不能为空' ) );
			}
			$res = M( 'movies_free' )->where( 'id = ' . $freeId )->delete();
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'mess' => '删除成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'mess' => '删除失败' ) );
			}
		} else {
			$arr = array( 'code' => 0, 'mess' => '非法访问' );
			$this->ajaxReturn( $arr );
		}
	}

	/**
	 * 批量修改图解金币
	 */
	public function changeMoviesGold() {
		$price = I( 'post.price' );
		$id    = I( 'post.id' );
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 0, 'res' => '非法访问' ) );
		} elseif ( $id == '' || $price == '' || ! is_numeric( $price ) || ! is_numeric( $id ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'res' => '章节id为空或价格为空' ) );
		} else {
			$data['price'] = $price;
			$res           = M( 'chapter' )->where( array( 'movies_id' => $id ) )->save( $data );
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'res' => '修改成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'res' => '修改失败' ) );
			}
		}
	}

	/**
	 * 获取影片绑定的渠道和vip
	 */
	public function getChannel() {
		$movies_id                = I( 'get.movies_id' );
		$channel['data_type']     = 1;
		$channel['movies_id']     = $movies_id;
		$channel['end_time']      = array( array( 'egt', time() ), array( 'eq', 0 ), 'or' );
		$res                      = M( 'movies_channel' )->where( $channel )->field( 'data_id' )->select(); //渠道
		$channel['data_type']     = 2;
		$resvip                   = M( 'movies_channel' )->where( $channel )->field( 'data_id' )->select(); //vip
		$returnData['channel_id'] = '';
		if ( ! empty( $res ) ) {
			$channel_str = array();
			foreach ( $res as $value ) {
				$channel_str[] = $value['data_id'];
			}
			$returnData['channel_id'] = trim( implode( ',', $channel_str ), ',' );
		}
		$returnData['vip_id'] = '';
		if ( ! empty( $resvip ) ) {
			$member_str = array();
			foreach ( $resvip as $valuevip ) {
				$member_str[] = $valuevip['data_id'];
			}
			$returnData['vip_id'] = trim( implode( ',', $member_str ), ',' );
		}
		$return['code'] = 200;
		$return['res']  = $returnData;
		$this->ajaxReturn( $returnData );
	}

	/**
	 * 保存影片绑定的渠道和vip
	 */
	public function setChannel() {
		$channel_id     = I( 'get.channel_id' );
		$vip_id         = I( 'get.vip_id' );
		$comic_id       = I( 'get.comic_id' );
		$end_time       = I( 'get.end_time' );
		$vip_remove     = I( 'get.vip_remove' );
		$channel_remove = I( 'get.channel_remove' );
		if ( $vip_remove != '' ) {
			$vip_remove_explode       = explode( ',', $vip_remove );
			$vip_del_arr['data_id']   = array( 'in', $vip_remove_explode );
			$vip_del_arr['data_type'] = 2;
			$vip_del_arr['movies_id'] = $comic_id;
			M( 'movies_channel' )->where( $vip_del_arr )->delete();
		}
		if ( $channel_remove != '' ) {
			$channel_remove_explode       = explode( ',', $channel_remove );
			$channel_del_arr['data_id']   = array( 'in', $channel_remove_explode );
			$channel_del_arr['data_type'] = 1;
			$channel_del_arr['movies_id'] = $comic_id;

			M( 'movies_channel' )->where( $channel_del_arr )->delete();
		}
		$insertChannel = array();
		if ( $channel_id != '' ) {
			$channel_explode = explode( ',', $channel_id );
			foreach ( $channel_explode as $channel ) {
				$insertChannel[] = $channel;
			}
		}
		foreach ( $insertChannel as $value ) {
			$where1['movies_id']  = $comic_id;
			$where1['data_id']    = $value;
			$where1['data_type']  = 1;
			$resChannel           = M( 'movies_channel' )->where( $where1 )->find();
			$saveData             = $where1;
			$saveData['end_time'] = strtotime( $end_time );
			$saveData['add_time'] = time();
			if ( $resChannel ) {
				M( 'movies_channel' )->where( 'id = ' . $resChannel['id'] )->save( $saveData );
			} else {
				M( 'movies_channel' )->add( $saveData );
			}
		}
		$insertVip = array();
		if ( $vip_id != '' ) {
			$vip_explode = explode( ',', $vip_id );
			foreach ( $vip_explode as $vip ) {
				$insertVip[] = $vip;
			}
		}
		foreach ( $insertVip as $valuevip ) {
			$where2['movies_id']     = $comic_id;
			$where2['data_id']       = $valuevip;
			$where2['data_type']     = 2;
			$resVip                  = M( 'movies_channel' )->where( $where2 )->find();
			$saveDataVip             = $where2;
			$saveDataVip['end_time'] = strtotime( $end_time );
			$saveDataVip['add_time'] = time();
			if ( $resVip ) {
				M( 'movies_channel' )->where( 'id = ' . $resVip['id'] )->save( $saveDataVip );
			} else {
				M( 'movies_channel' )->add( $saveDataVip );
			}
		}
		$returnData['code'] = 200;
		$returnData['msg']  = '操作成功';
		$this->ajaxReturn( $returnData );
	}

	/**
	 * 图解绑定渠道数据
	 */
	public function comicChannel() {
		import( 'Common.Lib.Page' );
		$name  = I( 'get.name' );
		$type  = I( 'get.type' );
		$where = array();
		if ( ! empty( $name ) ) {
			$map['b.name']     = array( 'like', '%' . $name . '%' );
			$map['b.org_name'] = array( 'like', '%' . $name . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
		}
		if ( ! empty( $type ) ) {
			if ( $type == 2 ) {
				$where['a.end_time'] = array( array( 'lt', time() ), array( 'neq', 0 ), 'and' );
			} else {
				$where['a.end_time'] = array( array( 'egt', time() ), array( 'eq', 0 ), 'or' );
			}
			$this->assign( 'type', $type );
		}
		$count  = M( 'movies_channel as a' )->join( 'left join yy_movies as b on b.id = a.movies_id' )->where( $where )->count( 1 );
		$p      = new \Common\Page( $count, 20 );
		$movies = M( 'movies_channel as a' )
			->join( 'left join yy_movies as b on b.id = a.movies_id' )
			->where( $where )->limit( $p->firstRow,
				$p->listRows )->field( 'a.id,a.movies_id,a.data_type,a.data_id,a.end_time,a.add_time,b.name,b.org_name' )->order( 'id desc' )->select();
		foreach ( $movies as &$val ) {
			if ( $val['data_type'] == 1 ) {
				$val['nick_name'] = '渠道：' . M( 'channel' )->where( 'id =' . $val['data_id'] )->getField( 'nick_name' );
			} else {
				$val['nick_name'] = 'vip：' . M( 'member' )->where( 'uid =' . $val['data_id'] )->getField( 'user' );
			}
		}
		$this->assign( 'list', $movies );
		$this->assign( 'page', $p->show() );
		$this->display();
	}

	/**
	 * 删除限时数据
	 */
	public function delMoviesChannel() {
		if ( IS_AJAX ) {
			$freeId = I( 'post.id' );
			if ( empty( $freeId ) ) {
				$this->ajaxReturn( array( 'code' => 0, 'mess' => 'ID不能为空' ) );
			}
			$res = M( 'movies_channel' )->where( 'id = ' . $freeId )->delete();
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'mess' => '删除成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'mess' => '删除失败' ) );
			}
		} else {
			$arr = array( 'code' => 0, 'mess' => '非法访问' );
			$this->ajaxReturn( $arr );
		}
	}

	/**
	 * 获取章节详情
	 */
	public function getchapterImg() {
		$id         = I( 'get.id' );
		$chapterImg = M( 'chapterImage' )->where( 'id =' . $id )->find(); //章节列表
		$this->ajaxReturn( $chapterImg );
	}

	/**
	 * 设置热门推荐值、本周推荐值
	 */
	public function PromoteNumber() {
		$from = I( 'post.' );
		if ( $from['movies_id'] == '' || $from['number_type'] == '' || ! is_numeric( $from['number_type'] ) ) {
			$this->error( '参数错误' );
		} else {
			if ( $from['num_type'] == 'hot' ) {
				$save['hot_num'] = $from['number_type'];
			} else {
				$save['week_num'] = $from['number_type'];
			}
			$res = M( 'movies' )->where( 'id =' . $from['movies_id'] )->save( $save );
			if ( $res ) {
				if ( $from['num_type'] == 'hot' ) {
					M( 'movies' )->where( 'hot_num >=' . $from['number_type'] )->setInc( 'score', 1 );
				} else {
					M( 'movies' )->where( 'week_num >=' . $from['number_type'] )->setInc( 'score', 1 );
				}
			}
			if ( $res ) {
				$this->success( '设置成功' );
			}
		}
	}

	/**
	 * 查看热门推荐、本周推荐排序
	 */
	public function seeOrder() {
		$rank = I( 'get.rank' );
		if ( $rank != '' ) {
			$where['rank'] = array( 'elt', $rank );
			$this->assign( 'rankIndex', $rank );
		} else {
			$where['rank'] = array( 'elt', 6 );
			$this->assign( 'rankIndex', 6 );
		}
		$where['status'] = 1;
		$hot_res         = M( 'movies as a' )->where( $where )->limit( 0, 6 )
		                                     ->join( 'left join yy_showtime as b on a.showtime_id = b.id' )
		                                     ->field( 'a.name,a.cover,a.subtitle,a.tags,a.hot' )->order( 'a.hot desc' )->select();
		$level_res       = M( 'movies as a' )->where( $where )->limit( 0, 4 )
		                                     ->join( 'left join yy_showtime as b on a.showtime_id = b.id' )
		                                     ->field( 'a.name,a.banner,a.subtitle,a.order_num' )->order( 'a.order_num desc' )->select();
		$new_res         = M( 'movies as a' )->where( $where )->limit( 0, 6 )
		                                     ->join( 'left join yy_showtime as b on a.showtime_id = b.id' )
		                                     ->field( 'a.name,a.cover,a.subtitle,a.tags' )->order( 'a.lastupdate desc' )->select();

		$this->assign( 'list_level', $level_res );
		$this->assign( 'list_hot', $hot_res );
		$this->assign( 'list_new', $new_res );
		$this->display();
	}

	public function getMoreMovies() {
		$page = I( 'get.page' );
		$type = I( 'get.type' );
		$rank = I( 'get.rank' );
		if ( $type == 'hot' ) {
			if ( $rank != '' ) {
				$where['rank'] = array( 'elt', $rank );
			} else {
				$where['rank'] = array( 'elt', 6 );
			}
			$where['status'] = 1;
			$firstPage       = $page * 20;
			$hot_res         = M( 'movies as a' )->where( $where )->limit( $firstPage, 20 )
			                                     ->join( 'left join yy_showtime as b on a.showtime_id = b.id' )
			                                     ->field( 'a.name,a.actor,a.director,a.showtime_id,a.tags,a.hot as number,a.cover,a.subtitle,b.name as showtime' )->order( 'a.hot desc' )->select();
			if ( ! empty( $hot_res ) ) {
				$returnData['code'] = 200;
				$returnData['res']  = $hot_res;
				$this->ajaxReturn( $returnData );
			} else {
				$returnData['code'] = 200;
				$returnData['res']  = '没有了';
				$this->ajaxReturn( $returnData );
			}
		} else {
			if ( $rank != '' ) {
				$where['rank'] = array( 'elt', $rank );
			} else {
				$where['rank'] = array( 'elt', 6 );
			}
			$where['status'] = 1;
			$firstPage       = $page * 20;
			$hot_res         = M( 'movies as a' )->where( $where )->limit( $firstPage, 20 )
			                                     ->join( 'left join yy_showtime as b on a.showtime_id = b.id' )
			                                     ->field( 'a.name,a.actor,a.director,a.showtime_id,a.tags,a.order_num as number,a.cover,a.subtitle,b.name as showtime' )->order( 'a.order_num desc' )->select();
			if ( ! empty( $hot_res ) ) {
				$returnData['code'] = 200;
				$returnData['res']  = $hot_res;
				$this->ajaxReturn( $returnData );
			} else {
				$returnData['code'] = 200;
				$returnData['res']  = '没有了';
				$this->ajaxReturn( $returnData );
			}
		}
	}

	public function similarity() {
		$id = I( 'get.id' );
		$this->assign( 'movies_id', $id );
		$this->display();

	}

	public function getSimilarityData() {
		$movies_id             = I( 'get.id' );
		$where['s.movies_id']  = $movies_id;
		$where['s.similarity'] = [ 'gt', 10000 ];
		$model                 = M( '·movies_similarity as s' )->join( 'left join yy_movies as m on m.id = s.to_movies_id' )
		                                                       ->field( 's.to_movies_id as id,m.name,m.org_name' )
		                                                       ->where( $where )->select();
		$is_bei_sim            = M( 'movies_similarity' )->where( [
			'to_movies_id' => $movies_id,
			'similarity'   => [ 'gt', 10000 ]
		] )->field( 'movies_id' )->select();
		$not_in_id             = [];
		if ( $is_bei_sim ) {
			foreach ( $is_bei_sim as $movies_id_val ) {
				$not_in_id[] = $movies_id_val['movies_id'];
			}
			$pageNumber = I( 'p' );
			if ( $pageNumber == 1 || empty( $pageNumber ) ) {
				$is_select_search['status']      = 1;
				$is_select_search['online_time'] = [ 'lt', time() ];
				$is_select_search['id']          = [ 'in', $not_in_id ];
				$is_select_list                  = M( 'movies' )->where( $is_select_search )->field( 'name,id,org_name,1 as is_select' )
				                                                ->order( 'id desc' )
				                                                ->select();
			} else {
				$is_select_list = [];
			}

		} else {
			$is_select_list = [];
		}

		$not_in_id[]              = $movies_id;
		$where['s.similarity']    = [ 'elt', 10000 ];
		$is_sim                   = M( 'movies_similarity as s' )->where( $where )->find();
		$returnData['switch']     = empty( $is_sim ) ? false : true;
		$movies_where['b.status'] = 1;
		$movies_where['b.id']     = [ 'not in', $not_in_id ];
		if ( is_numeric( I( 'get.name' ) ) && ! empty( I( 'get.name' ) ) ) {
			$movies_where['b.id'] = I( 'get.name' );
		} elseif ( ! empty( I( 'get.name' ) ) ) {
			$movies_name_where['b.name']     = array( 'like', '%' . I( 'get.name' ) . '%' );
			$movies_name_where['b.org_name'] = array( 'like', '%' . I( 'get.name' ) . '%', 'or' );
			$movies_name_where['_logic']     = 'or';
			$movies_where['_complex']        = $movies_name_where;
		}
		$movies_where['b.online_time'] = [ 'lt', time() ];
		$movies_arr                    = M( 'movies as b' )->where( $movies_where )->count( 1 );
		$page                          = new AjaxPage( $movies_arr );
		$list                          = M( 'movies as b' )->where( $movies_where )->field( 'b.name,b.id,b.org_name' )
		                                                   ->limit( $page->firstRow,
			                                                   $page->listRows )->order( 'id desc' )
		                                                   ->select();
		$new_array                     = array_merge( $is_select_list, $list );
		$returnData['page']            = $page->show();
		$returnData['data']            = $new_array;
		$returnData['similarity_data'] = $model;
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	public function serSimilarity() {
		$set_id    = I( 'post.id' );
		$movies_id = I( 'post.movies_id' );

		$is_have['similarity'] = [ 'gt', 10000 ];
		$is_have['movies_id']  = $movies_id;
		$similarity_arr        = M( 'movies_similarity ' )->where( $is_have )->order( 'similarity desc' )->select();
		$similarity            = 10000;
		$array_column          = array_column( $similarity_arr, 'to_movies_id' );
		$delectarr             = array_diff( $array_column, $set_id );
		foreach ( $delectarr as $values ) {
			$del['movies_id']    = $movies_id;
			$del['to_movies_id'] = $values;
			M( 'movies_similarity ' )->where( $del )->delete();
		}
		foreach ( $set_id as $value ) {
			$add['movies_id']    = $movies_id;
			$add['to_movies_id'] = $value;
			$res                 = M( 'movies_similarity' )->where( $add )->find();
			if ( ! $res ) {
				$similarity ++;
				$add['similarity'] = $similarity;
				$add['add_time']   = time();
				M( 'movies_similarity' )->add( $add );
			}
		}
		$this->ajaxReturn( array( 'code' => 200, 'data' => '操作成功' ) );

	}

	/**
	 * 下载脚本
	 */
	public function downScript() {
		$id = I( 'movies_id' );
		if ( $id == '' ) {
			$this->ajaxReturn( array( 'code' => 500, 'msg' => '图解ID不能为空' ) );
		}
		$movies_info            = M( 'movies' )->where( 'id =' . $id )->find();
		$where['img.status']    = 1;
		$where['m.id']          = $id;
		$img_array              = M( 'chapter_image as img' )->where( $where )
		                                                     ->join( 'INNER JOIN yy_chapter AS c ON c.id = img.chapter_id' )
		                                                     ->join( 'INNER JOIN yy_movies AS m ON m.id = c.movies_id' )
		                                                     ->field( 'img.url as file,img.reading as script,img.sortrank' )->select();
		$down_file_content      = array(
			'movies_info'  => $movies_info,
			'script_array' => $img_array
		);
		$down_file_content_json = json_encode( $down_file_content );
		$fliename               = "YYscript.data";

		header( "Content-Type: application/octet-stream" );
		header( 'Content-Length: ' . filesize( $fliename ) ); //下载文件大小
		header( 'Content-Disposition: attachment; filename="' . $fliename . '"' );
		$fileObj = fopen( 'php://output', 'a' );
		fwrite( $fileObj, $down_file_content_json );
		fclose( $fileObj );
		exit();
	}

	/**
	 * 获取标签值
	 */
	public function getBadge() {
		$id = I( 'post.id' );
		if ( empty( $id ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'data' => '电影id为空' ) );
		}
		//获取数据
		$result = M( 'Movies' )->where( 'id =' . $id )->field( 'id,name,badge' )->find();
		if ( $result ) {
			if ( ! empty( $result['badge'] ) ) {
				$badge               = json_decode( $result['badge'], true );
				$result['badge_txt'] = $badge['txt'];
				$result['badge_bg']  = $badge['bg'];
				$result['code']      = 200;
			} else {
				$result['badge_txt'] = '';
				$result['badge_bg']  = '';
				$result['code']      = 201;
			}
		} else {
			$result['code'] = 202;
		}
		$this->ajaxReturn( array( 'code' => 200, 'data' => $result ) );
	}

	/**
	 * 编辑标签
	 */
	public function editBadge() {
		$data = I( 'post.' );
		if ( empty( $data['movies_id'] ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'data' => '电影id为空' ) );
		}
		$save = '';
		if ( $data['option'] == 'reset' ) {
			$save['badge'] = '';
		} elseif ( empty( $data['txt'] ) ) {
			$this->ajaxReturn( array( 'code' => 201, 'msg' => '标签名不能为空' ) );
		} else {
			$save['badge'] = json_encode( array( 'txt' => $data['txt'], 'bg' => $data['bg'] ) );
		}

		$res = M( 'movies' )->where( 'id =' . $data['movies_id'] )->save( $save );
		if ( $res ) {
			$returnData['code'] = 200;
			$returnData['msg']  = '操作成功';
			$this->ajaxReturn( $returnData );
		} else {
			$returnData['code'] = 0;
			$returnData['msg']  = '操作失败';
			$this->ajaxReturn( $returnData );
		}
	}

	/**
	 * 更新章节数
	 */
	public function updateChapters() {
		$data = I( 'post.' );
		if ( empty( $data['movies_id'] ) ) {
			$this->ajaxReturn( array( 'code' => 0, 'data' => '电影id为空' ) );
		}
		//获取数据
		$chapterCount = M( 'chapter' )->where( 'status != 2 and movies_id =' . $data['movies_id'] )->count( 1 );
		$res          = M( 'movies' )->where( 'id = ' . $data['movies_id'] )->save( [ 'total_chapters' => $chapterCount ] );
		if ( $res ) {
			$returnData['code'] = 200;
			$returnData['msg']  = '更新成功';
		} else {
			$returnData['code'] = 201;
			$returnData['msg']  = '更新完成';
		}
		$this->ajaxReturn( $returnData );
	}
}

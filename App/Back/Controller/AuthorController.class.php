<?php

/**
 * 作者管理功能
 *
 * 文件功能。
 * @author      tsj 作者
 * @version     1.0 版本号
 */

namespace Back\Controller;

use Think\Controller;

class AuthorController extends CommonController {

	public function index() {
		$nick_name = I( 'get.nick_name' );
		if ( ! empty( $nick_name ) ) {
			$map = array( 'nick_name' => array( 'like', '%' . $nick_name . '%' ) );
			$this->assign( 'nick_name', $nick_name );
		}
		$map['status'] = array( 'neq', '2' );
		$Model         = D( 'Author' );
		$page_size     = C( 'PAGE_LIST_SIZE' );

		$count = $Model->where( $map )->count( '1' );
		import( 'Common.Lib.Page' );
		$page   = new \Common\Page( $count, $page_size, $_GET );
		$voList = $Model->where( $map )->limit( $page->firstRow, $page->listRows )->order( 'id desc' )->select();
		$this->assign( 'list', $voList );
		$this->assign( 'nick_name', $nick_name );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	//添加作者
	public function doAddUser() {
		$data    = I( 'post.' );
		$Channel = D( 'Author' );
		$data    = $Channel->create( $data );
		if ( ! $Channel->create() ) {
			$this->error( $Channel->getError() );
		}
		$data['add_time'] = time();
		$insert_id        = $Channel->add( $data );
		if ( ! $insert_id ) {
			$this->error( '添加失败' );
		}
		$this->success( '添加成功', '/Back/Author' );
	}

	/**
	 * 分层金币
	 */
	public function getGold() {
		$author_id   = I( 'get.author_id' );
		$fenc        = M( 'author' )->where( array( 'id' => $author_id ) )->getField( 'commission_ratio' );
		$fencnumber  = $fenc / 100;
		$Cheat_gold  = M( 'movies_gold' )
			->alias( 'p' )
			->join( 'yy_movies as n on(p.movies_id=n.id and n.charging_time <= UNIX_TIMESTAMP(p.DAY))' )
			->where( 'n.author_id=' . $author_id )
			->sum( 'p.sumgold*(n.commission_ratio*0.01)' );
		$overGold    = M( 'movies_gold' )
			->alias( 'p' )
			->join( 'yy_movies as n on(p.movies_id=n.id)' )
			->where( 'n.author_id=' . $author_id )
			->sum( 'p.sumgold' );
		$author_gold = $Cheat_gold * $fencnumber * 0.1 * 0.98;

		$returnData['over_gold']   = $overGold ? round( $overGold, 2 ) : 0;
		$returnData['author_gold'] = $author_gold ? round( $author_gold, 2 ) : 0;
		$returnData['Cheat_gold']  = $Cheat_gold ? round( $Cheat_gold, 2 ) : 0;
		$this->ajaxReturn( $returnData );
	}

	/**
	 * 查看作者每月分成
	 */
	public function monthGold() {
		import( 'Common.Lib.Page' );

		$author_id  = I( 'author_id' );
		$keyword    = I( 'keyword' );
		$fenc       = M( 'author' )->where( array( 'id' => $author_id ) )->getField( 'commission_ratio' );
		$fencnumber = $fenc / 100;

		$map['n.author_id'] = $author_id;
		$moviesList         = M( 'movies' )->where( 'status !=2 and author_id =' . $author_id )->select();
		//查询条件
		if ( $keyword ) {
			if ( is_numeric( $keyword ) ) {
				$map['n.id'] = $keyword;
			} else {
				$where['n.name']     = array( 'like', '%' . $keyword . '%' );
				$where['n.org_name'] = array( 'like', '%' . $keyword . '%' );
				$where['_logic']     = 'or';
				$map['_complex']     = $where;
			}
		}
		//统计
		$countSql = M( 'movies_gold' )
			->alias( 'p' )
			->join( 'yy_movies as n on(p.movies_id=n.id and n.charging_time <= UNIX_TIMESTAMP(p. DAY))' )
			->where( $map )
			->field( "DATE_FORMAT(p.day, '%Y-%m') months" )
			->group( 'months' )->select();
		$count    = count( $countSql );
		$page     = new \Common\Page( $count, 20 );
		//数据查询
		if ( $keyword ) {
			if ( empty( $count ) ) {
				$list = array();
			} else {
				$movies           = M( 'movies as n' )->where( $map )->find();
				$commission_ratio = $movies['commission_ratio'];
				$list             = M( 'movies_gold' )
					->alias( 'p' )
					->join( 'yy_movies as n on(p.movies_id=n.id and n.charging_time <= UNIX_TIMESTAMP(p. DAY))' )
					->where( $map )
					->field( "sum(p.sumgold) as overgold,sum(p.sumgold)*($commission_ratio*0.01) as sum_amount,sum(p.sumgold)*0.1*0.98*($commission_ratio*0.01)*$fencnumber as author_amount,DATE_FORMAT(p.day, '%Y-%m') months" )
					->limit( $page->firstRow, $page->listRows )->order( 'months desc' )
					->group( 'months' )
					->select();
				$this->assign( 'movies', $movies );
			}
		} else {
			$list = M( 'movies_gold' )
				->alias( 'p' )
				->join( 'yy_movies as n on(p.movies_id=n.id and n.charging_time <= UNIX_TIMESTAMP(p. DAY))' )
				->where( $map )
				->field( "n.`name`,n.author,sum(p.sumgold) as overgold,sum(p.sumgold)*(n.commission_ratio*0.01) as sum_amount,sum(p.sumgold)*0.1*0.98*(n.commission_ratio*0.01)*$fencnumber as author_amount,DATE_FORMAT(p.day, '%Y-%m') months" )
				->limit( $page->firstRow, $page->listRows )->order( 'months desc' )
				->group( 'months' )
				->select();
		}

		foreach ( $list as $key => $item ) {
			$where['author_id']      = $author_id;
			$where['month']          = array( 'like', $item['months'] . '%' );
			$isClose                 = M( 'author_close' )->where( $where )->find();
			$list[ $key ]['isClose'] = $isClose ? true : false;
		}

		$this->assign( 'list', $list );
		$this->assign( 'page', $page->show() );
		$this->assign( 'author_id', $author_id );
		$this->assign( 'keyword', $keyword );
		$this->assign( 'moviesList', $moviesList );
		$this->display();

	}

	//ajax编辑
	public function ajaxedit() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$post        = I( 'get.' );
		$where['id'] = $post['id'];
		$res         = M( 'Author' )->where( $where )->field( 'nick_name,commission_ratio,id' )->find();
		if ( $res ) {
			$this->ajaxReturn( array( 'code' => 200, 'data' => $res ) );
		}
	}

	public function ajax_commission() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$where['id'] = I( 'get.id' );
		if ( $data = M( 'Author' )
			->where( $where )->field( 'id,nick_name,email,account,tel,status,commission_ratio,tags,avatar,wechat_num,qq_num,desc' )->find() ) {
			$this->ajaxReturn( array( 'code' => 200, 'data' => $data ) );
		}
		$this->ajaxReturn( array( 'code' => 0, 'data' => '获取失败' ) );
	}

	//修改比例分成
	public function edit_commission_ratio() {
		if ( ! IS_POST ) {
			return;
		}
		$post                     = I( 'post.' );
		$data['commission_ratio'] = $post['commission_ratio'];
		$res                      = M( 'Author' )->where( [ 'id' => $post['id'] ] )->setField( $data );
		if ( $res ) {
			$this->success( '修改成功', U( 'index' ) );
			die;
		} else {
			$this->error( '参数错误！' );
			die;
		}
	}

	//删除作者Y-m-d信息
	public function doDelUser() {
		if ( ! IS_AJAX ) {
			return;
		}
		$Channel    = D( 'Author' );
		$channel_id = I( 'post.channel_id' );
		$bool       = $Channel->where( [ 'id' => $channel_id ] )->setField( 'status', 2 ); //不直接删除，伪删除
		if ( $bool ) {
			$this->ajaxReturn(
				array(
					'code'     => 0,
					'msg'      => 'ok',
					'jump_url' => U( 'Author/index' )
				)
			);
		} else {
			$this->ajaxReturn(
				array(
					'code' => 1,
					'msg'  => 'error',
				)
			);
		}
	}

	//修改作者Y-m-d密码
	public function doResetPwd() {
		$Channel = D( 'Author' );
		if ( false === $Channel->field( 'password,id' )->create() || false === $Channel->save() ) {
			$this->error( $Channel->getError() );
		}
		$this->success( '修改成功' );
	}

	//修改作者Y-m-d信息
	public function doEditUser() {
		$data = I( 'post.' );

		if ( empty( $data ) ) {
			$this->redirect( 'Author/index' );
		}
		$ChannelUser = D( 'Author' );
		if ( IS_POST ) {
			if ( $ChannelUser->save( $data ) === false ) {

				$this->error( '编辑失败' );
			}
			$this->success( '编辑成功' );
		}
	}

	/**
	 * 查看支付信息
	 */
	public function seePay() {
		$id = I( 'get.id' );
		if ( empty( $id ) ) {
			exit( '非法访问' );
		} else {
			$authorAccount             = M( "Author_account" )->where( "author_id = '$id'" )->find();
			$arr                       = explode( " ", $authorAccount['yh_position'] );
			$authorAccount['province'] = $arr[0];
			$authorAccount['city']     = $arr[1];
			$authorAccount['country']  = $arr[2];
			$this->assign( "authorAccount", $authorAccount );
			$this->assign( "id", $id );

			$this->display();
		}
	}

	/**
	 * 提交支付信息
	 */
	public function editAccounts() {
		$returnData          = array(
			'code' => 0,
			"msg"  => "提交失败",
		);
		$data                = I( "post." );
		$author_id           = $data['author_id'];
		$data['yh_position'] = $data['province'] . " " . $data['city'] . " " . $data['country'];
		unset( $data['province'] );
		unset( $data['city'] );
		unset( $data['country'] );
		$authorAccount = M( "Author_account" )->where( "author_id = '$author_id'" )->find();
		if ( $authorAccount ) {
			$res = M( "Author_account" )->where( "author_id = '$author_id'" )->save( $data );
		} else {
			$data['author_id'] = $author_id;
			$res               = M( "Author_account" )->add( $data );
		}
		if ( $res !== false ) {
			$returnData['code'] = 1;
			$returnData['msg']  = "修改成功,二次修改请联系管理员";
		}
		$this->ajaxReturn( $returnData );
	}

	/**
	 * 修复数据
	 */
	public function repairData() {
		$data = I( 'post.' );
		if ( ! $data['author_id'] ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => 'ID不能为空' ) );
		}

		if ( $data['type'] == 'hits' ) {
			//浏览量
			$hits = M( 'movies' )->where( 'author_id = ' . $data['author_id'] )->sum( 'hits' );
			$res  = M( 'author' )->where( 'id = ' . $data['author_id'] )->save( array( 'hits' => $hits ) );
		} elseif ( $data['type'] == 'like_num' ) {
			//点赞量
			$like_num = M( 'movies' )->where( 'author_id = ' . $data['author_id'] )->sum( 'like_num' );
			$res      = M( 'author' )->where( 'id = ' . $data['author_id'] )->save( array( 'like_num' => $like_num ) );
		} elseif ( $data['type'] == 'collect_num' ) {
			//收藏量
			$moviesList = M( 'movies as m' )->join( 'left join yy_movies_data as md on m.id = md.movies_id' )
			                                ->where( 'm.author_id = ' . $data['author_id'] )
			                                ->field( 'md.movies_id,md.collect' )
			                                ->select();
			$sum        = 0;
			foreach ( $moviesList as $value ) {
				$sum += $value['collect'];
			}
			$res = M( 'author' )->where( 'id = ' . $data['author_id'] )->save( array( 'collect_num' => $sum ) );
		} elseif ( $data['type'] == 'tags' ) {
			//作者标签统计
			$formlist = M( 'form' )->select();
			foreach ( $formlist as $item ) {
				$where = $saveData = array();

				$where['status']    = 1;
				$where['author_id'] = $data['author_id'];
				$where['form']      = array( 'like', '%"' . $item['id'] . '"%' );
				$count              = M( 'movies' )->where( $where )->count( 1 );

				$isClose = M( 'author_form' )->where( 'author_id = ' . $data['author_id'] . ' and form_id = ' . $item['id'] )->find();
				if ( $isClose ) {
					$saveData['number']   = $count;
					$saveData['add_time'] = time();
					$res                  = M( 'author_form' )->where( 'id = ' . $isClose['id'] )->save( $saveData );
				} else {
					$saveData['author_id'] = $data['author_id'];
					$saveData['form_id']   = $item['id'];
					$saveData['number']    = $count;
					$saveData['add_time']  = time();
					$res                   = M( 'author_form' )->add( $saveData );
				}
			}
		} else {
			$res = false;
		}
		$this->ajaxReturn( array( 'code' => $res ? 200 : 0, 'msg' => $res ? '修复成功' : '操作失败' ) );
	}

	public function authorList() {
		$list = M( 'author' )->where( 'status != 2' )->field( 'id' )->select();

		$ids = array_column( $list, 'id' );
		$this->ajaxReturn( $ids );
	}

	/**
	 * 修复所有数据
	 */
	public function repairAuthor() {
		$author_id = I( 'author_id' );
		if ( ! $author_id ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => 'ID不能为空' ) );
		}

		$where       = array( 'author_id' => $author_id );
		$hits        = M( 'movies' )->where( $where )->sum( 'hits' );
		$like_num    = M( 'movies' )->where( $where )->sum( 'like_num' );
		$moviesList  = M( 'movies as m' )->join( 'left join yy_movies_data as md on m.id = md.movies_id' )
		                                 ->where( 'm.author_id = ' . $author_id )
		                                 ->field( 'md.movies_id,md.collect' )
		                                 ->select();
		$collect_num = 0;
		foreach ( $moviesList as $value ) {
			$collect_num += $value['collect'];
		}

		$data['hits']        = intval( $hits );
		$data['like_num']    = intval( $like_num );
		$data['collect_num'] = intval( $collect_num );

		$res = M( 'author' )->where( 'id = ' . $author_id )->save( $data );
		$this->ajaxReturn( array( 'code' => 200, 'msg' => 'ID：' . $author_id . '修复完成' ) );
	}

	/**
	 * 结算操作
	 */
	public function settlement() {
		$data = I( 'post.' );

		if ( ! $data['author_id'] ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '作者ID不能为空' ) );
		}

		$where['author_id'] = $data['author_id'];
		$where['month']     = array( 'like', $data['month'] . '%' );

		$isClose = M( 'author_close' )->where( $where )->find();
		if ( ! $isClose ) {
			$data['month']    = date( 'Y-m-d', strtotime( $data['month'] ) );
			$data['sumgold']  = floor( $data['sumgold'] );
			$data['execgold'] = floor( $data['execgold'] );
			$data['add_time'] = time();

			$res = M( 'author_close' )->add( $data );
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '结算成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '结算失败' ) );
			}
		}
	}

	/**
	 * 作者标签统计
	 */
	public function authorFormUpdate() {
		$update = I( 'update' );
		if ( $update == 'update' ) {
			$authorList = M( 'author' )->where( 'status = 1' )->select();
			$formList   = M( 'form' )->select();

			$data = array();
			foreach ( $authorList as $key => $value ) {
				foreach ( $formList as $k => $item ) {
					$where              = $addData = array();
					$where['author_id'] = $value['id'];
					$where['form']      = array( 'like', '%"' . $item['id'] . '"%' );
					$count              = M( 'movies' )->where( $where )->count( 1 );

					$addData['author_id'] = $value['id'];
					$addData['form_id']   = $item['id'];
					$addData['number']    = intval( $count );
					$addData['add_time']  = time();
					$data[]               = $addData;
				}
			}
			$res = M( 'author_form' )->addAll( $data );
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '数据插入成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '数据插入失败' ) );
			}
		} elseif ( $update == 'del' ) {
			$res = M( 'author_form' )->where( 'id != 0' )->delete();
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '删除成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '删除失败' ) );
			}
		}
	}
}

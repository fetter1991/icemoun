<?php

namespace Back\Controller;

use Back\Model\ServerTuiModel;
use Common\Lib\AjaxPage;

/**
 * 推送管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/27
 * Time: 16:23
 * Class PushController
 * Edit 2019.11.12
 * @package Back\Controller
 */
class PushController extends CommonController {

	/**
	 * 推送列表
	 */
	public function index() {
		$channel = D( 'channel' )->field( 'id,nick_name' )->select();
		$where   = array();

		$channel_id = I( 'get.channel_id', 0 );
		$channel_id != 0 && $where['p.channel_id'] = $channel_id;

		$status = I( 'get.status', 2 );
		$status != 2 && $where['p.status'] = $status;


		$stime = I( 'get.start_time', '' );
		$stime != '' && $where['p.send_time'] = array( 'like', "%$stime%" );;


		import( 'Common.Lib.Page' );
		$count = M( 'push as p' )
			->join( 'left join yy_channel as c  on p.channel_id=c.id' )
			->where( $where )->count();
		$page  = new \Common\Page( $count, 20 );
		$data  = M( 'push as p' )
			->join( 'left join yy_channel as c  on p.channel_id=c.id' )
			->where( $where )
			->field( 'p.title,p.template_title,p.send_time,p.status,c.nick_name' )
			->order( 'p.add_time desc' )
			->limit( $page->firstRow . ',' . $page->listRows )
			->select();

		$this->assign( 'ktime', $stime );
		$this->assign( 'status', $status );
		$this->assign( 'channel_id', $channel_id );
		$this->assign( 'data', $data );
		$this->assign( 'channel', $channel );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	/**
	 * 智能推送列表
	 */
	public function intelligentPush() {
		$this->display();
	}

	public function getServerInfo() {
		$selCategory = I( 'get.' );
		if ( ! empty( $selCategory['server_id'] ) ) {
			if ( is_numeric( $selCategory['server_id'] ) ) {
				$where_str['tui.id'] = $selCategory['server_id'];
			} else {
				$where_str['tui.title'] = [ 'like', '%' . $selCategory['server_id'] . '%' ];
			}
		}


		$movies_count = M( 'service_tui' )->alias( 'tui' )->where( $where_str )->count( 1 );
		$page         = new AjaxPage( $movies_count );
		$list         = M( 'service_tui' )->alias( 'tui' )
		                                  ->where( $where_str )
		                                  ->order( 'sortrank asc' )
		                                  ->limit( $page->firstRow, $page->listRows )
		                                  ->field( 'tui.id,tui.title,tui.yy2c,tui.add_time,tui.desc,tui.img_url,tui.status,tui.rank,tui.sortrank' )
		                                  ->select();
		$returnList   = array();
		foreach ( $list as $key => $value ) {
			$returnList[ $key ]             = $value;
			$returnList[ $key ]['yy2c']     = $this->analysisYy2c( $value['yy2c'] );
			$returnList[ $key ]['add_time'] = date( 'Y-m-d H:i:s', $value['add_time'] );
		}
		$returnData['page'] = $page->show();
		$returnData['data'] = $returnList;
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	/**
	 * @param $yy2c
	 *
	 * @return string
	 */
	public function analysisYy2c( $yy2c ) {
		$json_yy2c  = json_decode( $yy2c, true );
		$push_count = '';
		switch ( $json_yy2c['a'] ) {
			case 1:
				$movies_id  = $json_yy2c['p']['mid'];
				$movies     = M( 'movies' )->where( 'id =' . $movies_id )->field( 'name' )->find();
				$push_count = 'ID:' . $movies_id . '<br>图解名称：' . $movies['name'];
				break;
			case 2:
				$push_count = '打开充值中心';
				break;
			case 3:
				$push_count = '打开弹窗提示：' . $json_yy2c['p']['content'];
				break;
			case 4:
				$active_id  = $json_yy2c['p']['aid'];
				$active     = M( 'activity' )->where( 'id =' . $active_id )->field( 'title' )->find();
				$push_count = 'ID:' . $active_id . '<br>活动名称：' . $active['title'];
				break;
			case 5:
				$topic_id   = $json_yy2c['p']['tid'];
				$topic      = M( 'topic' )->where( 'id =' . $topic_id )->field( 'name' )->find();
				$push_count = 'ID:' . $topic_id . '<br>专辑名称：' . $topic['name'];
				break;
			case 100:
				$topic_id   = $json_yy2c['p']['url'];
				$push_count = '打开URL：' . $topic_id;
				break;
			default:
				break;
		}

		return $push_count;
	}

	/**
	 * 添加智能推送
	 */
	public function add() {
		$postData = I( 'post.' );
		$model    = new ServerTuiModel();
		if ( ! $model->create() ) {
			// 如果创建失败 表示验证没有通过 输出错误提示信息
			$this->ajaxReturn( array( 'code' => 500, 'msg' => $model->getError() ) );
		} else {
			$res = $model->add();
			if ( $res ) {
				if ( $postData['max_sortrank'] == 1 ) {
					$this->rankEdit( $postData['sortrank'], $res );
				}
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '添加成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 500, 'msg' => '添加失败' ) );
			}
		}
	}

	/**
	 * 编辑智能推送
	 */
	public function edit() {
		$postData = I( 'post.' );
		$model    = new ServerTuiModel();
		if ( ! $model->create() ) {
			// 如果创建失败 表示验证没有通过 输出错误提示信息
			$this->ajaxReturn( array( 'code' => 500, 'msg' => $model->getError() ) );
		} else {
			$res = $model->save();
			if ( $res ) {
				$this->rankEdit( $postData['sortrank'], $postData['id'] );
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '修改成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 500, 'msg' => '修改失败' ) );
			}
		}
	}

	/**
	 * 修改排序
	 */
	public function rankEdit( $sortrank, $id ) {
		$isExist = M( 'service_tui' )->where( 'sortrank = ' . $sortrank )->find();
		if ( $isExist ) {
			$list = M( 'service_tui' )->where( 'sortrank >= ' . $sortrank . ' and id !=' . $id )->select();
			foreach ( $list as $key => $value ) {
				M( 'service_tui' )->where( 'id = ' . $value['id'] )->setInc( 'sortrank' );
			}
		}
	}

	/**
	 * 删除智能推送
	 */
	public function del() {
		$id = I( 'id' );
		if ( ! $id ) {
			$this->ajaxReturn( [ 'code' => 0, 'data' => '参数错误' ] );
		}
		$model  = new ServerTuiModel();
		$result = $model->where( [ 'id' => $id ] )->delete();
		if ( $result ) {
			$this->ajaxReturn( [ 'code' => 200, 'data' => '删除成功' ] );
		} else {
			$this->ajaxReturn( [ 'code' => 0, 'data' => '删除错误' ] );
		}
	}

	/**
	 * 获取智能上下架
	 */
	public function setStatus() {
		$id = I( 'id' );
		if ( ! $id ) {
			$this->ajaxReturn( [ 'code' => 0, 'data' => '参数错误' ] );
		}
		$model       = new ServerTuiModel();
		$movies_info = $model->where( 'id ="' . $id . '"' )->field( 'status' )->find();
		$status_get  = $movies_info['status'];
		$status      = $status_get == 0 ? 1 : 0;
		$result      = $model->where( [ 'id' => $id ] )->save( [ 'status' => $status ] );
		if ( $result ) {
			$this->ajaxReturn( [ 'code' => 200, 'data' => '成功' ] );
		} else {
			$this->ajaxReturn( [ 'code' => 0, 'data' => '失败' ] );
		}
	}

	/**
	 * 获取智能推送详情
	 */
	public function changeServer() {
		$id = I( 'id' );
		if ( ! $id ) {
			$this->ajaxReturn( [ 'code' => 0, 'data' => '参数错误' ] );
		}
		$model  = new ServerTuiModel();
		$result = $model->where( [ 'id' => $id ] )->find();
		$this->ajaxReturn( [ 'code' => 200, 'data' => $result ] );
	}

}

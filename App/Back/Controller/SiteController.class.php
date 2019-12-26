<?php

namespace Back\Controller;

use Common\Page;
use Think\Controller;
use Common\Lib\Wethird\Weixin;
use AppAdmin\Controller\PushSettingController;

class SiteController extends CommonController {
	public function index() {
		$this->display();
	}

	//后台用户反馈列表
	public function feedback() {
		$channellist = M( 'channel' )->select();
		$this->assign( 'channellist', $channellist );
		//按照渠道搜索
		if ( ! empty( I( 'get.val' ) ) ) {
			$where['channel_id'] = I( 'get.val' );
			$this->assign( 'val', I( 'get.val' ) );
		}
		//按照处理状态搜索
		$status = I( 'get.status' );
		if ( is_numeric( $status ) ) {
			if ( $status == 0 || $status == 1 ) {
				$where['status'] = $status;
			}
			$this->assign( 'status', I( 'get.status' ) );
		}

		//模糊搜索
		if ( ! empty( trim( I( 'get.user_id' ) ) ) ) {
			$where['user_id'] = trim( I( 'get.user_id' ) );
			$this->assign( 'user_id', trim( I( 'get.user_id' ) ) );
		}
		//所有渠道
		$count = M( 'idea' )->where( $where )->count();// 查询满足要求的总记录数
		import( 'Common.Lib.Page' );
		$p    = new Page( $count, 20 );
		$show = $p->show();// 分页显示输出
		// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
		$list = M( 'idea' )
			->where( $where )
			->order( 'add_time desc' )
			->field( 'yy_idea.id,yy_idea.content,yy_idea.user_id,yy_idea.user_id,yy_idea.type,yy_idea.add_time,yy_idea.status,touch' )
			->limit( $p->firstRow, $p->listRows )
			->select();
		foreach ( $list as &$vlaue ) {
			$ideaWhere['idea_id'] = $vlaue['id'];
			$ideaWhere['user_id'] = $vlaue['user_id'];
			$vlaue['message']     = M( 'user_message' )->where( $ideaWhere )->getField( 'content' );
		}
		$this->assign( 'count', $count );
		$this->assign( 'page', $show );// 赋值分页输出
		if ( empty( $list ) ) {
			$this->assign( 'flag', 0 );
		} else {
			$this->assign( 'flag', 1 );
		}
		$this->assign( 'data', $list );// 赋值数据集
		$this->display(); // 输出模板
	}

	//处理用户反馈建议
	public function handler() {
		$data = I( 'post.' );
		if ( ACTION_NAME == 'handler' ) {
			$data['type'] = 0;
		}
		$idea_id = $data['idea_id'];
		$user_id = $data['user_id'];


		$data['reply_time'] = time();
		$inserId            = M( 'user_message' )->add( $data );
		$handler            = M( 'idea' )->where( 'id=' . $idea_id )->setField( 'status', 1 );
		if ( $user_id == 0 ) {
			$res['code']     = 0;
			$res['msg']      = 'ok';
			$res['data']     = $data;
			$res['jump_url'] = U( 'Site/feedback' );
			$this->ajaxReturn( $res );
		}
		M( 'user_info' )->where( 'user_id=' . $user_id )->setField( 'is_newmsg', 1 );
		if ( $inserId && $handler ) {
			$user   = M( 'user' )->where( 'id=' . $user_id )->field( 'open_id,channel_id' )->find();
			$config = M( 'channel' )->where( 'id =' . $user['channel_id'] )->field( 'appid,domen' )->find();
			if ( empty( $config['appid'] ) ) {
				$this->ajaxReturn( array( 'code' => 0, 'errmsg' => 'appid为空', 'nick_name' => $config['nick_name'] ) );
			}
			$wx        = new Weixin( $config['appid'] );
			$index_url = ! empty( $config['domen'] ) ? $config['domen'] : C( 'DOMAIN' );
			$str       = "<a href='https://" . $config['appid'] . "." . $index_url . "/index.php?m=Home&c=UC&a=index&channel=" . $user['channel_id'] . "'>您反馈的信息有新的回复。</a>";
			$wx->send_custom_message( $user['open_id'], 'text', $str );

			$res['code']     = 0;
			$res['msg']      = 'ok';
			$res['data']     = $data;
			$res['jump_url'] = U( 'Site/feedback' );

			$pushSever = new PushSettingController();
			$pushSever->handlePush( '1013', $inserId );
		} else {
			$res['code']     = 1;
			$res['msg']      = 'error';
			$res['data']     = [];
			$res['jump_url'] = U( 'Site/feedback' );
		}
		$this->ajaxReturn( $res );
	}

	/**
	 * 获取评论详情
	 */
	public function getSiteInfo() {
		$id  = I( 'get.id' );
		$uid = I( 'get.uid' );
		$res = M( 'idea as a' )->where( 'a.id =' . $id )
		                       ->join( 'left join yy_user_message as b on a.id = b.idea_id and a.user_id = b.user_id ' )
		                       ->field( 'b.content as message,a.content,a.touch,a.user_id' )->find();
		$this->ajaxReturn( $res );
	}

	/**
	 * 举报界面
	 */
	public function report() {
		$count = M( 'user_tips' )->count( 1 ); //总数
		import( 'Common.Lib.Page' );
		$p    = new Page( $count, 20 );
		$show = $p->show();// 分页显示输出
		$this->assign( 'count', $count );
		$this->assign( 'page', $show );// 赋值分页输出
		$list = M( 'user_tips as tips' )
			->join( 'left join yy_user_info as info on info.user_id = tips.user_id' )
			->join( 'left join yy_chapter as chapter on chapter.id = tips.chapter_id' )
			->join( 'left join yy_movies as movies on movies.id = chapter.movies_id' )
			->field( 'info.nick_name,tips.id,tips.comments,tips.user_id,tips.add_time,chapter.name,movies.name as movies_name,movies.org_name,chapter.sortrank,movies.begin_pay' )
			->order( 'add_time DESC' )->limit( $p->firstRow, $p->listRows )->select();
		$this->assign( 'data', $list );// 赋值数据集
		$this->display(); // 输出模板
	}


}

?>
<?php

namespace Back\Controller;

class FaqController extends CommonController {

	public function index() {
		import( 'Common.Lib.Page' );
		$faq                   = M( 'faq_category' )->where( 'status = 1' )->select();
		$from                  = I( 'get.' );
		$where                 = array();
		$where['faq.status']   = 1;
		$where['faq.question'] = array( 'like', '%' . $from['question'] . '%' );
		$this->assign( 'question', $from['question'] );
		if ( $from['cat_id'] != '' ) {
			$where['faq.cat_id'] = $from['cat_id'];
			$this->assign( 'cat_id', $from['cat_id'] );
		}
		$count = M( 'faq as faq' )->where( $where )->count( 1 );
		$page  = new \Common\Page( $count, 20 );
		$list  = M( 'faq as faq' )->where( $where )
		                          ->join( 'left join yy_faq_category as ca on ca.id = faq.cat_id' )
		                          ->field( 'faq.id,faq.question,faq.answer,faq.cat_id,faq.sort,faq.add_time,faq.status,ca.name as cat_name,ca.is_h5,ca.is_app' )
		                          ->limit( $page->firstRow, $page->listRows )->order( 'sort desc' )->select();
		$this->assign( 'faq_category', $faq );
		$this->assign( 'list', $list );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	public function add() {
		$from             = I( 'post.' );
		$from['add_time'] = time();
		$from['status']   = 1;
		$res              = M( 'faq' )->add( $from );
		if ( $res ) {
			$this->success( '添加成功', '/Back/Faq/' );
		} else {
			$this->error( '添加失败', '/Back/Faq/' );
		}
	}

	public function edit() {
		$id   = I( 'post.id' );
		$save = I( 'post.' );

		$res = M( 'faq' )->where( 'id = ' . $id )->save( $save );
		if ( $res ) {
			$this->success( '修改成功', '/Back/Faq/' );
		} else {
			$this->error( '修改失败', '/Back/Faq/' );

		}
	}

	public function delect() {
		$id             = I( 'get.id' );
		$save['status'] = 0;
		$res            = M( 'faq' )->where( 'id = ' . $id )->save( $save );
		if ( $res ) {
			$returnData['code'] = 200;
			$returnData['msg']  = '删除成功';
			$this->ajaxReturn( $returnData );
		} else {
			$returnData['code'] = 0;
			$returnData['msg']  = '删除失败';
			$this->ajaxReturn( $returnData );
		}
	}

	/**
	 * 获取faq数据
	 */
	public function getFaqInfo() {
		$id  = I( 'get.id' );
		$res = M( 'faq' )->where( 'id = ' . $id )->find();
		$this->ajaxReturn( $res );
	}

}
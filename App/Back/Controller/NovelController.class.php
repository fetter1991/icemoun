<?php

/**
 * 图解管理
 *
 *
 * @author      tsj 作者
 * @version     2.0 版本号
 */

namespace Back\Controller;

use Common\Lib\AjaxPage;
use Common\Lib\MoxiangReading;

class NovelController extends CommonController {
	/**
	 * NovelController constructor.
	 */
	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	public function index() {
		$where                = array();
		$where['movies_type'] = 2;
		$where['status']      = array( 'NEQ', 2 );

		$data = I( 'get.' );
		if ( $data['movies_id'] ) {
			$map['id']         = array( 'like', '%' . $data['movies_id'] . '%' );
			$map['name']       = array( 'like', '%' . $data['movies_id'] . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
			$this->assign( 'moviesID', $data['movies_id'] );
		}

		if ( $data['author'] ) {
			$map['author']     = array( 'like', '%' . $data['author'] . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
			$this->assign( 'author', $data['author'] );
		}

		$count   = M( 'movies' )->where( $where )->count( 1 );
		$PageObj = new \Common\Page( $count, 20 );
		$list    = M( 'movies' )
			->where( $where )
			->limit( $PageObj->firstRow, $PageObj->listRows )
			->order( 'id desc' )
			->select();

		$this->assign( 'list', $list );
		$this->assign( 'page', $PageObj->show() );
		$this->display();
	}


	/**
	 * 获取书本信息
	 */
	public function getNovelInfo() {
		$id = I( 'get.id' );
		if ( ! $id ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '小说ID不能为空' ) );
		}

		$moxiang = new MoxiangReading();
		$res     = $moxiang->getResult( 'book', $id );
		$res     = json_decode( $res, true );
		if ( $res['errorCode'] == 0 ) {
			$data['novelID']   = $res['data']['id'];
			$data['name']      = $res['data']['name'];
			$data['cover']     = $res['data']['cover'];
			$data['desc']      = $res['data']['description'];
			$data['author']    = $res['data']['author'];
			$data['begin_pay'] = $res['data']['begin_pay'];
			$data['price']     = $res['data']['price'];
			$data['sex']       = $res['data']['sex'];
			//标签
			$tagsArr      = array_column( $res['data']['tags'], 'name' );
			$data['tags'] = implode( '|', $tagsArr );

			$this->assign( 'info', $data );
			//获取章节列表
			$chapterRes = $moxiang->getResult( 'chapters', $res['data']['id'] );
			if ( $chapterRes['errorCode'] == 0 ) {
				$chapterRes = json_decode( $chapterRes, true );
				$list       = $chapterRes['data'];
			} else {
				$list = '';
			}
			$this->assign( 'list', $list );
		} else {
			$this->assign( 'msg', $res['message'] );
		}
		$this->assign( 'apiId', $id );
		$this->display();
	}

	/**
	 * 检测排序
	 */
	public function checkSort() {
		$id = I( 'id' );
		if ( ! $id ) {
			$this->ajaxReturn( array( 'msg' => '原始ID不能为空' ) );
		}

		$moxiang    = new MoxiangReading();
		$chapterRes = $moxiang->getResult( 'chapters', $id );
		$msg        = '';
		if ( $chapterRes['errorCode'] == 0 ) {
			$chapterRes = json_decode( $chapterRes, true );
			$sortArr    = array_column( $chapterRes['data'], 'sort' );

			$unique_arr = array_unique( $sortArr );
			// 获取重复数据的数组
			$repeat_arr = array_diff_assoc( $sortArr, $unique_arr );
			if ( ! empty( $repeat_arr ) ) {
				foreach ( $repeat_arr as $key => $item ) {
					$msg .= "序号：" . $item . "重复<br/>";
				}
				$this->ajaxReturn( array( 'msg' => $msg, 'code' => 1 ) );
			}

			if ( count( $sortArr ) <= 1 ) {
				$this->ajaxReturn( array( 'msg' => '只有1章', 'code' => 1 ) );
			}

			$j = 1;
			for ( $i = 0; $i < count( $sortArr ); $i ++ ) {
				if ( $j != $sortArr[ $i ] ) {
					$msg .= "缺少序号：" . intval( $i + 1 ) . "<br/>";
					break;
				}
				$j ++;
			}
			if ( $msg ) {
				$this->ajaxReturn( array( 'msg' => $msg, 'code' => 2 ) );
			} else {
				$this->ajaxReturn( array( 'msg' => '暂未发现问题' ) );
			}
		} else {
			$this->ajaxReturn( array( 'msg' => '检测失败' ) );
		}
	}

	/**
	 * 导入小说
	 */
	public function add() {
		$data                 = I( 'post.' );
		$novelId              = $data['novelId'];
		$data['movies_type']  = 2;
		$data['status']       = 0;
		$data['form']         = '["17"]';
		$where                = array();
		$where['movies_type'] = 2;
		$where['name']        = $data['name'];

		$isExist = M( 'movies' )->where( $where )->find();
		if ( ! $isExist ) {
			$res = M( 'movies' )->add( $data );
			if ( $res ) {
				$moxiang    = new MoxiangReading();
				$chapterRes = $moxiang->getResult( 'chapters', $novelId );
				if ( $chapterRes['errorCode'] == 0 ) {
					$chapterRes = json_decode( $chapterRes, true );
					$list       = $chapterRes['data'];
					$addData    = array();
					foreach ( $list as $key => $value ) {
						$addData[ $key ]['movies_id'] = $res;
						$addData[ $key ]['sortrank']  = $value['sort'];
						$addData[ $key ]['name']      = $value['title'];
						$addData[ $key ]['price']     = $value['price'];
						$addData[ $key ]['status']    = 1;
						$addData[ $key ]['add_time']  = time();
					}
					$addChapter = M( 'chapter' )->addAll( $addData );
					if ( $addChapter ) {
						$this->ajaxReturn( array( 'code' => 200, 'msg' => '导入成功', 'resID' => $res ) );
					} else {
						$this->ajaxReturn( array( 'code' => 200, 'msg' => '插入章节列表失败' ) );
					}
				} else {
					$this->ajaxReturn( array( 'code' => 201, 'msg' => '章节列表信息获取失败' ) );
				}
			} else {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '导入失败' ) );
			}
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '小说已经存在' ) );
		}
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
	 * 章节列表
	 */
	public function chapter() {
		$movies_id        = I( 'get.movies_id' );
		$map              = array();
		$map['movies_id'] = $movies_id;
		$map['status']    = array( 'neq', 2 );
		$chapter          = M( 'chapter' )->where( $map )->field( 'name,id' )->order( 'sortrank asc' )->select(); //章节列表

		$Model = M( 'chapter' );
		$count = $Model->where( $map )->count( '1' );
		if ( $count > 0 ) {
			import( 'Common.Lib.Page' );
			$page   = new \Common\Page( $count, 20 );
			$voList = $Model->where( $map )->order( 'sortrank desc' )->limit( $page->firstRow,
				$page->listRows )->select();
			foreach ( $voList as &$val ) {
				$seletArr['main_id']   = $val['id'];
				$seletArr['main_type'] = 2;
				$seletArr['data_type'] = 1;
				$clickNum              = M( 'stat_data' )->where( $seletArr )->field( 'num' )->find();
				$val['click_num']      = $clickNum['num'];
			}
			$this->assign( 'list', $voList );
			$this->assign( 'page', $page->show() );
		}
		$this->assign( 'chapter', $chapter );
		$this->assign( 'movies_id', $movies_id );
		$map['status'] = array( 'eq', 1 );
		$is_ok         = M( 'chapter' )->where( $map )->count( 1 );
		//默认价格
		$moviesData = M( 'movies' )->where( 'id ="' . $movies_id . '"' )->field( 'name,price' )->find();
		$this->assign( 'movies_name', $moviesData['name'] );
		$this->assign( 'price', $moviesData['price'] );
		$this->assign( 'UpperNumber', $is_ok );
		$this->display();
	}

	/**
	 * 修改章节
	 */
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
			$this->success( '修改成功' );
		} catch ( Exception $exception ) {
			$this->error( '修改失败' );
		}
	}


	/**
	 *获取图解内容列表
	 */
	public function getImagesIdList() {
		$movies_id = I( 'movies_id' );
		$old_id    = I( 'old_id' );
		//api章节列表
		$info = $this->_getInfo( 'chapters', $old_id );

		//获取内容列表
		$chapterList  = M( 'chapter' )->where( 'status = 1 and movies_id = ' . $movies_id )->select();
		$imagesIdList = array();
		foreach ( $chapterList as $key => $item ) {
			$isExist = M( 'chapter_image' )->where( 'status = 1 and chapter_id = ' . $item['id'] )->field( 'id' )->find();
			if ( ! $isExist ) {
				if ( $item['sortrank'] == $info['data'][ $key ]['sort'] ) {
					$imagesIdList[] = array(
						'id'       => $item['id'],
						'sortrank' => $info['data'][ $key ]['sort'],
						'api_id'   => $info['data'][ $key ]['id']
					);
				}
			}
		}
		$this->ajaxReturn( array( 'code' => 200, 'data' => $imagesIdList ) );
	}


	/**
	 * 更新章节内容
	 */
	public function updateChapterImages() {
		$id      = I( 'post.id' );
		$api_id  = I( 'post.api_id' );
		$sort    = I( 'post.sort' );
		$isExist = M( 'chapter_image' )->where( 'status = 1 and chapter_id = ' . $id )->find();
		if ( ! $isExist ) {
			$info               = $this->_getInfo( 'info', $api_id );
			$data['chapter_id'] = $id;
			$data['sortrank']   = $sort;
			$data['reading']    = $info['data']['content'];
			$data['status']     = 1;
			$data['url']        = 'http://cdn-yp.yymedias.com/Timg/?txt=' . urlencode( $info['data']['title'] );
			$data['add_time']   = time();
			$res                = M( 'chapter_image' )->add( $data );
			if ( $res ) {
				$this->ajaxReturn( array( 'code' => 200, 'data' => $data ) );
			}
		} else {
			$this->ajaxReturn( array( 'code' => 201, 'id' => $id, 'msg' => '已存在' ) );
		}
	}


	/**
	 * 更新章节列表
	 *
	 * @param $movies_id
	 */
	public function updateChapterList() {
		$data      = I( 'post.' );
		$apiId     = $data['apiID'];
		$movies_id = $data['movies_id'];

		$book = $this->_getInfo( 'book', $apiId );
		if ( $book['code'] == 200 && $book['data'] != '' ) {
			$moviesInfo = M( 'movies' )->where( 'id = ' . $movies_id )->field( 'name' )->find();
			if ( $moviesInfo['name'] != $book['data']['name'] ) {
				$this->ajaxReturn( array( 'code' => 0, 'msg' => '书籍信息不一致，请检查小说ID是否正确' ) );
			} else {
				$info = $this->_getInfo( 'chapters', $apiId );
				if ( $info['code'] == 200 && $info['data'] != '' ) {
					foreach ( $info['data'] as $key => $v ) {
						$isExist = M( 'chapter' )->where( "name = '" . $v['title'] . "'" )->find();
						if ( ! $isExist ) {
							$addData['movies_id'] = $movies_id;
							$addData['sortrank']  = $v['sort'];
							$addData['name']      = $v['title'];
							$addData['price']     = $v['price'];
							$addData['status']    = 1;
							$addData['add_time']  = time();
							$res                  = M( 'chapter' )->add( $addData );
						}
					}
					$this->ajaxReturn( array( 'code' => 200, 'msg' => '更新成功' ) );
				} else {
					$this->ajaxReturn( array(
						'code' => $info['code'],
						'data' => $info['data'],
						'msg'  => $info['msg']
					) );
				}
			}
		} else {
			$this->ajaxReturn( array( 'code' => $book['code'], 'data' => $book['data'], 'msg' => $book['msg'] ) );
		}

	}

	/**
	 * 获取接口内容
	 *
	 * @param $key
	 * @param $id
	 *
	 * @return array
	 */
	private function _getInfo( $key, $id ) {
		$moxiang = new MoxiangReading();
		$info    = $moxiang->getResult( $key, $id );
		$info    = json_decode( $info, true );
		if ( $info['errorCode'] == 0 ) {
			return array( 'code' => 200, 'data' => $info['data'], 'msg' => '获取信息成功' );
		} else {
			return array( 'code' => $info['errorCode'], 'data' => $info['data'], 'msg' => $info['message'] );
		}
	}

}
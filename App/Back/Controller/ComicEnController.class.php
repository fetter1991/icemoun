<?php

/**
 * 图解管理
 *
 *
 * @author      tsj 作者
 * @version     2.0 版本号
 */

namespace Back\Controller;

use Exception;
use Common\Lib\AjaxPage;
use Common\Lib\TransApi;
use Common\Lib\YouDaoApi;

class ComicEnController extends CommonController {
	/**
	 * ComicEnController constructor.
	 */
	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

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
		$movies_count = M( 'movies_en' )->alias( 'mo' )->where( $where_str )->count( 1 );
		$page         = new AjaxPage( $movies_count );
		$list         = M( 'movies_en' )->alias( 'mo' )->where( $where_str )
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
			$value['md5'] = md5( $value['id'] . '@waipaifanyi' );
		}
		$returnData['page'] = $page->show();
		$returnData['data'] = $list;
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	/**
	 * 选择影片
	 */
	public function selectMovies() {
		$name = I( 'get.name' );
		$type = I( 'get.type' );

		//已存在影片
		$moviesEn        = M( 'Movies_en' )->field( 'id' )->select();
		$ids             = array_map( 'array_shift', $moviesEn );
		$where['id']     = array( 'not in', $ids );
		$where['status'] = array( 'NEQ', 2 );
		if ( ! empty( $name ) ) {
			$map['id']         = array( 'not in', $ids );
			$map['id']         = array( 'like', '%' . $name . '%' );
			$map['name']       = array( 'like', '%' . $name . '%' );
			$map['org_name']   = array( 'like', '%' . $name . '%' );
			$map['_logic']     = 'or';
			$where['_complex'] = $map;
		}
		if ( ! empty( $type ) ) {
			$this->assign( 'type', $type );
		}
		$count = M( 'Movies' )->where( $where )->count( 1 );
		$p     = new \Common\Page( $count, 20 );

		$movies = M( 'Movies' )->where( $where )
		                       ->limit( $p->firstRow, $p->listRows )
		                       ->field( 'id,name,org_name,banner,cover,tags,desc,rank' )
		                       ->order( 'id desc' )
		                       ->select();

		$this->assign( 'list', $movies );
		$this->assign( 'name', $name );
		$this->assign( 'page', $p->show() );
		$this->display();
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
		$result                  = M( 'Movies_en' )->where( 'id =' . $id )->find();
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
		$data = I( 'post.' );

		$moviesInfo           = M( 'movies' )->where( 'id = ' . $data['movies_id'] )->find();
		$moviesInfo['status'] = 0;
		$Model                = M( 'movies_en' );
		$isExist              = $Model->where( 'id = ' . $data['movies_id'] )->find();
		if ( $isExist ) {
			$Model->save( $moviesInfo );
		} else {
			$Model->add( $moviesInfo );
		}

		$ChapterRes = $this->_updateChapter( $data['movies_id'] );
		if ( $ChapterRes['code'] == 200 ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '更新成功', 'moviesId' => $data['movies_id'] ) );
		} elseif ( $ChapterRes['code'] == 201 ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '更新成功，章节数为0', 'moviesId' => $data['movies_id'] ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '更新失败' ) );
		}
	}


	/**
	 * 同步章节
	 *
	 * @param $movies_id
	 *
	 * @return mixed
	 */
	private function _updateChapter( $movies_id ) {
		//影片章节列表
		$chapterList = M( 'chapter' )->where( 'status = 1 and movies_id = ' . $movies_id )->select();
		if ( $chapterList ) {
			$chapterRes = M( 'chapter_en' )->addAll( $chapterList );
			if ( $chapterRes ) {
				return array( 'code' => 200 );
			} else {
				return array( 'code' => 0 );
			}
		} else {
			return array( 'code' => 201 );
		}
	}

	/**
	 *获取图解内容列表
	 */
	public function getImagesIdList() {
		$movies_id   = I( 'movies_id' );
		$chapterList = M( 'chapter' )->where( 'status = 1 and movies_id = ' . $movies_id )->select();

		$imagesIdList = array();
		foreach ( $chapterList as $key => $item ) {
			$list = array();
			$list = M( 'chapter_image' )->where( 'status = 1 and chapter_id = ' . $item['id'] )->field( 'id' )->select();
			foreach ( $list as $value ) {
				$res = M( 'chapter_image_en' )->where( 'status = 1 and id = ' . $value['id'] )->find();
				if ( ! $res ) {
					$imagesIdList[] = $item['id'];
					break;
				}
			}
		}

		$this->ajaxReturn( array( 'code' => 200, 'data' => $imagesIdList ) );
	}

	/**
	 * 有道API翻译
	 */
	public function updateChapterImages() {
		$translate         = new YouDaoApi();
		$id                = I( 'post.id' );
		$chapterImagesList = M( 'chapter_image' )->where( 'status = 1 and chapter_id = ' . $id )->order( 'sortrank asc' )->select();

		foreach ( $chapterImagesList as $key => $v ) {
			if ( empty( $v['reading'] ) || $v['reading'] == " " ) {
				$chapterImagesList[ $key ]['reading'] = "--";
			} else {
				$reading                              = str_replace( array( "\r\n", "\r", "\n" ), " ", $v['reading'] );
				$chapterImagesList[ $key ]['reading'] = $reading;
			}
		}

		$str = '';
		foreach ( $chapterImagesList as $key => $item ) {
			$str .= $item['reading'] . "\n";
		}

		//翻译
		$reading = $translate->do_request( $str );
		$reading = json_decode( $reading, true );

		if ( $reading['errorCode'] == 0 ) {
			$translation = $reading['translation']['0'];
			$readingArr  = explode( "\n", $translation );

			foreach ( $chapterImagesList as $k => $item ) {
				//是否已存在
				$imagesInfo = M( 'chapter_image_en' )->where( 'status = 1 and id = ' . $item['id'] )->find();
				if ( ! $imagesInfo ) {
					$item['reading'] = $readingArr[ $k ];
					$res             = M( 'chapter_image_en' )->add( $item );
					if ( ! $res ) {
						array_push( $errList, $item['id'] );
					}
				}
			}
			if ( empty( $errList ) ) {
				$this->ajaxReturn( array( 'code' => '200', 'msg' => '更新成功', 'data' => '章节ID： ' . $id ) );
			} else {
				$this->ajaxReturn( array( 'code' => '200', 'msg' => '部分图解翻译失败', 'data' => $errList ) );
			}
		} else {
			$this->ajaxReturn( array( 'code' => $reading['errorCode'], 'msg' => $reading[1] ) );
		}
	}

	/**
	 * 百度API翻译
	 */
	public function updateChapterImages_Baidu() {
		$translate         = new TransApi();
		$id                = I( 'post.id' );
		$chapterImagesList = M( 'chapter_image' )->where( 'status = 1 and chapter_id = ' . $id )->order( 'sortrank asc' )->select();

		foreach ( $chapterImagesList as $key => $v ) {
			if ( empty( $v['reading'] ) || $v['reading'] == " " ) {
				$chapterImagesList[ $key ]['reading'] = "--";
			} else {
				$reading                              = str_replace( array( "\r\n", "\r", "\n" ), " ", $v['reading'] );
				$chapterImagesList[ $key ]['reading'] = $reading;
			}
		}

		$str = '';
		foreach ( $chapterImagesList as $key => $item ) {
			$str .= $item['reading'] . "\n";
		}

		//翻译
		$reading = $translate->translate( $str, 'zh', 'en' );
		if ( $reading['trans_result'] ) {
			$readingArr = $reading['trans_result'];

			foreach ( $chapterImagesList as $k => $item ) {
				//是否已存在
				$imagesInfo = M( 'chapter_image_en' )->where( 'status = 1 and id = ' . $item['id'] )->find();
				if ( ! $imagesInfo ) {
					$item['reading'] = $readingArr[ $k ]['dst'];
					$res             = M( 'chapter_image_en' )->add( $item );
					if ( ! $res ) {
						array_push( $errList, $item['id'] );
					}
				}
			}
			if ( empty( $errList ) ) {
				$this->ajaxReturn( array( 'code' => '200', 'msg' => '更新成功', 'data' => '章节ID： ' . $id ) );
			} else {
				$this->ajaxReturn( array( 'code' => '200', 'msg' => '部分图解翻译失败', 'data' => $errList ) );
			}
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '更新失败' ) );
		}

	}

	/**
	 * 修改图解
	 */
	public function edit() {
		$Movies = D( "Movies_en" ); // 实例化对象
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
				$resMoveis = M( 'movies_en' )->where( 'id =' . $data['id'] )->field( 'order_num,hot' )->find();
				if ( $data['hot'] != $resMoveis['hot'] ) {
					M( 'movies_en' )->where( 'hot >=' . $data['hot'] )->setInc( 'hot', 1 );
				}
				if ( $data['order_num'] != $resMoveis['order_num'] ) {
					M( 'movies_en' )->where( 'order_num >=' . $data['order_num'] )->setInc( 'order_num', 1 );
				}
				$db_id = $data['db_id'];
				if ( $db_id ) {
					M( 'movie_search' )->where( 'db_id =' . $db_id )->save( [ 'movie_id' => $data['id'] ] );
				}
				//修改作者影片数
				$authorData = M( 'author' )->where( 'id = ' . $data['author_id'] )->find();
				if ( $authorData ) {
					$moviesSum = M( 'movies_en' )->where( 'status != 2 and author_id =' . $data['author_id'] )->count();
					M( 'author' )->where( 'id = ' . $data['author_id'] )->save( [ 'movies_count' => $moviesSum ] );
				}
				$this->ajaxReturn( array( 'code' => 200, 'msg' => '修改成功' ) );
			} else {
				$this->ajaxReturn( array( 'code' => 500, 'msg' => '修改失败' ) );
			}
		}
	}

	/**
	 * 上下架
	 */
	public function setStatus() {
		if ( ! IS_AJAX ) {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '非法入口' ) );
		}
		$id          = I( 'post.id' );
		$movies_info = M( 'movies_en' )->where( 'id ="' . $id . '"' )->field( 'status' )->find();
		$status_get  = $movies_info['status'];
		$status      = $status_get == 0 ? 1 : 0;
		$online      = time();
		$data        = array();
		if ( $status == 1 ) {
			$data['status']      = $status;
			$data['online_time'] = $online_time ? strtotime( $online_time ) : $online;
			if ( $movies_info['lastupdate'] == 0 ) {
				$data['lastupdate'] = $online_time ? strtotime( $online_time ) : $online;
			}
		} else {
			$data = array( 'status' => $status );
		}
		if ( M( 'movies_en' )->where( 'id ="' . $id . '"' )->save( $data ) ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功', 'status' => $status ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}
	}
	//end图解功能

	/**
	 * 章节列表
	 */
	public function chapter() {
		$movies_id        = I( 'get.movies_id' );
		$map              = array();
		$map['movies_id'] = $movies_id;
		$map['status']    = array( 'neq', 2 );
		$chapter          = M( 'chapter_en' )->where( $map )->field( 'name,id' )->order( 'sortrank asc' )->select(); //章节列表

		$Model = M( 'chapter_en' );
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
		$is_ok         = M( 'chapter_en' )->where( $map )->count( 1 );
		//默认价格
		$moviesData = M( 'movies_en' )->where( 'id ="' . $movies_id . '"' )->field( 'name,price' )->find();
		$this->assign( 'movies_name', $moviesData['name'] );
		$this->assign( 'price', $moviesData['price'] );
		$this->assign( 'UpperNumber', $is_ok );
		$this->display();
	}

	/**
	 * 图解章节上下架
	 */
	public function chapterSetStatus() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$id     = I( 'post.id' );
		$status = M( 'chapter_en' )->where( 'id ="' . $id . '"' )->getField( 'status' );
		if ( $status == 0 ) {
			$status = 1;
		} else {
			$status = 0;
		}
		if ( M( 'chapter_en' )->where( 'id ="' . $id . '"' )->save( array( 'status' => $status ) ) ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功', 'status' => $status ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}
	}

	/**
	 * 修改章节
	 */
	public function chapterEdit() {
		$data      = I( 'post.' );
		$movies_id = I( 'movies_id' );
		if ( empty( $data['id'] ) ) {
			$this->error( '参数错误' );
		}

		try {
			if ( $data['id'] != $data['chapter_id'] ) {

				$sortrank = M( 'chapter_en' )->where( 'id = "' . $data['chapter_id'] . '"' )->getField( 'sortrank' );


				if ( $sortrank > $data['sortrank'] ) {
					if ( $data['before_or_after'] == 1 ) {
						$sortrank --;
					}
					$map = array(
						'movies_id' => $data['movies_id'],
						'sortrank'  => array( array( 'gt', $data['sortrank'] ), array( 'elt', $sortrank ) )
					);
					M( 'chapter_en' )->where( $map )->setDec( 'sortrank' );
				} else {
					if ( $data['before_or_after'] == 2 ) {
						$sortrank ++;
					}

					$map = array(
						'movies_id' => $data['movies_id'],
						'sortrank'  => array( array( 'lt', $data['sortrank'] ), array( 'egt', $sortrank ) )
					);
					M( 'chapter_en' )->where( $map )->setInc( 'sortrank' );
				}
				$data['sortrank'] = $sortrank;
			}
			M( 'chapter_en' )->save( $data );
			$this->success( '修改成功', '/Back/ComicEn/chapter?movies_id=' . $movies_id );
		} catch ( Exception $exception ) {
			$this->error( '修改失败' );
		}
	}

	/**
	 * 章节图片
	 */
	public function chapterImg() {
		$chapter_id = I( 'get.chapter_id' );

		$map['chapter_id'] = $chapter_id;
		$map['status']     = array( 'neq', 2 );

		$chapterImg = M( 'chapterImage_en' )->where( $map )->order( 'sortrank asc' )->field( '*' )->select(); //章节列表
		$this->_list( 'chapterImage_en', $map, 'sortrank asc' );
		$list = $this->get( 'list' );
		foreach ( $list as $key => $value ) {
			$readingZh                 = M( 'chapterImage' )->where( 'id = ' . $value['id'] )->field( 'reading' )->find();
			$list[ $key ]['readingZh'] = $readingZh['reading'];
		}

		$this->assign( 'list', $list );
		$this->assign( 'chapter_id', $chapter_id );
		$ZiD             = M( 'chapter_en' )->where( 'id ="' . $chapter_id . '"' )->field( 'movies_id,sortrank,name' )->select();
		$movid           = ! empty( $ZiD[0]['movies_id'] ) ? $ZiD[0]['movies_id'] : "";
		$sortrank        = ! empty( $ZiD[0]['sortrank'] ) ? $ZiD[0]['sortrank'] : "";
		$chapterName     = ! empty( $ZiD[0]['name'] ) ? $ZiD[0]['name'] : "";
		$map             = array( 'movies_id' => $movid, 'sortrank' => array( 'lt', $sortrank ) );
		$preChapter      = M( 'Chapter_en' )->where( $map )->order( 'sortrank desc' )->find();
		$map['sortrank'] = array( 'gt', $sortrank );
		$nextChapter     = M( 'Chapter_en' )->where( $map )->order( 'sortrank asc' )->find();
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
		$chapterImgNum      = M( 'chapterImage_en' )->where( $maps )->count( 1 ); //章节列表
		$this->assign( 'chapter_name', $chapterName );
		$this->assign( 'endId', $endId );
		$this->assign( 'beginId', $beginId );
		$this->assign( 'UpperNumber', $chapterImgNum );
		$movies_id = M( 'chapter_en' )->where( 'id="' . $chapter_id . '"' )->getField( 'movies_id' ); //图解id
		$this->assign( 'movies_id', $movies_id );
		$this->display();
	}

	/**
	 * 图解章节图片上下架
	 */
	public function chapterImgSetStatus() {
		if ( ! IS_AJAX ) {
			exit( '非法入口' );
		}
		$id     = I( 'post.id' );
		$status = M( 'chapterImage_en' )->where( 'id ="' . $id . '"' )->getField( 'status' );
		if ( $status == 0 ) {
			$status = 1;
		} else {
			$status = 0;
		}
		if ( M( 'chapterImage_en' )->where( 'id ="' . $id . '"' )->save( array( 'status' => $status ) ) ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '操作成功', 'status' => $status ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '操作失败' ) );
		}
	}

	/**
	 * 修改章节
	 */
	public function chapterImgEdit() {
		$data = I( 'post.' );
		if ( empty( $data['id'] ) ) {
			$this->error( '参数错误' );
		}
		$oldSortrank = M( 'chapterImage_en' )->where( 'id = "' . $data['id'] . '"' )->getField( 'sortrank' );
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
					M( 'chapterImage_en' )->where( $map )->setDec( 'sortrank' );
				} else {
					if ( $data['before_or_after'] == 2 ) {
						$data['sortrank'] ++;
					}

					$map = array(
						'chapter_id' => $data['chapter_id'],
						'sortrank'   => array( array( 'lt', $oldSortrank ), array( 'egt', $data['sortrank'] ) )
					);
					M( 'chapterImage_en' )->where( $map )->setInc( 'sortrank' );
				}
			}
			M( 'chapterImage_en' )->save( $data );
			$this->success( '修改成功' );
		} catch ( Exception $exception ) {
			$this->error( '修改失败' );
		}
	}

	/**
	 * 编辑图解内容
	 */
	public function editChapterImg() {
		$data = I( 'post.' );
		if ( empty( $data['id'] ) ) {
			$this->error( '参数错误' );
		}

		$res = M( 'chapter_image_en' )->where( 'id = ' . $data['id'] )->save( $data );
		if ( $res ) {
			$this->ajaxReturn( array( 'code' => 200, 'msg' => '修改成功', 'data' => $data ) );
		} else {
			$this->ajaxReturn( array( 'code' => 0, 'msg' => '修改失败' ) );
		}
	}

	/**
	 * 获取内容
	 */
	public function getContent() {
		$chapter_id  = I( 'id' );
		$map         = array(
			'chapter_id' => $chapter_id,
			'status'     => '1'
		);
		$where['id'] = $chapter_id;
		$movies_id   = M( 'chapter_en' )->where( $where )->field( 'movies_id,sortrank,name' )->find();

		$chapterFires['movies_id'] = $movies_id['movies_id'];
		$chapterFires['status']    = 1;
		$chapterFires['sortrank']  = array( 'lt', $movies_id['sortrank'] );

		$chapter_first            = M( 'chapter_en' )->where( $chapterFires )->order( 'sortrank desc' )->getField( 'id' );
		$chapterFires['sortrank'] = array( 'gt', $movies_id['sortrank'] );
		$chapter_last             = M( 'chapter_en' )->where( $chapterFires )->order( 'sortrank asc' )->getField( 'id' );

		$h_chapter_first = $chapter_first ? $chapter_first : '';
		$h_chapter_last  = $chapter_last ? $chapter_last : '';
		$content         = M( 'chapterImage_en' )->where( $map )->order( 'sortrank asc' )->field( 'url,reading' )->select();
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

	/**
	 * 获取章节详情
	 */
	public function getchapterImg() {
		$id         = I( 'get.id' );
		$chapterImg = M( 'chapterImage_en' )->where( 'id =' . $id )->find(); //章节列表
		$this->ajaxReturn( $chapterImg );
	}

}

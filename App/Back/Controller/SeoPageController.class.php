<?php


namespace Back\Controller;

class SeoPageController extends CommonController {
	/**
	 * UserToolsController constructor.
	 */
	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	public function index() {
		$this->display();
	}

	/**
	 * 获取ID列表
	 */
	public function getIds() {
		$ids = M( 'movies' )->where( 'status = 1' )->field( 'id' )->order( array( 'id' => 'asc' ) )->select();
		$this->ajaxReturn( $ids );
	}

	/**
	 * 循环生成html文件
	 */
	public function buildIndex() {
		$id = I( 'id' );
		$this->_build( $id );
	}


	private function _build( $movies_id ) {
		if ( file_exists( ROOT_PATH . '/seo/' . $movies_id . '.html' ) ) {
			$this->ajaxReturn( array( 'code' => 200, 'data' => $movies_id, 'msg' => '已生成' ) );
		} else {
			$movies = M( 'movies as m' )->join( 'left join yy_chapter as c on m.id = c.movies_id' )
			                            ->field( 'm.id,m.name,m.org_name,m.score,m.actor,m.cover,m.banner,m.showtime_id,m.zone_id,m.tags,m.actor,m.editor_note,c.id as c_id' )
			                            ->where( 'm.status = 1 and m.id = ' . $movies_id )
			                            ->find();

			if ( $movies ) {
				$zone                 = M( 'zone' )->where( 'id = ' . $movies['zone_id'] )->field( 'name' )->find();
				$show_time            = M( 'showtime' )->where( 'id = ' . $movies['showtime_id'] )->field( 'name' )->find();
				$movies['zone']       = $zone['name'];
				$movies['light_star'] = floor( $movies['score'] / 2 );
				$movies['showtime']   = $show_time['name'];

				//章节列表
				$chapter = M( 'chapter_image' )->where( 'status = 1 and chapter_id = ' . intval( $movies['c_id'] ) )->select();
				if ( ! $chapter ) {
					$chapter = array();
				}
				//相似
				$similarity = array();
				$similar    = M( 'movies_similarity' )->where( 'movies_id = ' . $movies_id )->select();
				if ( $similar ) {
					foreach ( $similar as $key => $item ) {
						$si_movies = '';
						$si_movies = M( 'movies' )->where( 'status = 1 and id = ' . $item['to_movies_id'] )->find();
						if ( $si_movies ) {
							$zone                    = M( 'zone' )->where( 'id = ' . $si_movies['zone_id'] )->field( 'name' )->find();
							$show_time               = M( 'showtime' )->where( 'id = ' . $si_movies['showtime_id'] )->field( 'name' )->find();
							$si_movies['zone']       = $zone['name'];
							$si_movies['showtime']   = $show_time['name'];
							$si_movies['light_star'] = floor( $si_movies['score'] / 2 );
							$similarity[]            = $si_movies;
						}
					}
				}

				$this->assign( 'similarity', $similarity );
				$this->assign( 'chapter', $chapter );
				$this->assign( 'movie', $movies );

				$this->buildHtml( $movies['id'] . '_index.html', ROOT_PATH . '/seo/' );
				$this->ajaxReturn( array( 'code' => 200, 'data' => $movies_id ) );
			}
		}
	}
}
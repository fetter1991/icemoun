<?php


namespace Back\Controller;

use Think\Controller;

/**
 * YY2C协议
 *
 * Class Yy2cAnalysisController
 * @package Back\Controller
 */
class Yy2cAnalysisController extends Controller {
	/**
	 * Yy2cAnalysisController constructor.
	 */
	public function __construct() {
		parent::__construct();
		import( 'Common.Lib.Page' );
	}

	//主界面
	public function index() {
		$getData = I( 'get.' );
		$this->assign( $getData );
		$this->assign( 'YY2CList', $this->list );
		$this->display();
	}

	//供外部获取YY2C类型列表
	public function getYY2CList( $isJson ) {
		if ( $isJson && $isJson == true ) {
			$this->ajaxReturn( $this->list );
		} else {
			return $this->list;
		}
	}

	/**
	 * 处理YY2C表单数据
	 */
	public function callback() {
//		$data       = I( 'post.' );
		$data['tt'] = 'tt';
		$this->ajaxReturn( $data );
	}

	/**
	 * Demo     {"a":1, "v": 1, "p":{}}
	 * 字段     说明
	 * a        代表跳转方式  （整型） （必须）
	 * v        协议版本 （整型） （必须）
	 * p        参数，基于参数a而变化，详见表格说明 （JSON结构） （必须）
	 * @var array
	 */
	private $list = array(
		array( 'a' => '1', 'title' => '图解' ),
		array( 'a' => '2', 'title' => '充值中心' ),
		array( 'a' => '3', 'title' => '提示文案' ),
		array( 'a' => '4', 'title' => '活动' ),
		array( 'a' => '5', 'title' => '专辑' ),
		array( 'a' => '6', 'title' => '打开消息中心' ),
		array( 'a' => '7', 'title' => '打开影片阅读页' ),
		array( 'a' => '8', 'title' => '打开排行榜' ),
		array( 'a' => '9', 'title' => '打开求片' ),
		array( 'a' => '10', 'title' => '打开限免列表 ' ),
		array( 'a' => '11', 'title' => '打开小说列表 ' ),
		array( 'a' => '12', 'title' => '打开登录页面 ' ),
		array( 'a' => '13', 'title' => '分享内容 ' ),
		array( 'a' => '14', 'title' => '打开个人资料 ' ),
		array( 'a' => '15', 'title' => '打开最近更新列表 ' ),
		array( 'a' => '16', 'title' => '打开作者广场 ' ),
		array( 'a' => '17', 'title' => '打开视频列表 ' ),
		array( 'a' => '18', 'title' => '打开专辑列表 ' ),
		array( 'a' => '19', 'title' => '打开影人专辑列表 ' ),
		array( 'a' => '20', 'title' => '清除所有页面，打开首页 ' ),
		array( 'a' => '100', 'title' => 'URL' ),
	);

	/**
	 * @param $yy2c
	 *
	 * @return string
	 */
	public function analysisYy2c( $yy2c ) {
		$json_yy2c  = json_decode( $yy2c, true );
		$arr_yy2c   = json_decode( $json_yy2c['cus_yy2c'], true );
		$push_count = '';
		switch ( $arr_yy2c['a'] ) {
			case 1:
				$movies_id  = $arr_yy2c['p']['mid'];
				$movies     = M( 'movies' )->where( 'id =' . $movies_id )->field( 'name' )->find();
				$push_count = 'ID:' . $movies_id . '<br>图解名称：' . $movies['name'];
				break;
			case 2:
				$push_count = '打开充值中心';
				break;
			case 3:
				$push_count = '打开弹窗提示：' . $arr_yy2c['p']['content'];
				break;
			case 4:
				$active_id  = $arr_yy2c['p']['aid'];
				$active     = M( 'activity' )->where( 'id =' . $active_id )->field( 'title' )->find();
				$push_count = 'ID:' . $active_id . '<br>活动名称：' . $active['title'];
				break;
			case 5:
				$topic_id   = $arr_yy2c['p']['tid'];
				$topic      = M( 'topic' )->where( 'id =' . $topic_id )->field( 'name' )->find();
				$push_count = 'ID:' . $topic_id . '<br>专辑名称：' . $topic['name'];
				break;
			case 100:
				$topic_id   = $arr_yy2c['p']['url'];
				$push_count = '打开URL：' . $topic_id;
				break;
			default:
				break;
		}

		return $push_count;
	}


	/**
	 * 选择影片
	 */
	public function selectMovies() {
		$keyword = I( 'get.keyword' );
		$type    = I( 'get.type' );

		$where['status'] = 1;
		if ( ! empty( $keyword ) ) {
			if ( is_numeric( $keyword ) ) {
				$where['id'] = $keyword;
			} else {
				$map['name']       = array( 'like', '%' . $keyword . '%' );
				$map['org_name']   = array( 'like', '%' . $keyword . '%' );
				$map['_logic']     = 'or';
				$where['_complex'] = $map;
			}
		}
		if ( ! empty( $type ) ) {
			$where['movies_type'] = $type;
			$this->assign( 'type', $type );
		}
		$count  = M( 'Movies' )->where( $where )->count( 1 );
		$p      = new \Common\Page( $count, 18 );
		$movies = M( 'Movies' )->where( $where )
		                       ->limit( $p->firstRow, $p->listRows )
		                       ->order( 'id desc' )
		                       ->select();
		$this->assign( 'list', $movies );
		$this->assign( 'page', $p->show() );
		$this->assign( 'keyword', $keyword );
		$this->display();
	}

	/**
	 * 选择活动
	 */
	public function selectActivity() {
		$Model = M( 'activity' );

		//取得满足条件的记录数
		$where['status']     = 1;
		$where['end_time']   = array( 'egt', time() );
		$where['begin_time'] = array( 'elt', time() );
		$count               = $Model->where( $where )->count( '1' );
		$page                = new \Common\Page( $count );
		if ( I( 'get.name' ) ) {
			if ( is_numeric( I( 'get.name' ) ) ) {
				$where['id'] = I( 'get.name' );
			} else {
				$where['title'] = array( 'like', I( 'get.name' ) . '%' );
			}
		}
		$voList = $Model->alias( 'a' )
		                ->where( $where )
		                ->order( 'add_time desc' )
		                ->limit( $page->firstRow . ',' . $page->listRows )
		                ->field( 'id,title,begin_time,end_time' )
		                ->select();

		$this->assign( 'list', $voList );
		$this->assign( 'page', $page->show() );
		$this->display();
	}

	/**
	 * 选择专辑
	 */
	public function selectTopic() {
		$Model = M( 'Topic' );

		//取得满足条件的记录数
		$where['status'] = 1;

		$count = $Model->where( $where )->count( '1' );
		$page  = new \Common\Page( $count );
		if ( I( 'get.name' ) ) {
			if ( is_numeric( I( 'get.name' ) ) ) {
				$where['id'] = I( 'get.name' );
			} else {
				$where['a.name'] = array( 'like', I( 'get.name' ) . '%' );
			}
		}
		$voList = $Model->alias( 'a' )
		                ->where( $where )
		                ->order( 'add_time desc' )
		                ->limit( $page->firstRow . ',' . $page->listRows )
		                ->field( 'id,name,desc,cover,rank' )
		                ->select();

		$this->assign( 'list', $voList );
		$this->assign( 'page', $page->show() );
		$this->display( 'selectTopic' );
	}
}
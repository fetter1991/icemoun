<?php

namespace Home\Controller;

use Common\Page;

class IndexController extends CommonController {
	public function index() {
		$this->display();
	}

	public function article() {
		$id     = I( 'id' );
		$movies = M( 'movies_copy' )->where( 'id = ' . $id )->find();
		if ( $movies['tags'] ) {
			$movies['tag_list'] = explode( '|', $movies['tags'] );
		}
		$this->assign( 'info', $movies );
		$this->display();
	}


	public function comic() {
		$this->display();
	}

	public function comic_read() {
		$id = I( 'id' );

		$this->assign( 'id', $id );
		$this->display();
	}

	public function getMoviesData() {
		$getData = I( 'get.' );

		$where['movies_type'] = 3;
		$count                = M( 'movies_copy' )->where( $where )->count( 1 );

		import( 'Common.Lib.Page' );
		$Page = new Page( $count, 15 );

		$list = M( 'movies_copy' )->where( $where )->limit( $Page->firstRow, $Page->listRows )->select();

		$returnData['page'] = $Page->show();
		$returnData['data'] = $list;
		$this->ajaxReturn( array( 'code' => 200, 'data' => $returnData ) );
	}

	public function getImg() {
		$id    = I( 'id' );
		$id    = 2;
		$comic = M( 'movies_copy' )->where( 'id = ' . $id )->find();
		$dir   = BOOK . $comic['name'] . '/';
		$path  = iconv( "utf-8", "gbk", $dir );
		$temp  = scandir( $path );
		unset( $temp[0] );
		unset( $temp[1] );
		foreach ( $temp as $key => $item ) {
			$temp[ $key ] = '/Public/book/' . $comic['name'] . '/' . $item;
		}
		$this->ajaxReturn( array( 'code' => 200, 'data' => $temp ) );
	}

	/**
	 * 遍历文件夹
	 *
	 * @param $files
	 */
	private function list_file( $files ) {
		//1、首先先读取文件夹
		$temp = scandir( $files );
		//遍历文件夹
		foreach ( $temp as $v ) {
			$a = $files . '/' . $v;
			//如果是文件夹则执行
			if ( is_dir( $a ) ) {
				//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
				if ( $v == '.' || $v == '..' ) {
					continue;
				}
				//把文件夹红名输出
				//echo "<font color='red'>$a</font>", "<br/>";

				//因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
				$this->list_file( $a );
			} else {
				echo $a, "<br/>";
			}
		}
	}
}
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件
// 检测PHP环境

if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	die( 'require PHP > 5.3.0 !' );
}
//define('BIND_MODULE','Admin');
//定义网站根目录
define( 'ROOT_PATH', './' );
// 定义应用目录
define( 'APP_PATH', './App/' );
define( 'BOOK', './Public/book/' );

//开启调试模式
define( 'APP_DEBUG', false );
//本地库
define( 'IS_LOCAL', true );

define( '__FRONT__', 'graphmovie.yymedias.com' );
define( '__BACK__', 'auth-graphmovie.yymedias.com' );
define( '__ASSETS__', '//auth-graphmovie.yymedias.com' );
// 定义生成目录安全文件True
define( 'BUILD_DIR_SECURE', true );
// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
// 亲^_^ 后面不需要任何代码了 就是如此简单
?>



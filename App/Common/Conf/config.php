<?php
return array(
	//'配置项'=>'配置值'
	'URL_MODEL' => 2,

	// ---- Redis 配置 ---
	'REDIS'     => array(
		'IP'      => '127.0.0.1',
		'PWD'     => 'tsj',
		'PORT'    => '6379',
		'TIMEOUT' => 1
	),

	'DB_TYPE'    => 'mysql',     // 数据库类型
	'DB_HOST'    => IS_LOCAL ? '127.0.0.1' : '10.0.1.7', // 服务器地址
	'DB_NAME'    => 'novel',          // 数据库名
	'DB_USER'    => 'root',      // 用户名
	'DB_PWD'     => '123456',          // 密码
	'DB_PORT'    => '3306',        // 端口
	'DB_PREFIX'  => 'yy_',    // 数据库表前缀
	'DB_CHARSET' => 'utf8mb4',

	'URL_HTML_SUFFIX' => '',

	'OLDURL' => 'yymedias.com',

	'YOUYINGURL'           => [
		'yymedias.com',
		'jiayoumei-tech.com',
		'yycm0.com',
		'yycm5.com',
	],
	'ADMIN_URL'            => 'yymedias.test/uploadTest', //后端域名
	'PAGE_LIST_SIZE'       => 20,
	'SHOW_PAGE_TRACE'      => false,
	'DB_FIELDS_CACHE'      => false,
	'DOMAIN'               => 'jiayoumei-tech.com',//前端域名
	'URL_CASE_INSENSITIVE' => false,
	'TMPL_PARSE_STRING'    => array(
		'__PUBLIC__' => __ASSETS__ . '/Public', // 更改默认的/Public 替换规则
		'__JS__'     => __ASSETS__ . '/Public/js', // 增加新的JS类库路径替换规则
		'__CSS__'    => __ASSETS__ . '/Public/css', // 增加新的上传路径替换规则
		'__IMG__'    => __ASSETS__ . '/Public/img', // 增加新的上传路径替换规则
		'__LIB__'    => __ASSETS__ . '/Public/lib', // 增加新的上传路径替换规则
		'__MC__'     => '/Public/metronic', // 增加新的上传路径替换规则
		'__STATIC__' => '/Public'
	),
	'UPLOAD_FILE_QINIU'    => array(
		'maxSize'      => 5 * 1024 * 1024,//文件大小
		'rootPath'     => './',
		'saveName'     => array( 'uniqid', '' ),
		'exts'         => [
			'bmp',
			'jpg',
			'png',
			'tiff',
			'gif',
			'pcx',
			'tga',
			'exif',
			'fpx',
			'svg',
			'psd',
			'cdr',
			'pcd',
			'dxf',
			'ufo',
			'eps',
			'ai',
			'raw',
			'WMF'
		],  // 设置附件上传类型
		'driver'       => 'Qiniu',//七牛驱动
		'driverConfig' => array(
			'secretKey' => 'DHSf_z59vHbosD5z48HPdxTQk15zlGWKc5N0-51R',
			'accessKey' => 'vgQW4ufGJlFSv-z01zDKOpyqAfgpJ9bQdCDFMqBD',
			'domain'    => 'cimage.flgwx.com',
			'bucket'    => 'comic',
		)
	),
	'pwd'                  => 'fulegefx'
);
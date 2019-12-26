<?php
return array(
	//********* 数据库读写分离  *****************//
	'DB_HOST' => IS_LOCAL ? '127.0.0.1' : '10.0.1.7',    // 服务器地址
	'DB_USER'        => 'root,root',                            // 用户名
	'DB_PWD'         => '123456,123456',                        // 密码
	'DB_DEPLOY_TYPE' => 1, //是否启用分布式
	'DB_RW_SEPARATE' => true,  // 设置读写分离

	'PAGE_LIST_SIZE' => 20, // 表格每一页显示条数
	'ZZ_MATCH'       => array(
		'PWD' => '/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]{3,12}$/', // 密码正则
		'DAY' => '/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/' // 日期正则
	),


	'SHOW_PAGE_TRACE' => false,
	// 秘钥
	'APPC_KEY'        => array(
		'PWD'   => 'appc@^&key',
		'LOGIN' => '!$f@l%g#'
	),
	//***********************************SESSION设置**********************************
	'SESSION_PREFIX'  => 'zy_user_user', // session 前缀
	'SESSION_OPTIONS' => array(
		'name'             => 'zy_user_user',                    //设置session名
		'expire'           => 3600 * 24,                      //SESSION保存15天
		'use_trans_sid'    => 1,                               //跨页传递
		'use_only_cookies' => 0,                               //是否只开启基于cookies的session的会话方式
	),

	'KEFU_PHONE' => '13144818177'
);
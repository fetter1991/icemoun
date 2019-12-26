<?php
namespace Back\Model;
use Think\Model;
class AuthorModel extends CommonModel {
	protected $_validate  = array(
        array('nick_name','require','昵称必须', Model::EXISTS_VALIDATE),
		array('tel','require','电话号码必须', Model::EXISTS_VALIDATE),
        array('repassword','password','确认密码不正确',Model::EXISTS_VALIDATE,'confirm'), // 验证确认密码是否和密码一致
        array('password','checkPwd','密码格式不正确',Model::EXISTS_VALIDATE,'function'), // 自定义函数验证密码格式
        array('account','','帐号名称已经存在！',Model::EXISTS_VALIDATE,'unique',Model::MODEL_INSERT), // 在新增的时候验证name字段是否唯一
	);

	protected $_auto = array ( 
		array('add_time','getTime',Model::MODEL_INSERT,'callback'), // 对update_time字段在更新的时候写入当前时间戳
        array('password','getPwd',Model::MODEL_BOTH,'callback'), // 对update_time字段在更新的时候写入当前时间戳
	);
	
}
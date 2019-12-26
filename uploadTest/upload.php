<?php

require_once 'Upload.class.php';
if (empty($_POST)) {
	exit('非法请求');
}
$servername = 'resources.yymedias.test'; //当前服务器
$url_from = $_SERVER['HTTP_REFERER']; //前一URL
$arr = parse_url($url_from);
if ($arr['host'] != $servername) {
	echo json_encode(array('code' => 0, 'msg' => '错误不允许上传'));
	exit;
}
$savePath = $_POST['savepath'];
$type = $_POST['type'];
if (!empty($savePath)) {
	$option['saveName'] = $savePath . '_' . uniqid();
}
$option['autoSub'] = false;
$option['rootPath'] = './channel/idcard/';
$upload = new Upload($option);
$info = $upload->upload();

if ($info) {
	$arr = reset($info);
	echo json_encode(array('code' => 200, 'url' => 'http://resources.yymedias.test/uploadTest/channel/idcard/' . $arr['savepath'] . $arr['savename']));
} else {
	echo json_encode(array('code' => 0, 'msg' => $upload->getError()));
}
?>
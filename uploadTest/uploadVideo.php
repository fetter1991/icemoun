<?php

    set_time_limit(180);
    require_once 'Upload.class.php';
    if (empty($_POST)) {
        exit('非法请求');
    }
    $servername = 'resources.yymedias.test'; //当前服务器
  
    $savePath = $_POST['savepath'];
    $type = $_POST['type'];
    if (!empty($savePath)) {
        $option['saveName'] = $savePath . '_' . uniqid();
    }
	$savefile = $_POST['rootPath'];
	
    $option['autoSub'] = false;
    $option['rootPath'] = './'.$savefile.'/';
	if (!file_exists($option['rootPath'])){
		mkdir ($option['rootPath'],0777,true);
	} 
    $upload = new Upload($option);
    $info = $upload->upload();

    if ($info) {
        $arr = reset($info);
        echo json_encode(array('code' => 200, 'url' => 'http://resources.yymedias.test/uploadTest/'.$savefile.'/'. $arr['savepath'] . $arr['savename']));
    } else {
        echo json_encode(array('code' => 0, 'msg' => $upload->getError()));
    }
?>
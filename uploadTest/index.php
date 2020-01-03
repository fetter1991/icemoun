<?php
	require_once 'Upload.class.php';
	if(empty($_POST)){
		exit('非法请求');
	}
	$savePath = $_POST['savepath'];
	$type = $_POST['type'];
	if(!empty($type)){
		$option['saveName'] = $type.'_'.uniqid();
	}
	$option['savePath'] = $savePath.'/';
    $option['autoSub'] = false;
	$upload = new Upload($option);
	
	$info = $upload->upload();

	if($info){
		$arr = reset($info);
		echo json_encode(array('code'=>200,'url'=>'http://resources.icemoun.test:83/uploadTest/comic/'.$arr['savepath'].$arr['savename']));
	}else{
		echo json_encode(array('code'=>0,'msg'=>$upload->getError()));
	}
	
?>
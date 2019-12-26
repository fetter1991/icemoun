<?php
$key = $_POST['k'];
if ($key != 'bobotoolkey') {
    exit('null-nokey');
}
if (!empty($_POST['id'])) {
    $movieId = $_POST['id'];
    $path = 'movies/'.$movieId.'';
    if (is_dir($path)) {
        try{
            $files = readfileAll($path);
            echo json_encode($files);
            exit();
        }catch (Exception $exception) {
            var_dump($exception);
        }
        
    }
} else if (!empty($_POST['file']) && !empty($_POST['opter'])){
    $maxDel = 700;
    $file = $_POST['file'];
    $opter = $_POST['opter'];
    if (!in_array($opter, ['bobo', '王梦琳', '郑佳翰', '柯梦雪', '岑雅茵', '王惠铃', '王洪波'])) {exit('账号不允许');}
    $logFile = 'Log/'.date('Y-m-d ').$opter.'.log';
    if (is_file($file)) {
        try {
            if (file_exists($logFile)) {
                $tmp = file_get_contents($logFile);
                $line = explode(PHP_EOL, $tmp);
                if (count($line)>$maxDel) {exit('超过最大删除数量了');}
            }
            if (time() > strtotime('2019-06-26 18:00')) {exit('超过时间限制，不准删了。');}
            unlink($file);
            file_put_contents($logFile, $file.PHP_EOL, FILE_APPEND);
            exit('0');
        }catch (Exception $exception) {
            var_dump($exception);
        }
    } else {
        exit('文件不存在');
    }
}
exit ('null-def');

//获取目录所有文件
/*
     * @param  readfileAll() //读取所有文件及文件夹
     * @param  iconv  // 字符转码
     * @param  $dirName // 基于 ./Public/Uploads/  的文件夹
     * @param 2017 11/20/11:03
     */

function readfileAll($dirName){
    $array=array();
    $arrays=array();
    $dir=$dirName;
    if(is_dir(iconv('utf-8','gb2312',$dir))==true){
        if($handle=opendir(iconv('utf-8','gb2312',$dir))){//打开文件内容
            while(false!==($files=readdir($handle))){//读取文件内容
                $files=iconv('gb2312','utf-8',$files);
                if($files!="."&&$files!=".."){
                    $files=iconv('utf-8','utf-8',$files);//字符转义
                    $dir=iconv('utf-8','utf-8',$dir);
                    $new_dir=$dir."/".$files;
                    if(is_dir(iconv('utf-8','gb2312',$dir."/".$files))==false)//is_file 是判断是否是目录还是文件  是目录就返回否  不是的话返回 true
                    {
                        $arrays[] = $new_dir;
                    } else {
                        $array = readfileAll($new_dir);//递归调用
                        $arrays = array_merge($arrays, $array);
                    }
                }
            }
            closeDir($handle);
        }
    }else{
        if(is_file(iconv('utf-8','gb2312',$dir))==false){
        
        }
    }
    return $arrays;//返回二维数组
}


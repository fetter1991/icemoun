<?php
/**
 * 剧能玩图片抓取
 * Created by PhpStorm.
 * User: bobo
 * Date: 2018/11/2
 * Time: 15:39
 */
ignore_user_abort(true);
set_time_limit(60);
define('BASE_PATH', '../movies/');
define('EXT', '.jpg');

$imgUrl = I('imgUrl');
$imgNum = I('imgNum');
$moviesId = I('movies_id');
$chapterId = I('chapter_id');
$sign = I('sign');
if (empty($imgUrl) || empty($moviesId) || empty($chapterId)) {
    outPutError('param error');
}
if ($sign != md5($imgUrl.$moviesId.$chapterId)) {
    outPutError('sign error');
}

//$chapterImgUrl = 'http://share.zrpic.com/jnwtv-livecartoon-api/cp/pageinfolist?lcId='.$lcId.'&plcId='.$plcId.'&cpId='.$cpId.'&sign='.$chapterImgSign;
//$tmp = httpRequest($chapterImgUrl);
//$chapterImgRst = json_decode($tmp, true);

$moviesDir = BASE_PATH.$moviesId;
$chapterDir = $moviesDir.'/'.$chapterId;
if (!is_dir($moviesDir)) {@mkdir($moviesDir);}
if (!is_dir($chapterDir)) {@mkdir($chapterDir);}

try{
    $imgFile = $chapterDir.'/'.md5($moviesId.'/'.$chapterId.'/'.$imgNum).EXT;
    if (file_exists($imgFile)){outPutSuccess(['file'=>2]);}
    else {
        file_put_contents($imgFile, file_get_contents($imgUrl));
    }
    outPutSuccess(['file'=>1]);
} catch(Exception $exception) {
    outPutError($exception->getMessage());
}

function I($name){
    return isset($_POST[$name]) ? $_POST[$name] : null;
}
function outPutError($data){
    $rst = ['code'=>1, 'errInfo'=>$data];
    echo json_encode($rst); exit();
}
function outPutSuccess($data){
    $rst = ['code'=>0, 'data'=>$data];
    echo json_encode($rst); exit();
}
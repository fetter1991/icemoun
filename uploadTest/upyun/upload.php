<?php
use Upyun\Upyun;
use Upyun\Config;

//上传文件到又拍云
class upload {
    public $client;
    public $uploadFile = 'tyson';
    function __construct() {
        require_once 'php-sdk/vendor/autoload.php'; // 针对压缩包安装
        $serviceConfig = new Config('graphmovie-2', 'tyson', 'tyson123456');
        $this->client = new Upyun($serviceConfig);
    }

    /**
     * 上传文件到又拍云
     * @param type $data json格式 包含文件路径名
     */
    public function uploadImg() {
        try {
            $jsonArr = []; //结果储存数组
            $imgData = isset($_POST['imgData']) && !empty($_POST['imgData']) ? $_POST['imgData'] : "";
            if ($imgData == "") { //文件名不能为空
                $jsonArr['status_code'] = "4";
                $returnArr = json_encode($jsonArr);
                echo $returnArr;
                exit();
            }
            $qrcodeFile = '/gifqrcodetmp/qrcode/'; //二维码上传地址
            $qrcodeInsetFile = $qrcodeFile.'qrcode_'.time().'.jpg';
            $isOnFile = $this->client->has($qrcodeInsetFile); //判断文件是否已经上传
            if($isOnFile){
                $jsonArr['status_code'] = "2";
                $returnArr = json_encode($jsonArr);
                echo $returnArr;
                exit();
            }
            $imgContent = file_get_contents($imgData);
            $this->client->write($qrcodeInsetFile, $imgContent);
            $jsonArr['code'] = 200;
            $jsonArr['res'] = base64_encode($qrcodeInsetFile);
            $returnArr = json_encode($jsonArr);
            echo $returnArr;
        } catch (Exception $ex) {
            $jsonArr['status_code'] = "0";
            $jsonArr['errorCause '] = $ex->getMessage();
            $returnArr = json_encode($jsonArr);
            echo $returnArr;
        }
    }
	
}
$file = new upload();
$file->uploadImg();
        

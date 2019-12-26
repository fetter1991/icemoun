<?php
use Upyun\Upyun;
use Upyun\Config;

//上传文件到又拍云
class upyunUpload {
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
    public function upload() {
        try {
            $jsonArr = []; //结果储存数组
            $filename = isset($_GET['filename']) && !empty($_GET['filename']) ? $_GET['filename'] : "";
            if ($filename == "") { //文件名不能为空
                $jsonArr['status_code'] = "4";
                $returnArr = json_encode($jsonArr);
                echo $returnArr;
                exit();
            }
            $file = "../".$filename;
//            $isFile = $this->client->has('../'.$filename); //判断源文件是否存在
            if(!file_exists($file)){
                $jsonArr['status_code'] = "3";
                $returnArr = json_encode($jsonArr);
                echo $returnArr;
                exit();
            }
            $storageFile = $filename; //上传地址
            $isOnFile = $this->client->has($storageFile); //判断文件是否已经上传
            if($isOnFile){
                $jsonArr['status_code'] = "2";
                $returnArr = json_encode($jsonArr);
                echo $returnArr;
                exit();
            }
            $cont = fopen($file, "r");
            fclose($file);
//            $cont = $this->client->read($file);
            if (!empty($cont)) {
                $Result = $this->client->write($storageFile, $cont);
                if (is_array($Result)) {
                    $jsonArr['status_code'] = "1";
                } else {
                    print_r($Result);
                    $jsonArr['status_code'] = "0";
                }
            }
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
$file = new upyunUpload();
$file->upload();
        

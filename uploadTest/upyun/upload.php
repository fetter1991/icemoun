<?php
use Upyun\Upyun;
use Upyun\Config;

//�ϴ��ļ���������
class upload {
    public $client;
    public $uploadFile = 'tyson';
    function __construct() {
        require_once 'php-sdk/vendor/autoload.php'; // ���ѹ������װ
        $serviceConfig = new Config('graphmovie-2', 'tyson', 'tyson123456');
        $this->client = new Upyun($serviceConfig);
    }

    /**
     * �ϴ��ļ���������
     * @param type $data json��ʽ �����ļ�·����
     */
    public function uploadImg() {
        try {
            $jsonArr = []; //�����������
            $imgData = isset($_POST['imgData']) && !empty($_POST['imgData']) ? $_POST['imgData'] : "";
            if ($imgData == "") { //�ļ�������Ϊ��
                $jsonArr['status_code'] = "4";
                $returnArr = json_encode($jsonArr);
                echo $returnArr;
                exit();
            }
            $qrcodeFile = '/gifqrcodetmp/qrcode/'; //��ά���ϴ���ַ
            $qrcodeInsetFile = $qrcodeFile.'qrcode_'.time().'.jpg';
            $isOnFile = $this->client->has($qrcodeInsetFile); //�ж��ļ��Ƿ��Ѿ��ϴ�
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
        

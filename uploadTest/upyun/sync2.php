<?php
set_time_limit(600); // 10分钟
use Upyun\Upyun;
use Upyun\Config;

//同步又拍云目录2，用于去水印图片
class sync {
    public $client;
    public $uploadFile = 'tyson';
    function __construct() {
        require_once 'php-sdk/vendor/autoload.php'; // 针对压缩包安装
        $serviceConfig = new Config('graphmovie-2', 'tyson', 'tyson123456');
        $this->client = new Upyun($serviceConfig);
    }
    
    /**
     * 创建目录
     * @param  string $savepath 要创建的穆里
     * @return boolean          创建状态，true-成功，false-失败
     */
    private function mkdir($savepath){
        $dir = $savepath;
        if(is_dir($dir)){
            return true;
        }
        
        if(mkdir($dir, 0777, true)){
            return true;
        } else {
            $this->error = "目录 {$savepath} 创建失败！";
            return false;
        }
    }
    
    function run ($id){
        try{
            $lockFile = 'lock/'.$id.'-2';
            $lockErrorFile = $lockFile.'-err';
            if (file_exists($lockErrorFile)) {
                echo file_get_contents($lockErrorFile);
                return;
            }
            if (file_exists($lockFile)) {
                echo '正在同步....'; return;
            }
            $path = 'yymovies/'.$id;
            $upPath = 'movies/'.$id;
            
            if(is_dir('../'.$path) && !isset($_GET['update'])){ // 存在目录，
                echo 'ok'; return;
            }
            file_put_contents($lockFile, date('Y-m-d H:i:s'));
            
            
            if (!$this->mkdir('../'.$path)) {
                /* 检测目录是否可写 */
                if (!is_writable($path)) {
                    $this->error = '上传目录 ' . $path . ' 不可写！';
                    file_put_contents($lockErrorFile, $this->error);
                    return false;
                }
                file_put_contents($lockErrorFile, $this->error);
                return false;
            }
            $info = $this->client->info($upPath);
            if ($info && $info['x-upyun-file-type'] == 'folder') {
                $files = $this->client->read($upPath, null, ['X-List-Limit' => 1000]);
                foreach ($files['files'] as $file) {
                    $fileName = $upPath.'/'.$file['name'];
                    $newFileName = $path.'/'.$file['name'];
                    if ($file['type'] == 'N') { // 文件
                        if (!file_exists('../'.$newFileName)) {
                            $this->client->read($fileName, fopen('../'.$newFileName, 'w'));
                        }
                    } else if ($file['type'] == 'F') { // 文件夹
                        if ($this->mkdir('../'.$newFileName)) {
                            $files2 = $this->client->read($fileName, null, ['X-List-Limit' => 1000]);
                            foreach ($files2['files'] as $file2) {
                                $fileName2 = $fileName.'/'.$file2['name'];
                                $newFileName2 = $newFileName.'/'.$file2['name'];
                                if ($file2['type'] == 'N') { // 文件
                                    if (!file_exists('../'.$newFileName2)) {
                                        $this->client->read($fileName2, fopen('../'.$newFileName2, 'w'));
                                    }
                                }
                            }
                        } else {
                            $this->error = '上传目录 ' . $fileName. ' 不可写！';
                            file_put_contents($lockErrorFile, $this->error);
                            return;
                        }
                    }
                }
            }
            @unlink($lockFile);
        }catch (Exception $exception) {
            $code = $exception->getCode();
            file_put_contents($lockErrorFile, $code . '--'.$exception->getMessage());
            if ($code == 404) {
            
            }
        }
    }
}
if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $file = new sync();
    $file->run($id);
}



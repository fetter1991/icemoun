<?php
// +----------------------------------------------------------------------
// |[ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Date: 2016-10-24 下午6:06
//+----------------------------------------------------------------------
namespace Common\Lib\Wethird;
use Common\Lib\Log;
//require_once('config.php');
require_once('crypt/wxBizMsgCrypt.php');

class Wxthird
{

    protected $appid;
    protected $appsecret;

    public function __construct($config)
    {

        $this->appid = trim($config['appid']);
        $this->appsecret = trim($config['appsecret']);
        $memcached = new \Memcached();
        $memcached->addServer('10.135.156.233','11211');

        $this->access_token = $memcached->get($this->appid.'_access_token');
        if (empty($this->access_token)){
            //$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid.'&secret='.$this->appsecret;
            $url = 'http://fulige.h5youx.com/api/api.php?appid='.$this->appid.'&appsecret='.$this->appsecret;
            $res = $this->http_request($url);
            $this->log = new Log(array('log_file_path'=>'./log/token/'));
            $this->log->log('0',$res,date('Y-m-d H:i:s'));
           // $result = json_decode($res, true);
                $this->access_token = $memcached->get($this->appid.'_access_token');
            
        }
    }

    



    //获取用户信息
    public function user_info($openid,$access_token){
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //使用refesh_token获取access_token
    public function oauth2_refresh_token($refresh_token){
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$this->appid.'&grant_type=refresh_token&refresh_token='.$refresh_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //获取access_token
    public function oauth2_access_token($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //公众号服务器配置验证
    public function checkSignature($token){
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $signature = $_GET['signature'];
        $array = array($timestamp,$nonce,$token);
        sort($array);
        $str = implode($array);
        $sign = sha1($str);
        if($sign == $signature){
            return true;
        }else{
            return false;
        }
    }
    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    protected function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        // var_dump($output);
        return $output;
    }
}



?>

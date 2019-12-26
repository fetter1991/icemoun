<?php
header("content-type:text/html;charset=utf-8"); 
require  "./qcloudsms_php/src/index.php";
use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;
/**
 * 
 */
class SendMessageClass {

    protected $appid = ''; //短信应用SDK AppID
    protected $appkey = ''; //短信应用SDK AppKey
    protected $phoneNumber = array(); //电话号码
    protected $templateId = ''; //模板ID
    protected $smsSign = ''; //签名
    protected $smsService = 'Tencent'; //服务商 Tencent:腾讯 目前只有一个
    protected $setMode = '1'; // 发送模式 1：单发 2：群发
    protected $message = ''; // 内容 ['3123','332']

    public function __construct() {
        $smsData = $_POST;
        $this->validate($smsData);
        $this->appid = isset($smsData['appid']) ? $smsData['appid'] :'';
        $this->appkey = isset($smsData['appkey']) ?  $smsData['appkey'] : '';
        $this->phoneNumber = isset($smsData['phoneNumber']) ? $smsData['phoneNumber'] : '';
        $this->templateId = isset($smsData['templateId']) ? $smsData['templateId'] : '';
        $this->smsSign = isset($smsData['smsSign']) ? $smsData['smsSign'] : '';
        $this->smsService = isset($smsData['smsService']) ? $smsData['smsService'] : 'Tencent';
        $this->setMode = isset($smsData['setMode']) ? $smsData['setMode'] : '1';
        $this->message =  isset($smsData['message']) ? $smsData['message'] : '';
    }

    public function validate($smsData) {
        $temp = array(
            'appid','appkey','phoneNumber','message'
        );
        foreach($temp as  $value){
            if(isset($smsData[$value])){
                if(empty($smsData[$value])){
                    $this->returnJson('400',$value.'不能为空');
                }
            }else{
                $this->returnJson('400',$value.'不能为空');
            }
        }
    }

    /**
     * 返回封装方法
     * @param type $code
     * @param type $message
     */
    public function returnJson($code,$message) {
        $gbk =  iconv("GB2312","UTF-8",$message);
        $array['result'] = $code;
        $array['errmsg'] = $gbk;
        $json = json_encode($array);
        echo $json;exit();die;
    }
    
    /**
     * 发短信入口
     */
    public function sendMessage() {
        if ($this->smsService == 'Tencent') {
            if ($this->setMode == 1) {
                $this->sendTenOneMes();
            }else{
                $this->sendTenMoreMes();
            }
        }else{
            $this->returnJson('400','没有这样的服务商');
        }
    }

    /**
     * 单发短信
     */
    public function sendTenOneMes() {
        try {
            $ssender = new SmsSingleSender($this->appid, $this->appkey);
            $result = $ssender->send(0,"86", $this->phoneNumber, $this->message, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            echo $result;
        } catch (\Exception $e) {
            $this->returnJson('400',$e);
        }
    }

    /**
     * 群发短信
     */
    public function sendTenMoreMes() {
        try {
            $msender = new SmsMultiSender($this->appid, $this->appkey);
            $result = $msender->sendWithParam("86", $this->phoneNumber, $this->message, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            echo $result;
        } catch (\Exception $e) {
            $this->returnJson('400',$e);
        }
    }

 

}

$class = new SendMessageClass();
$class->sendMessage();

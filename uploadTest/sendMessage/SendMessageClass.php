<?php
header("content-type:text/html;charset=utf-8"); 
require  "./qcloudsms_php/src/index.php";
use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;
/**
 * 
 */
class SendMessageClass {

    protected $appid = ''; //����Ӧ��SDK AppID
    protected $appkey = ''; //����Ӧ��SDK AppKey
    protected $phoneNumber = array(); //�绰����
    protected $templateId = ''; //ģ��ID
    protected $smsSign = ''; //ǩ��
    protected $smsService = 'Tencent'; //������ Tencent:��Ѷ Ŀǰֻ��һ��
    protected $setMode = '1'; // ����ģʽ 1������ 2��Ⱥ��
    protected $message = ''; // ���� ['3123','332']

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
                    $this->returnJson('400',$value.'����Ϊ��');
                }
            }else{
                $this->returnJson('400',$value.'����Ϊ��');
            }
        }
    }

    /**
     * ���ط�װ����
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
     * ���������
     */
    public function sendMessage() {
        if ($this->smsService == 'Tencent') {
            if ($this->setMode == 1) {
                $this->sendTenOneMes();
            }else{
                $this->sendTenMoreMes();
            }
        }else{
            $this->returnJson('400','û�������ķ�����');
        }
    }

    /**
     * ��������
     */
    public function sendTenOneMes() {
        try {
            $ssender = new SmsSingleSender($this->appid, $this->appkey);
            $result = $ssender->send(0,"86", $this->phoneNumber, $this->message, "", "");  // ǩ������δ�ṩ����Ϊ��ʱ����ʹ��Ĭ��ǩ�����Ͷ���
            echo $result;
        } catch (\Exception $e) {
            $this->returnJson('400',$e);
        }
    }

    /**
     * Ⱥ������
     */
    public function sendTenMoreMes() {
        try {
            $msender = new SmsMultiSender($this->appid, $this->appkey);
            $result = $msender->sendWithParam("86", $this->phoneNumber, $this->message, "", "");  // ǩ������δ�ṩ����Ϊ��ʱ����ʹ��Ĭ��ǩ�����Ͷ���
            echo $result;
        } catch (\Exception $e) {
            $this->returnJson('400',$e);
        }
    }

 

}

$class = new SendMessageClass();
$class->sendMessage();

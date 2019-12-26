<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/24
 * Time: 15:16
 */
namespace Back\Model;
class woZanPay{
    
    private $_config = array(
        'A4C78861DAA6C6046283214DD97BE94B3' => array( // 有影传媒通道
            'appid' => 'A4C78861DAA6C6046283214DD97BE94B3',
            'open_key' => '93fcde288c044c0a747799e9965ec18d',
            'product_no' => 'P000128'
        ),
        'AE88DEF3338728EFEEAA3AA38C60435E1' => array(
            'appid' => 'AE88DEF3338728EFEEAA3AA38C60435E1',
            'open_key' => '2975e76cbd675f30e9717e257ad76830',
            'product_no' => 'P000133'
        )
    );
    protected $appid = 'A4C78861DAA6C6046283214DD97BE94B3';
    public $open_key = '93fcde288c044c0a747799e9965ec18d';
    public $product_no = 'P000128';
    protected $value = array();
    protected $ex = array();
    
    function __construct($appId = 'A4C78861DAA6C6046283214DD97BE94B3'){
        if (isset($this->_config[$appId])) {
            $config = $this->_config[$appId];
            $this->appid = $config['appid'];
            $this->open_key = $config['open_key'];
            $this->product_no = $config['product_no'];
        }
    }
   

    public function order(){
        $this->getJson();
        $this->getExt();
        $this->getSign();
        $url = 'https://pay.szwzpay.com/pay/order?appID='.$this->appid.'&json='.urlencode($this->json).'&ext='.urlencode($this->ext).'&sign='.$this->sign;
        $res = $this->http_request($url);
        return $res;
    }
    

    public function getSign(){

        $openKey = $this->open_key."&";
        $openKey = mb_convert_encoding($openKey, "UTF-8");
        $key = $this->appid.'&'.$this->json.'&'.$this->ext;

        $key = mb_convert_encoding($key, "UTF-8");
        $str = hash_hmac('sha1',$key,$openKey,true);

        $this->sign = (base64_encode($str));
    }

    public function getJson(){
        $this->json =  json_encode($this->value);
    }

    public function getExt(){
        $this->ext = json_encode($this->ex);
    }

    public function setExt($key,$value){
        $this->ex[$key] = $value;
    }

    public function setValue($key,$value){
        $this->value[$key] = $value;
    }
    

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
        return $output;
    }


}
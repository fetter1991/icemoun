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
        ),
        'A4628DD91B42B0F76AEAF1BA47EB0D7CB' => array(
            'appid' => 'A4628DD91B42B0F76AEAF1BA47EB0D7CB',
            'open_key' => 'd67018ff7858775c58e7d58002a4f50a',
            'product_no' => 'P000134'
        ),
        'A50A60479D606D8C71C04AF469DD9967A' => array(
            'appid' => 'A50A60479D606D8C71C04AF469DD9967A',
            'open_key' => '541b01238d91aaef41b34ee01964e510',
            'product_no' => 'P000135'
        ),
        'A99D914AAC9213A9D115257B598998BAA' => array(
            'appid' => 'A99D914AAC9213A9D115257B598998BAA',
            'open_key' => 'e7c21b1c0a54821ac9a0a7617e26c2a9',
            'product_no' => 'P000136'
        ),
        'A5A8CF5FE0ECAD7CC353DC6A1B6F79877' => array(
            'appid' => 'A5A8CF5FE0ECAD7CC353DC6A1B6F79877',
            'open_key' => '67052c69626e7d2596c9c39c044148b8',
            'product_no' => 'P000137'
        ),
        'A0A19183631C0EE4C5E9583B9A1638E63'=>array( //
            'appid' => 'A0A19183631C0EE4C5E9583B9A1638E63',
            'open_key' => '777bea8fce20db7cdc4b66ac49ff6518',
            'product_no' => 'P000140'
        ),
        'ABE28E7816EC81527FAE47238EDF7DFAA' => array( // 白象影库
            'appid' => 'ABE28E7816EC81527FAE47238EDF7DFAA',
            'open_key' => '3bac154b9e21ce13f3ce7678fe2f54da',
            'product_no' => 'P000142'
        ),
        'ABDC14A35D5FDC3AECCE0B7C81D0586AA' => array( // 白象-富民通道
            'appid' => 'ABDC14A35D5FDC3AECCE0B7C81D0586AA',
            'open_key' => 'ad1688c97baf5abc712e6c25935abea2',
            'product_no' => 'P000143'
        ),
        'AFDA937F47459C722C24EE61769AC91E7' => array( // 平安-公众号-芙蓉影库
            'appid' => 'AFDA937F47459C722C24EE61769AC91E7',
            'open_key' => 'a11d43b3dfa18415a1d36a039f9ea5af',
            'product_no' => 'P000144'
        ),
        'A38AE5BE0E16CF4F357EEABBDD7EAD995' => array( // 平安-公众号-白桃影库 2019年7月4日15:48:26
            'appid' => 'A38AE5BE0E16CF4F357EEABBDD7EAD995',
            'open_key' => '5e2642363ec38b1476a4fdd8dcca144a',
            'product_no' => 'P000153'
        ),
        'A58FE508966507EC33CF4B36C83DD4F9F' => array( // 平安-公众号-白桃影库 2019年8月27日11:05:28
            'appid' => 'A58FE508966507EC33CF4B36C83DD4F9F',
            'open_key' => 'fc9162ab1311b4384d91d965d5b8c44b',
            'product_no' => 'P000154'
        ),
        'A8D547732CE2ADA149F7717A03F29B405' => array( // 汇聚-阅影-芙蓉影库 2019年12月4日17:29:41
            'appid' => 'A8D547732CE2ADA149F7717A03F29B405',
            'open_key' => '5b5b640d6ccdece137d1b86eeeef6d97',
            'product_no' => 'P000155'
        ),
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
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/5
 * Time: 11:53
 */
namespace Back\Controller;

use Think\Controller;
use Common\Lib\Log;
use Back\Model\woZanPay;

class RefundController extends CommonController{
    private $_payChannel = array(
        'YYTH' => array( // 有影传媒通道
            'appid' => 'A4C78861DAA6C6046283214DD97BE94B3',
            'open_key' => '93fcde288c044c0a747799e9965ec18d',
            'product_no' => 'P000128'
        ),
        'BETH' => array( // 不二影库
            'appid' => 'AE88DEF3338728EFEEAA3AA38C60435E1',
            'open_key' => '2975e76cbd675f30e9717e257ad76830',
            'product_no' => 'P000133'
        ),
        'YHTH' => array( // 樱花影库
            'appid' => 'A4628DD91B42B0F76AEAF1BA47EB0D7CB',
            'open_key' => 'd67018ff7858775c58e7d58002a4f50a',
            'product_no' => 'P000134'
        )
    );
    /**
     * 退款操作
     */
    public function refund(){
        $log = new Log(array('log_file_path'=>'./log/refund/'));
        $tradeNo = 'YHTH201809041557523399';
        $pre = substr($tradeNo,0,4);
        if (isset($this->_payChannel[$pre])) {
            $pay = $this->_payChannel[$pre];
            $url = 'http://pay.szwzpay.com/refund-order.do';
            $tradeList = array($tradeNo);
            $data = array(
                'app_id'=>$pay['appid'],
                'order_nos'=>$tradeList,
                'sign' => md5($pay['appid'].implode('',$tradeList).$pay['open_key'])
            );
            $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
            $res = $this->_puturl($url,$data);
            var_dump($res);
            $log->log('1',print_r($res,true),date('Y-m-d H:i:s'));
        }
    }
    
    private function _puturl($url,$data){
        $data = json_encode($data);
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT"); //设置请求方式
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
        $output = curl_exec($ch);
        var_dump($output);
        curl_close($ch);
        return json_decode($output,true);
    }
}

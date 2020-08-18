<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/18
 * Time: 10:05
 * 主要处理充值回调工作。
 */
namespace Back\Controller;
use Common\Lib\AnalyseRedis;
use Think\Controller;
use Common\Lib\Log;
use Back\Model\woZanPay;
use Common\Lib\Wethird\Weixin;
use Exception;

class PayController extends Controller {
    
    /**
    * 微信支付回调方法
    */
    public function notifyWX(){
      $log = new Log(array('log_file_path'=>'./log/wpay/'));
      $xml = empty(file_get_contents("php://input"))?'':file_get_contents("php://input");
      libxml_disable_entity_loader(true);
      $data = json_decode(json_encode(simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA)),true);
      $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
      $trade = M('Trade')->where(array('trade_no' => $data['out_trade_no']))->find();

      $rst = array('return_code'=>'SUCCESS', 'return_msg'=>'OK');
      $outXml = $this->ToXml($rst);
      
      if (empty($trade)) {
          exit($outXml);
      }
      
      if ($data['return_code'] != 'SUCCESS' || $data['result_code'] != 'SUCCESS') {
          exit($outXml);
      }
      
      if($trade['pay'] !=$data['total_fee']){
          exit($outXml);
      }
      if ($data['attach'] != md5($trade['trade_no'].$trade['pay'].'PAY@68UC7DX@YYMEDIAS')) {
          $log->log('0',$data['attach'].'!='.md5($trade['trade_no'].$trade['pay'].'PAY@68UC7DX@YYMEDIAS'));
          exit($outXml);
      }
      if($trade['pay_status'] == 1){
          exit($outXml);
      }
      try {
          $rst = $this->_payOk($trade['trade_no']);
          if ($rst !== true) {
              $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
          }
          exit($outXml);
      }catch(Exception $exception){
          $this->_abnormalOrders($data);
          exit($outXml);
      }
    }

    private function ToXml($values){
      $xml = "<xml>";
      foreach ($values as $key=>$val) {
          if (is_numeric($val)){
              $xml.="<".$key.">".$val."</".$key.">";
          }else{
              $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
          }
      }
      $xml.="</xml>";
      return $xml;
    }



    /**
     * 微信支付回调方法
     */
    public function notifyWeixin(){
        $log = new Log(array('log_file_path'=>'./log/pay/'));
        $xml = empty(file_get_contents("php://input"))?'':file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA)),true);
        $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
        $trade = M('Trade')->where(array('trade_no' => $data['out_trade_no']))->find();
        
        if($trade['pay'] !=$data['total_fee']){
            exit('success');
        }
        if($trade['pay_status'] == 1){
            exit('success');
        }
        try {
            $rst = $this->_payOk($trade['trade_no']);
            if ($rst !== true) {
                $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
            }
            exit('success');
        }catch(Exception $exception){
            $this->_abnormalOrders($data);
            exit('success');
        }
    }
    
    private function _abnormalOrders($data){
        $log_file_path      ='./abnormal/';
        if(!is_dir($log_file_path)){
            mkdir($log_file_path,0777,true);
        }
        if(!is_dir($log_file_path)){
            mkdir($log_file_path,0777,true);
        }
        $filename = 'orders.log';
        $open = fopen($log_file_path . $filename, 'a');
        fwrite($open,'Abnormal orders:'.$data['out_trade_no'].'|'.date('Y-m-d H:i:s')."\n");
        fclose($open);
    }
    
    
    /**
     * 我赞支付回调地址
     */
    public function notifyWozan() {
        $log = new Log(array('log_file_path'=>'./log/szwz_pay/'));
        $data = $_POST;
        $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
        $wozan = new woZanPay($data["appId"]);
        $sign = md5($data['orderNo'].$data['userName'].$data['result'].$data['amount'].$data['pay_time'].$data['pay_channel'].$wozan->open_key);
//        file_put_contents('test.log',print_r($data,true).PHP_EOL.$data["appId"].PHP_EOL.$wozan->open_key.PHP_EOL.$sign.PHP_EOL.PHP_EOL,FILE_APPEND);
        if($sign == $data['sign']){
            try {
                // 2018年10月31日19:29:26 增加回调支付时间
                $payTime = strtotime($data['pay_time']);
                if ($payTime == false) {$payTime = NOW_TIME;}
                $rst = $this->_payOk($data['orderNo'], $payTime);
                if ($rst !== true) {
                    $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
                }
                exit('success');
            }catch(Exception $exception){
                $this->_abnormalOrders($data);
                exit('success');
            }
        }
        exit('error');
   }
    
    /**
     * 快支支付回调地址
     */
   public function notifyKuaiZhi(){
       $log = new Log(array('log_file_path'=>'./log/kuaizhi_pay/'));
       $data = $_POST;
       $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
       $rst = array('code'=>0, 'msg'=>'SUCCESS');
       if (!empty($data) && !empty($data['sign'])) {
           ksort($data);
           $str = '';
           foreach ($data as $k => $v) {
               if ($k != "sign" && $v != '' && !is_array($v)) {
                   $str .= $k . '='. $v . '&';
               }
           }
           $str = trim($str, '&');
           $str .= 'b2ea60be9994425886681ed1fce5e70a';
           $sign = md5($str);
           if ($data['sign'] != $sign) {
               $rst['code'] = 1; $rst['msg'] = '签名失败';
               echo json_encode($rst); exit();
           }
           if (!empty($data['out_trade_no'])) {
               $payTime = strtotime($data['payment_time']);
               if ($payTime == false) {$payTime = NOW_TIME;}
               $rst = $this->_payOk($data['out_trade_no'], $payTime);
               if ($rst !== true) {
                   $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
               }
           }
       }
       echo json_encode($rst); exit();
   }
    
    /**
     * 快支支付正式回调地址// payid:13755006   key:6qYfk6F8AesvX1IgAXSs9vLnyuuiB2e4
     */
    public function notifyKuaiZhi2(){
        $log = new Log(array('log_file_path'=>'./log/kuaizhi_pay/'));
        $data = $_POST;
        $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
        $rst = array('code'=>0, 'msg'=>'SUCCESS');
        if (!empty($data) && !empty($data['sign'])) {
            ksort($data);
            $str = '';
            foreach ($data as $k => $v) {
                if ($k != "sign" && $v != '' && !is_array($v)) {
                    $str .= $k . '='. $v . '&';
                }
            }
            $str = trim($str, '&');
            $str .= '6qYfk6F8AesvX1IgAXSs9vLnyuuiB2e4';
            $sign = md5($str);
            if ($data['sign'] != $sign) {
                $rst['code'] = 1; $rst['msg'] = '签名失败';
                echo json_encode($rst); exit();
            }
            if (!empty($data['out_trade_no'])) {
                $payTime = strtotime($data['payment_time']);
                if ($payTime == false) {$payTime = NOW_TIME;}
                $rst = $this->_payOk($data['out_trade_no'], $payTime);
                if ($rst !== true) {
                    $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
                }
            }
        }
        echo json_encode($rst); exit();
    }
    
    /**
     * 我赞3.0 支付回调地址
     */
    public function notifyWozan3(){
        $log = new Log(array('log_file_path'=>'./log/szwz_pay_3.0/'));
        // JSON 形式POST过来的数据
        $content = file_get_contents('php://input');
        $data    = (array)json_decode($content, true);
        $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
        $appkey = 'C1147B5E7007C051296EC116FBA32BC4';
        if (!empty($data)) {
            /*
             *  appid: 900623E9462366FC1403A93AF9E62AFB
             *  appkey: C1147B5E7007C051296EC116FBA32BC4
             *  puid: A2ADA427C5CE4B9BBFF8DBCE15213CC2
             *  "app_id": "xxxxxxxxxx",
             *  "order_no": "xxxxxxxxxxx",
             *  "pay_no": "xxxxxxxxxxx",
             *  "ch_out_trade_order_no": "xxxxxxxxxxxxx",
             *  "pay_amount": 1,
             *  "pay_status": 1,
             *  "order_time": 1565854774,
             *  "pay_time": 1565854777,
             *  "sign": "xxxxxxxxxxx",
             *  "uid": "xxxxxxxxxxx"
             */
            ksort($data, SORT_STRING);
            $signArr = [];
            foreach ($data as $key => $item) {
                if (in_array($key, ['app_id', 'order_no', 'pay_no', 'ch_out_trade_order_no', 'pay_amount', 'pay_status', 'order_time', 'pay_time'])) {
                    $signArr[] = $key.'='.$item;
                }
            }
            if (empty($data['sign']) || strtolower($data['sign']) != md5(implode('&', $signArr) . '&app_key=' . $appkey)) {
                exit('sign error');
            }
            if ($data['pay_status']  == 1) { // 支付成功
                try {
                    $payTime = strtotime($data['pay_time']);
                    if ($payTime == false) {$payTime = NOW_TIME;}
                    $rst = $this->_payOk($data['order_no'], $payTime);
                    if ($rst !== true) {
                        $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
                    }
                    exit('success');
                }catch(Exception $exception){
                    $this->_abnormalOrders($data);
                    exit('success');
                }
            }
        }
        exit('error');
    }
    
    /**
     * 支付宝网关
     */
    public function getwayAlipay(){
        $log = new Log(array('log_file_path'=>'./log/alipay/getway/'));
        // JSON 形式POST过来的数据
        $content = file_get_contents('php://input');
        $data    = (array)json_decode($content, true);
        $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
        exit('success');
    }
    
    /**
     * 支付宝支付成功回调
     */
    public function notifyAlipay(){
        $log = new Log(array('log_file_path'=>'./log/alipay/notify/'));
        $data    = $_POST;
        $log->log('0',print_r($data,true),date('Y-m-d H:i:s'));
        require_once('Comic/Common/Lib/Alipay/aop/AopClient.php');
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmX9V01jGNYMRZKhQUZex8kgW2llnqYFAcsyRrK1LwSx5JvzGn6XjbIr8TUHzu43qNFdBHJccMzRawNU4HlhyZeVErGV7OajrHmBxaPkSeyzavxdQa9t1pH1nMAss0I5+v924pLBsJ9zZixVNj1N3s2UAIAwNpHdI1SD7ALm/DYEnoh5DpKH9fBZsQoTYo97jVi1eghg6ukDCQFq0rFdh2iQJ9zY8i3ooQ3O0MwwBE+OOg7VjMsPbT9NyRBxlq7TF/sz/Ua++HWzA9F0lRwLyfZZ/Sc+WxDEjt5wZIsD1NmCSJN7SMBzxwhl88+/fBW2x8ANac9Ggapi8HviEkT9dhQIDAQAB';
        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
        if ($flag) {
            if ($data['trade_status'] == 'TRADE_FINISHED' || $data['trade_status'] == 'TRADE_SUCCESS') {
                $rst = $this->_tradeCheck($data['out_trade_no'], $data['total_amount'] * 100, strtotime($data['gmt_payment']));
                if ($rst !== true) {
                    $log->log('1',$rst.' ---- '.print_r($data,true),date('Y-m-d H:i:s'));
                }
            }
            exit('success');
        }
        exit('error');
    }
    
    /**
     * 订单成功的处理的中间件
     * @param $tradeNo 订单号
     * @param $totalAmount 支付总金额 （单位分）
     * @param mixed $payTime 付款时间
     * @return bool|string
     */
    private function _tradeCheck ($tradeNo, $totalAmount,$payTime = NOW_TIME) {
        $lastChar = substr($tradeNo, -1);
        if (is_numeric($lastChar)) {
            return $this->_payOk($tradeNo, $payTime);
        } else if ($lastChar == 'T') { // 商品订单处理流程
            return false;
        } else {
            return false;
        }
    }
    
    /**
     * 常规订单处理的中间件
     * @param $tradeNo
     * @param mixed $payTime
     * @return bool|string
     */
   private function _payOk($tradeNo, $payTime=NOW_TIME) {
       $TradeModel = M('Trade');
       $map = array('trade_no'=>$tradeNo);
       $trade = $TradeModel->where($map)->find();
    
//       file_put_contents('./log/pay/test.txt',$TradeModel->getLastSql().PHP_EOL,FILE_APPEND);
       
       if ($trade['pay_status'] == 1) {
           return true;
       } else if ($trade['pay_status'] == 0) {
           $channelId = M('user')->where(array('id'=>$trade['user_id']))->getField('channel_id');
           $TradeModel->where(array('id'=>$trade['id']))->save(array('pay_status'=>1,'pay_time'=>$payTime)); // 修改订单状态
           
           // 2019年10月18日13:58:22 只要VIP时间大于0 ，都加上
           if($trade['deadline_num'] > 0) { //if ($trade['type']==1) { // VIP 支付
               $map = array('user_id'=>$trade['user_id']);
               M('user_info')->where($map)->save(array('is_vip'=>1));
               $Vip = M('vip');
               $one = $Vip->where($map)->find();
               if (empty($one)) {
                   $data= array(
                       'user_id'=>$trade['user_id'],
                       'vip_overdue'=>NOW_TIME + $trade['deadline_num'],
                       'add_time'=>NOW_TIME,
                       'pay_time'=>NOW_TIME
                   );
                   if (false === $Vip->add($data)){
                       return 'vip_add_error';
                   }
               } else {
                   $data = array(
                       'vip_overdue'=>(NOW_TIME > $one['vip_overdue'] ? NOW_TIME : $one['vip_overdue']) + $trade['deadline_num'],
                       'pay_time'=>NOW_TIME
                   );
                   if (false === $Vip->where(array('id'=>$one['id']))->save($data)) {
                       return 'vip_update_err';
                   }
               }
           }
        
           $num = $trade['num'];
           if ($num>0) { // 只要是gold大于0 ，无论哪种情况，都应该加上gold
               M('user_info')->where(array('user_id'=>$trade['user_id']))->setInc('gold',$num);
           }
           
           // 2019年10月18日14:27:18 增加作品单独购买
           if (!empty($trade['buy_info'])) {
               try{
                   $buyInfo = json_decode($trade['buy_info'], true);
                   if (!empty($buyInfo)) {
                       $chapterId = empty($buyInfo['chapter_id']) ? 0 : $buyInfo['chapter_id'];
                       $moviesId = empty($buyInfo['movies_id']) ? 0 : $buyInfo['movies_id'];
                       if (!empty($buyInfo['chapter_id'])) { // 购买一章节
                           $moviesId = M('Chapter')->where(['id'=>$buyInfo['chapter_id']])->getField('movies_id');
                       }
                       if (!empty($chapterId) || !empty($moviesId)) {
                           $data = array(
                               'user_id' => $trade['user_id'],
                               'movies_id' => $moviesId,
                               'chapter_id' => $chapterId,
                               'price' => $trade['pay'],
                               'old_price' => 0,
                               'add_time' => NOW_TIME
                           );
                           @M('UserChapter')->add($data);
                       }
                   }
               } catch (Exception $exception) {}
           }
           
           // 2018年1月4日12:13:33 更新UserInfo 表中 total 字段。
           M('user_info')->where(array('user_id'=>$trade['user_id']))->setInc('total',$trade['pay']);
        
           //  更新渠道数据
           $ChannelData = D('ChannelData');
           $map = array('channel_id'=>$channelId, 'type'=>3, 'open'=>1);  // 这里直接写死为3 3代表充值
           $kou = M('channel_kou')->where($map)->find();
           $kou = empty($kou) ? array('max_v'=>0,'bi'=>0) : $kou;
           $day = date('Y-m-d 00:00:00', NOW_TIME);
           $map = array('channel_id'=>$channelId, 'type'=>3, 'day'=>$day);
           $one = $ChannelData->where($map)->find();
           if (empty($one)) { // 插入
               $data = array(
                   'channel_id'=>$channelId, 'type'=>3,'day'=>$day,
                   'value' => $trade['pay'],
                   'show_value' => getKouStep(0,0,$kou['max_v'],$kou['bi'], $trade['pay']),
                   'add_time'=>NOW_TIME
               );
               if (fasle === $ChannelData->add($data)){
                   return 'channel_data_add_err';
               }
            
           } else { // 更新
               $data = array(
                   'value' => array('exp','value+'.$trade['pay']),
                   'show_value' => array('exp', 'show_value+'.getKouStep($one['value'], $one['show_value'], $kou['max_v'],$kou['bi'],$trade['pay']))
               );
               if (false == $ChannelData->where('id='.$one['id'])->save($data)){
                   return 'channel_data_update_err';
               }
           }

            $appid = M('channel')->where('id ='.$channelId)->getField('appid');
            $admin_url = M('channel')->where('id ='.$channelId)->getField('domen');
            $index_url = !empty($admin_url) ? $admin_url : C('DOMAIN');
           if(!empty($appid)){
               $wx = new Weixin($appid);
               $openId = M('user')->where(array('id'=>$trade['user_id']))->getField('open_id');
               if(!empty($openId)){
                   if ($trade['activity'] == '64' || $trade['activity'] == '63') {
                       $kefuPhone = C('KEFU_PHONE');
                       $blingbling  = empty($kefuPhone) ? "" : " 或者拨打客服电话：".$kefuPhone."，";
                       $str = "感谢购买【有影の茶】！\r\n\r\n您的订单已经开始处理，"
                           .($trade['deadline_num'] > 0 ? "赠送VIP已生效，":"")
                           .($trade['num'] > 0 ? ("赠送金币".$trade['num']."已到账，"):"")
                           ."茶叶预计将于10月8日发出。\r\n请添加客服微信：dfacf222，".$blingbling.
                       "以及时获取物流状态。祝您假期愉快。";
                   } if ($trade['activity'] == '74') {
                       $kefuPhone = C('KEFU_PHONE');
                       $blingbling  = empty($kefuPhone) ? "" : " 或者拨打客服电话：".$kefuPhone."，";
                       $str = "感谢购买【豆瓣电影日历】！\r\n\r\n您的订单已经开始处理，"
                           .($trade['deadline_num'] > 0 ? "赠送VIP已生效，":"")
                           .($trade['num'] > 0 ? ("赠送金币".$trade['num']."已到账，"):"")
                           ."实物预计将于三个工作日内发出。\r\n请添加客服微信：dfacf222，".$blingbling.
                           "以及时获取物流状态。祝您假期愉快。";
                   }  else {
                       $str = "充值成功通知：\n\n";
                       $str .= "充值金额:".($trade['pay']/100)."元\n";
                       if ($trade['num'] > 0) {
                           $str .= "获得金币：".$trade['num']."\n";
                       }
                       if ($trade['deadline_num'] > 0) {
                           $str .= "获得VIP时长".round($trade['deadline_num'] / 86400,1)."天\n";
                       }
                       if (!empty($trade['buy_info'])) {
                           $buyInfo = json_decode($trade['buy_info'], true);
                           if (!empty($buyInfo)) {
                               $chapterId = empty($buyInfo['chapter_id']) ? 0 : $buyInfo['chapter_id'];
                               $moviesId = empty($buyInfo['movies_id']) ? 0 : $buyInfo['movies_id'];
                               $chapterName = '';
                               if (!empty($buyInfo['chapter_id'])) { // 购买一章节
                                   $chapter = M('Chapter')->where(['id'=>$buyInfo['chapter_id']])->find();
                                   $moviesId = $chapter['movies_id'];
                                   $chapterName = $chapter['name'];
                               }
                               if (!empty($moviesId) || !empty($chapterId)) {
                                   $moviesName = M('Movies')->where(['id'=>$moviesId])->getField('name');
                                   $str .= "《{$moviesName}》 {$chapterName} 已经解锁成功！\n";
                               }
                           }
                       }
                       $str .= "\n<a href='https://".$appid.".".$index_url."/index.php?m=Home&c=Movie&a=chapterContinue'>继续阅读</a>\n\n";
                       $str .= "如有疑问，请联系用户中心的联系客服";
                       
                       $kefuPhone = C('KEFU_PHONE');
                       if (!empty($kefuPhone)) {
                           $str .= "; 或者拨打客服电话：".$kefuPhone;
                       }
                   }
                   $wx->send_custom_message($openId,'text',$str);
               }

           }
    
           // 2019年9月30日14:11:04 把这个单独拿出来，是因为APP也有购买消息
           if ($trade['activity'] == '64' || $trade['activity'] == '63') {
               $kefuPhone = C('KEFU_PHONE');
               $blingbling  = empty($kefuPhone) ? "" : " 或者拨打客服电话：".$kefuPhone."，";
               $str = "感谢购买【有影の茶】！\r\n\r\n您的订单已经开始处理，"
                   . ($trade['deadline_num'] > 0 ? "赠送VIP已生效，" : "")
                   . ($trade['num'] > 0 ? ("赠送金币" . $trade['num'] . "已到账，" ): "")
                   . "茶叶预计将于10月8日发出。\r\n请添加客服微信：dfacf222，".$blingbling."以及时获取物流状态。祝您假期愉快。";
               $messageData = [
                   'user_id' => $trade['user_id'],
                   'from_user_id' => 10000,
                   'content' => $str,
                   'type' => 1, 'reply_time' => NOW_TIME
               ];
               M('user_message')->add($messageData);
               M('user_info')->where(['user_id'=>$trade['user_id']])->save(['is_newmsg'=>1]);
           } else if ($trade['activity'] == '74') {
               $kefuPhone = C('KEFU_PHONE');
               $blingbling  = empty($kefuPhone) ? "" : " 或者拨打客服电话：".$kefuPhone."，";
               $str = "感谢购买【豆瓣电影日历】！\r\n\r\n您的订单已经开始处理，"
                   .($trade['deadline_num'] > 0 ? "赠送VIP已生效，":"")
                   .($trade['num'] > 0 ? ("赠送金币".$trade['num']."已到账，"):"")
                   ."实物预计将于三个工作日内发出。\r\n请添加客服微信：dfacf222，".$blingbling.
                   "以及时获取物流状态。祝您假期愉快。";
               $messageData = [
                   'user_id' => $trade['user_id'],
                   'from_user_id' => 10000,
                   'content' => $str,
                   'type' => 1, 'reply_time' => NOW_TIME
               ];
               M('user_message')->add($messageData);
               M('user_info')->where(['user_id'=>$trade['user_id']])->save(['is_newmsg'=>1]);
           }
           
    
           // 2018年12月06日20:57 增加影片收藏统计
           $fromStr = explode('.',$trade['from_str']);
           if ($fromStr[0]=='txt' && isset($fromStr[1]) && is_numeric($fromStr[1])) {
               $Analyse = new AnalyseRedis();
               $Analyse->movieTradeSuccess($fromStr[1], $trade['pay'], date('Ymd',$payTime));
               $Analyse->close();
           }

           return true;
       }
       return 'other pay_status';
   }
}
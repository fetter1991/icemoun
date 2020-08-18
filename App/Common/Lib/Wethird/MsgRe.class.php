<?php
// +----------------------------------------------------------------------
// |[ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Date: 2016-10-25 上午10:15
// +----------------------------------------------------------------------
// | Author: 阿东           email: chenquanlee@foxmail.com
// +----------------------------------------------------------------------
// | description: 第三方微信公众平台，信息回复
// +----------------------------------------------------------------------
namespace Common\Lib\Wethird;
require_once('config.php');
require_once('crypt/wxBizMsgCrypt.php');
use Common\Lib\Log;
use Think\Exception;

class MsgRe
{
    protected $weChat;
    protected $appid;
    protected $log;
    //响应消息
    public function responseMsg()
    {
        $signature  = $_GET['signature'];
        $timestamp  = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $encrypt_type = $_GET['encrypt_type'];
        $msg_signature  = $_GET['msg_signature'];
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)){
            //解密
            if ($encrypt_type == 'aes'){
                $pc = new \WXBizMsgCrypt(Token, EncodingAESKey, AppID);
                $decryptMsg = "";  //解密后的明文
                $errCode = $pc->DecryptMsg($msg_signature, $timestamp, $nonce, $postStr, $decryptMsg);
                $postStr = $decryptMsg;
            }
            $this->log = new Log(array('log_file_path'=>'./log/mp/'));

            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->log->log('0',print_r(array('poststr'=>$postObj),true),date('Y-m-d H:i:s'));
            $RX_TYPE = trim($postObj->MsgType);
            if(empty($_GET['appid'])){$this->log->log('1','appid为空',date('Y-m-d H:i:s'));exit('appid为空');}
            $this->appid = $_GET['appid'];

            if ($this->appid != 'wx570bc396a51b8ff8') { // 微信测试APPID
                $channel = M('channel')->where('appid = "'.$this->appid.'"')->find();
                if($channel['status'] == 0){
                    exit('success');
                }
            }
            
            $this->weChat = new Weixin($this->appid);

            //消息类型分离

            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                case "image":
                    $result = $this->receiveImage($postObj);
                    break;
                case "location":
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknown msg type: ".$RX_TYPE;
                    break;
            }
            //加密
            if ($encrypt_type == 'aes'){
                $encryptMsg = ''; //加密后的密文

                $errCode = $pc->encryptMsg($result, $timestamp, $nonce, $encryptMsg);
                
                $result = $encryptMsg;
            }
            
            
            // 2019年5月22日14:48:59 增加微信可触达消息用户集合
//            if ($this->appid == 'wx553cd76f54d1a426') {
                if (($RX_TYPE == 'event' && ($postObj->Event != 'unsubscribe'))
                    || $RX_TYPE == 'text' || $RX_TYPE == 'image' || $RX_TYPE == 'location' || $RX_TYPE == 'voice' || $RX_TYPE =='link')
                {
                    $this->setActiveUser(strval($postObj->FromUserName));
                } else {
                    if ($RX_TYPE == 'event' && $postObj->Event == 'unsubscribe') { // 如果是取关了的话，则取消
                        $this->setActiveUser(strval($postObj->FromUserName), false);
                    }
                }
                
//            }
            
            
            echo $result;
        }else {
            echo "";
            exit;
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
    
    //接收事件消息
    private function receiveEvent($object)
    {
        $content = "";
        //$content = $object->Event."from_callback";

        switch ($object->Event)
        {
            case "subscribe":
                $content = '关注';
                    //调用微信接口获取用户信息
                    $userinfo = $this->weChat->get_user_info(strval($object->FromUserName));

                    if($userinfo) {
                        //获取渠道信息
                        $channel = M('channel')->where('original_id = "'.strval($object->ToUserName).'"')->find();
                        $domen = empty($channel['domen']) ? C('DOMAIN') : $channel['domen'];
                        $channelDomen = "https://".$this->appid.".".$domen;
                        if($channel['status'] == 0){
                            return 'success';
                        };
                        $rank = $channel['rank'] > 4 ? 4 : $channel['rank'];
                        //获取用户信息
                        $one = M('user')->where('open_id = "'.$userinfo['openid'].'"')->field('id,follow_time')->find();
                        //判断是否第一次进入
                        if(empty($one)){

                            if($channel['is_app']==1 && !empty($userinfo['unionid'])){
                                $where = array('account'=>$userinfo['unionid'],'acc_type'=>1);
                                $count = M('account')->where($where)->count();
                                if(empty($count)){
                                    $user = array(
                                        'open_id'=>$userinfo['openid'],
                                        'channel_id'=>$channel['id'],
                                        'expand_id'=>0,
                                        'is_userinfo'=>0,
                                        'is_follow'=>1,
                                        'follow_time'=>strval($object->CreateTime),
                                        'userinfoJson'=>json_encode($userinfo),
                                        'add_time'=>time(),
                                    );
                                    $userid = M('user')->add($user);
                                    if($userid){
                                        $data = array(
                                            'user_id'=>$userid,
                                            'gold'=>'0',
                                            'avatar'=>$userinfo['headimgurl'],
                                            'nick_name'=>$userinfo['nickname'],
                                            'sex'=>$userinfo['sex'],
                                        );
                                        M('user_info')->add($data);
                                        $this->log->log('0','\'用户添加成功 \''.json_encode($userinfo),date('Y-m-d H:i:s'));
                                    }else{
                                        $this->log->log('1','\'用户添加失败 \''.json_encode($userinfo),date('Y-m-d H:i:s'));
                                    }
                                    $where['user_id'] = $userid;
                                    M('account')->add($where);
                                }else{
                                    $account = M('account')->where($where)->find();
                                    $user = array(
                                        'open_id'=>$userinfo['openid'],
                                        'channel_id'=>$channel['id'],
                                        'expand_id'=>0,
                                        'is_userinfo'=>1,
                                        'is_follow'=>1,
                                        'follow_time'=>strval($object->CreateTime),
                                        'userinfoJson'=>json_encode($userinfo),
                                    );
                                    M('user')->where('id ="'.$account['user_id'].'"')->save($user);

                                }
                            }else{
                                $user = array(
                                    'open_id'=>$userinfo['openid'],
                                    'channel_id'=>$channel['id'],
                                    'expand_id'=>0,
                                    'is_userinfo'=>0,
                                    'is_follow'=>1,
                                    'follow_time'=>strval($object->CreateTime),
                                    'userinfoJson'=>json_encode($userinfo),
                                    'add_time'=>time(),
                                );
                                $userid = M('user')->add($user);
                                if($userid){
                                    $data = array(
                                        'user_id'=>$userid,
                                        'gold'=>'0',
                                        'avatar'=>$userinfo['headimgurl'],
                                        'nick_name'=>$userinfo['nickname'],
                                        'sex'=>$userinfo['sex'],
                                    );
                                    M('user_info')->add($data);
                                    $this->log->log('0','\'用户添加成功 \''.json_encode($userinfo),date('Y-m-d H:i:s'));
                                }else{
                                    $this->log->log('1','\'用户添加失败 \''.json_encode($userinfo),date('Y-m-d H:i:s'));
                                }

                            }
                            $map = array('channel_id'=>$channel['id']);
                            $channelReply = M('reply')->where($map)->find();
                            $diyReplyFlag = false;
                            if (!empty($channelReply) && $channelReply['new_reply'] != '1') {
                                // {"content":"{{userName}}\uff0c\u77e5\u9053\u4f60\u4f1a\u6765\uff0c\u6240\u4ee5\u6211\u4e00\u76f4\u5728\u7b49\u4f60\u3002\n\u63a8\u8350\u51e0\u90e8\u597d\u770b\u7684\u7535\u5f71\uff1a\n1\u3001<a href='https://wx553cd76f54d1a426.jiayoumei-tech.com/index.php?m=Home&c=Movie&a=detail&id=6232'>\u9999\u6e2f\u5947\u6848\u4e4b\u5438\u8840\u8d35\u5229\u738b</a>\u3000\ud83d\ude0d\n\n2\u3001<a href='https://wx553cd76f54d1a426.jiayoumei-tech.com/index.php?m=Home&c=Movie&a=detail&id=6192'>\u6050\u6016\u8352\u6751</a>\u3000\ud83d\udc7b\n\n3\u3001<a href='https://wx553cd76f54d1a426.jiayoumei-tech.com/index.php?m=Home&c=Movie&a=detail&id=6187'>\u90aa\u6076\u7684\u90bb\u5c45</a>\u3000\ud83c\udf83"}
                                $reply = json_decode($channelReply['new_reply'], true);
                                if (!empty($reply['content'])) {
                                    $content = $reply['content'];
                                    $content = str_replace('{{userName}}', $userinfo['nickname'], $content);
                                    $content = str_replace('{{topOfficial}}', "<a href='".$channelDomen."/index.php?m=Home&c=Public&a=stick'>置顶公众号</a>", $content);
                                    $diyReplyFlag = true;
                                }
                            }
                            if (!$diyReplyFlag) {
                                $content = $userinfo['nickname']."，知道你会来，所以我一直在等你。\n\n"
                                    ."先推荐几部热门影片，给你看：\n\n";
                                $map = array('status'=>1, 'rank'=>array('elt',$rank));
                                $hot= M('movies')->where($map)->field('name,id,banner')->order('level desc')->limit('0,5')->select();
                                $icon = array('🔞', '💦','💘','👙', '🔥', '👇🏻');
                                foreach($hot as $key=>$v){
                                    $content .="<a href='".$channelDomen."/index.php?m=Home&c=Movie&a=detail&id=".$v['id']."'>".$icon[$key]." ".$v['name']."</a>\n\n";
                                }
                                $content .= "\n<a href='".$channelDomen."'>👉🏻点我获取更多精彩内容👈🏻</a>\n\n";
                                $content .="<a href='".$channelDomen."/index.php?m=Home&c=Public&a=stick'>置顶公众号</a>，方便下次阅读" ;
                            }
                        }else{
                            if($channel['is_app']==1 && !empty($userinfo['unionid'])){
                                $where = array('account'=>$userinfo['unionid'],'acc_type'=>1);
                                $count = M('account')->where($where)->count();
                                if(empty($count)){
                                    $where['user_id'] = $one['id'];
                                    M('account')->add($where);
                                }
                            }

                            M('user')->where('id = "'.$one['id'].'"')->save(array('is_follow'=>1,'follow_time'=>strval($object->CreateTime),'userinfoJson'=>json_encode($userinfo)));
                            $data = array(
                                'avatar'=>$userinfo['headimgurl'],
                                'nick_name'=>$userinfo['nickname'],
                                'sex'=>$userinfo['sex'],
                            );
                            M('user_info')->where('user_id = "'.$one['id'].'"')->save($data);
    
                            // 2018年11月6日17:32:33 加入DIY 回复信息
                            $map = array('channel_id'=>$channel['id']);
                            $channelReply = M('reply')->where($map)->find();
                            $diyReplyFlag = false;
                            if (!empty($channelReply) && $channelReply['old_reply'] != '1') {
                                $reply = json_decode($channelReply['old_reply'], true);
                                if (!empty($reply['content'])) {
                                    $content = $reply['content'];
                                    $content = str_replace('{{userName}}', $userinfo['nickname'], $content);
                                    $content = str_replace('{{topOfficial}}', "<a href='".$channelDomen."/index.php?m=Home&c=Public&a=stick'>置顶公众号</a>", $content);
                                    $diyReplyFlag = true;
                                }
                            }
                            if (!$diyReplyFlag) {
                                $content = $userinfo['nickname'] . "，知道你会来，所以我一直在等你。\n\n";
                                $userMovie = M('UserMovies')->where(array('user_id' => $one['id']))->order('time desc')->find();
                                $icon = array('🔞', '💦', '💘', '👙', '🔥', '👇🏻');
                                $moviesNum = 5;
                                if (empty($userMovie)) {
                                    $content .= "先推荐几部热门影片，给你看：\n\n";
                                } else {
                                    $content .= "<a href='".$channelDomen."/index.php?m=Home&c=Movie&a=chapterContinue'>🔥点我继续上次阅读🔥</a>\n\n";
                                    $content .= "再推荐几部热门影片，给你看：\n\n";
                                    $moviesNum = 3;
                                }
                                $map = array('status' => 1, 'rank' => array('elt', $rank));
                                $hot = M('movies')->where($map)->field('name,id,banner')->order('level desc')->limit('0,' . $moviesNum)->select();
    
                                foreach ($hot as $key => $v) {
                                    $content .= "<a href='".$channelDomen. "/index.php?m=Home&c=Movie&a=detail&id=" . $v['id'] . "'>" . $icon[$key] . " " . $v['name'] . "</a>\n\n";
                                }
                                $content .= "\n<a href='".$channelDomen . "'>👉🏻点我获取更多精彩内容👈🏻</a>\n\n";
                                $content .= "<a href='".$channelDomen. "/index.php?m=Home&c=Public&a=stick'>置顶公众号</a>，方便下次阅读";
                            }


//                            $pid = M('user_associated')->where(array('user_id'=>$one['id'],'is_follow'=>0))->getField('pid');
////                            file_put_contents('test.log',$one['id']);
//                            if(!empty($pid)){
//                                $open_id = M('user')->where(array('id'=>$pid))->getField('open_id');
//                                $nick_name = M('user_info')->where(array('user_id'=>$pid))->getField('nick_name');
//                                M('user_associated')->where(array('user_id'=>$one['id'],'is_follow'=>0))->save(array('is_follow'=>1));
//                                M('user_info')->where(array('user_id'=>$pid))->setInc('gold',80);
//                                $string ='邀请好友'. $userinfo['nickname']."成功关注,赠送80金币, 请继续邀请好友哦~\n为方便下次阅读，请<a href='".$channelDomen."/index.php?m=Home&c=Public&a=stick'>置顶公众号</a>";
//                                $this->weChat->send_custom_message($open_id,'text',$string);
//
//                                M('user_info')->where(array('user_id'=>$one['id']))->setInc('gold',40);
//                                $msg = "因好友".$nick_name."邀请成功关注，您将获得40金币，同时好友".$nick_name."也将获得80金币奖励，推荐好友即可获得书币，您也可以哦~
//点击链接获取属于您的海报：<a href='".$channelDomen."/index.php?m=Home&c=UC&a=fis'>邀请卡</a>
//为方便下次阅读，<a href='".$channelDomen."/index.php?m=Home&c=Public&a=stick'>请置顶公众号</a>";
//                                $this->weChat->send_custom_message($userinfo['openid'],'text',$msg);
//                            }
                        }
                    }
                break;
            case "unsubscribe":
                $content = '取消关注';
                M('user')->where('open_id = "'.strval($object->FromUserName).'"')->save(array('is_follow'=>0,'unfollow_time'=>strval($object->CreateTime)));
                return 'success';
                break;
            case "CLICK":
                if(strval($object->EventKey) == 'service') {
                    $channel_id = M('channel')->where('original_id = "' . strval($object->ToUserName) . '"')->getField('id');
                    $options = M('channel_options')->field('wechat_num,qq_num')->where('channel_id = "' . $channel_id . '"')->find();

                    $content = '';
                    if (!empty($options['wechat_num'])) {
                        $content .= '客服微信号:' . $options['wechat_num'] . "\n";
                    }
                    if (!empty($options['qq_num'])) {
                        $content .= '客服QQ号:' . $options['qq_num'];
                    }
                    if (empty($content)) {
                        $content = '暂无客服';
                    }
                }
                break;
            case "VIEW":
                $content = 'view';
                break;
            case "SCAN":
                $content = 'scan';
                return ''; // 2019年9月5日15:31:00 去除扫码回复
                break;
            case "LOCATION":
                $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
                break;
            case "scancode_waitmsg":
                $content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
                break;
            case "scancode_push":
                $content = "扫码推事件";
                break;
            case "pic_sysphoto":
                $content = "系统拍照";
                break;
            case "pic_weixin":
                $content = "相册发图：数量 ".$object->SendPicsInfo->Count;
                break;
            case "pic_photo_or_album":
                $content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
                break;
            case "location_select":
                $content = "发送位置：标签 ".$object->SendLocationInfo->Label;
                break;
            default:
                $content = "receive a new event: ".$object->Event;
                break;
        }

        if(is_array($content)){
            if (isset($content[0]['PicUrl'])){
                $result = $this->transmitNews($object, $content);
            }else if (isset($content['MusicUrl'])){
                $result = $this->transmitMusic($object, $content);
            }
        }else{
            $result = $this->transmitText($object, $content);
        }

        return $result;
    }

    //接收文本消息
    private function receiveText($object)
    {
        if(trim($object->ToUserName) == 'gh_3c884a361561'){ // 微信测试username
            $log = new Log(array('log_file_path'=>'./log/test/'));
            $log->log('0',print_r($object,true),date('Y-m-d H:i:s'));
            $keyword = trim($object->Content);
            $content = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
            if(strpos($keyword,'QUERY_AUTH_CODE')!== false){
                $AuthCode     =    str_replace('QUERY_AUTH_CODE:', '', $keyword);
                $content = $AuthCode.'_from_api';
            }
            if(is_array($content)){
                if (isset($content[0])){
                    $result = $this->transmitNews($object, $content);
                }else if (isset($content['MusicUrl'])){
                    $result = $this->transmitMusic($object, $content);
                }
            }else{
                $result = $this->transmitText($object, $content);
            }
            return $result;
        }

        $channel = M('channel')->where('original_id = "'.strval($object->ToUserName).'"')->find();

        if(empty($channel) || $channel['status'] == 0){
            return 'success';
        };

        $content = strval($object->Content);
    
        $domen = empty($channel['domen']) ? C('DOMAIN') : $channel['domen'];
        $channelDomen = "https://".$this->appid.".".$domen;
        
        if ($content == '签到') {
            $data = array(
                array(
                    'title'=>'点击签到，送金币。',
                    "url"=>$channelDomen.'/index.php?m=Home&c=UC&a=sign',
                    "picurl"=>'https://cdn-yp.yymedias.com/public/img/sign-banner.png'
                )
            );
            
            $result = $this->weChat->send_custom_message(strval($object->FromUserName), "news", $data);
            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
            return 'success';
        }
    
        if($content == '新年快乐') {
            $tmpChannelList = [10001,10063,10065,10025,10040,10068,10076,10066,10078,10140,10152,10153,10171,10172,10175,10176,10173,10179,10181,10180,10184,10185,10187,10188,10189,10190,10191,10192,10193,10194,10196,10198,10201,10202,10203,10205];
            if (in_array($channel['id'], $tmpChannelList)){
                if(time() > 1551369600) {
                    $str ="暗号已经过期了哦！";
                    $result = $this->transmitText($object,$str);
                    return $result;
                }
                $user = M('user')->where(array('open_id'=>strval($object->FromUserName)))->field('id')->find();
                if (!empty($user) && !empty($user['id'])) {
                    try {
                        $redis = new \redis();
                        $redis->connect('172.16.16.9', 6379);
                        $redis->auth('crs-pkviqe1h:tujie888#@!');
                        $tmpGoldKey = 'OpenChannel:'.$channel['id'].':addGold';
                        $time = $redis->zScore($tmpGoldKey, $user['id']);
                        if (empty($time)) {
                            $redis->zAdd($tmpGoldKey, time(), $user['id']);
                            $redis->close();
                            M('user_info')->where(array('user_id'=>$user['id']))->setInc('gold', 666);
                            $str ="猪事大吉！【666金币】已经发放至您的账户，请查收！\n\n"
                                ."<a href='".$channelDomen."/index.php?m=Home&c=UC&a=index'>个人中心</a>";
                            $result = $this->transmitText($object,$str);
                            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
                            return $result;
                        } else {
                            $redis->close();
                            $str ="您已经领取过了哦，谢谢支持！";
                            $result = $this->transmitText($object,$str);
                            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
                            return $result;
                        }
                    }catch (Exception $ex) {
                        $str ="金币发放失败，请重试。";
                        $result = $this->transmitText($object,$str);
                        return $result;
                    }
                }
            }
        }
        
//        if($content == 'YY') {
//            if ($channel['is_youying']) {
//                if(time() > 1550073600) {
//                    $str ="暗号已经过期了哦！";
//                    $result = $this->transmitText($object,$str);
//                    return $result;
//                }
//                $user = M('user')->where(array('open_id'=>strval($object->FromUserName)))->field('id')->find();
//                if (!empty($user) && !empty($user['id'])) {
//                    try {
//                        $redis = new \redis();
//                        $redis->connect('172.16.16.9', 6379);
//                        $redis->auth('crs-pkviqe1h:tujie888#@!');
//                        $tmpGoldKey = 'ChangeChannel:'.$channel['id'].':addGold';
//                        $time = $redis->zScore($tmpGoldKey, $user['id']);
//                        if (empty($time)) {
//                            $redis->zAdd($tmpGoldKey, time(), $user['id']);
//                            $redis->close();
//                            M('user_info')->where(array('user_id'=>$user['id']))->setInc('gold', 2000);
//                            $str ="2000金币已经发放到您的账户中\n\n"
//                                ."<a href='".$channelDomen."/index.php?m=Home&c=UC&a=index'>个人中心</a>";
//                            $result = $this->transmitText($object,$str);
//                            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
//                            return $result;
//                        } else {
//                            $redis->close();
//                            $str ="您已经领取过了哦，谢谢支持！";
//                            $result = $this->transmitText($object,$str);
//                            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
//                            return $result;
//                        }
//                    }catch (Exception $ex) {
//                        $str ="金币发放失败，请重试。";
//                        $result = $this->transmitText($object,$str);
//                        return $result;
//                    }
//                }
//            }
//        }
        
        if($content == '中大奖' || $content == '摇摇乐'  || $content == '摇一摇') {
//            if($channel['is_youying'] == 1) { // 针对所有渠道开放。
                $data = array(
                    array(
                        'title'=>'每日摇摇乐',
                        'description'=>'每天免费摇奖，金币赠送不停',
                        "url"=>$channelDomen.'/index.php?m=Home&c=Activity&a=luckyDraw&linkError='.(NOW_TIME + 43200),
                        "picurl"=>'https://cdn-yp.yymedias.com/public/img/yaoyaole.png'
                    )
                );
    
                $result = $this->weChat->send_custom_message(strval($object->FromUserName), "news", $data);
                $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
                return 'success';
//            }
        }
        
        M('message')->add((array)$object);

        $map = array('condition'=>$content,'channel_id'=>$channel['id']);

        $keyword = M('keyword')->where($map)->find();

        if(!empty($keyword)){
            $str ="<a href='".$keyword['url']."'>".$keyword['content']."</a>";

            $result = $this->transmitText($object,$str);
            return $result;
        }
    
        $channelOptions = M('channel_options')->field('is_movies_search')->where(array('channel_id'=>$channel['id']))->find();
        if (empty($channelOptions['is_movies_search'])){ // 如果未开启，则直接返回
            return 'success';
        }
        
        // 2018年11月5日17:52:55 修改成 只能搜索到7级以下的内容
        $rank = $channel['rank'] > 4 ? 4 : $channel['rank'];
//        $rank = $channel['rank'];
        $list = array();
        if(strlen($content) < 20){
            $map = array('name'=>array('like','%'.$content.'%'),'status'=>1, 'rank'=>array('elt',$rank));
            $list = M('movies')->where($map)->limit('0,5')->field('name,id,banner,subtitle')->select();
        }
        $str = '';
        if(empty($list)){
            $map = array('status'=>1, 'rank'=>array('elt', $rank));
            $list = M('movies')->where($map)->field('name,id,banner')->order('hot desc,order_num desc')->limit('0,5')->select();
            $str .="暂无您要的影片\n推荐阅读以下电影\n\n";
            foreach($list as $k=>$v){
                $str .="👉 <a href='".$channelDomen."/index.php?m=Home&c=Movie&a=detail&id=".$v['id']."&seek=1'>".$v['name']."</a>\n\n";
            }
            $str .= "<a href='".$channelDomen."/'>👉 更多精彩点这里 👈</a>\n\n";
//            $str .="暂无您要的影片\n\n <a href='".$channelDomen."/'>👉点击查看更多精彩</a>\n\n";
            $result = $this->transmitText($object,$str);
            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
            return $result;
        } else if(count($list) > 1) { // 2018年10月29日14:24:25 修改成文字链的形式
            $str .="为您找到以下影片\n\n";
            foreach($list as $k=>$v){
                $str .="<a href='".$channelDomen."/index.php?m=Home&c=Movie&a=detail&id=".$v['id']."&seek=1'>👉《".$v['name']."》</a>\n\n";
            }
            $result = $this->transmitText($object,$str);
            $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
            return $result;
        }

        $data = array();
        foreach($list as $v){
            $data[] = array(
                'title'=>'《'.$v['name'].'》 '.$v['subtitle'],
                "url"=>$channelDomen.'/index.php?m=Home&c=Movie&a=detail&id='.$v['id'].'&seek=1',
                "picurl"=>$v['banner']
            );
        }
        

        $result = $this->weChat->send_custom_message(strval($object->FromUserName), "news", $data);
        $this->log->log(0,print_r($result,true),date('Y-m-d H:i:s'));
        return 'success';
//        $keyword = trim($object->Content);
//        if (strstr($keyword, "TESTCOMPONENT_MSG_TYPE_TEXT")){
//            // $content = "这是个文本消息";
//            //$content = $keyword."_callback";
//        }else if(strstr($keyword, "QUERY_AUTH_CODE")){
//            $content = "";
//            //调用客服接口发送消息
//            $authorization_code = str_replace("QUERY_AUTH_CODE:","",$keyword);
//            $weixin = new Wxexploit();
//            $authorization = $weixin->query_authorization($authorization_code);
//            $log = new Log(array('log_file_path'=>'./log/mp/'));
//            $log->log(0,print_r($authorization_code),date('Y-m-d H:i:s'));
//            $result = $weixin->send_custom_message(strval($object->FromUserName), "text", $authorization_code."_from_api", $authorization["authorization_info"]["authorizer_access_token"]);
//
//        }else{
//            $content = date("Y-m-d H:i:s",time())."\n".$object->FromUserName."\n信息返回 测试";
//        }
        //$content = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
//        if(strpos($object->Content,'QUERY_AUTH_CODE')!== false){
//            $AuthCode     =    str_replace('QUERY_AUTH_CODE:', '', $object->Content);
//            $content = $AuthCode.'_from_api';
//        }
//        if(is_array($content)){
//            if (isset($content[0])){
//                $result = $this->transmitNews($object, $content);
//            }else if (isset($content['MusicUrl'])){
//                $result = $this->transmitMusic($object, $content);
//            }
//
//        }else{
//
//            $result = $this->transmitText($object, $content);
//        }




//        return $result;
    }

    //接收图片消息
    private function receiveImage($object)
    {
        $content = array("MediaId"=>$object->MediaId);
        $result = $this->transmitImage($object, $content);
        return $result;
    }

    //接收位置消息
    private function receiveLocation($object)
    {
        $content = "你发送的是位置，经度为：".$object->Location_Y."；纬度为：".$object->Location_X."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收语音消息
    private function receiveVoice($object)
    {
        if (isset($object->Recognition) && !empty($object->Recognition)){
            $content = "你刚才说的是：".$object->Recognition;
            $result = $this->transmitText($object, $content);
        }else{
            $content = array("MediaId"=>$object->MediaId);
            $result = $this->transmitVoice($object, $content);
        }
        return $result;
    }

    //接收视频消息
    private function receiveVideo($object)
    {
        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");
        $result = $this->transmitVideo($object, $content);
        return $result;
    }

    //接收链接消息
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //回复文本消息
    private function transmitText($object, $content)
    {

        if (!isset($content) || empty($content)){
            return "";
        }

        $xmlTpl = "<xml>
	<ToUserName><![CDATA[%s]]></ToUserName>
	<FromUserName><![CDATA[%s]]></FromUserName>
	<CreateTime>%s</CreateTime>
	<MsgType><![CDATA[text]]></MsgType>
	<Content><![CDATA[%s]]></Content>
</xml>";
        if($object){

//            if($object->Event != 'subscribe'){
//                $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $object->Event.'from_callback');
//            }else{
                $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
//            }

        }

        return $result;
    }

    //回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>%s</ArticleCount>
    <Articles>
$item_str    </Articles>
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    //回复音乐消息
    private function transmitMusic($object, $musicArray)
    {
        if(!is_array($musicArray)){
            return "";
        }
        $itemTpl = "<Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
    </Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[music]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复图片消息
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "<Image>
        <MediaId><![CDATA[%s]]></MediaId>
    </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[image]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复语音消息
    private function transmitVoice($object, $voiceArray)
    {
        $itemTpl = "<Voice>
        <MediaId><![CDATA[%s]]></MediaId>
    </Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[voice]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    //回复视频消息
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "<Video>
        <MediaId><![CDATA[%s]]></MediaId>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
    </Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[video]]></MsgType>
    $item_str
</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }
    
    
    /**
     * 2019年5月22日12:11:43 设置微信用户的活跃时间
     * @param $openId
     * @param bool $active
     */
    private function setActiveUser($openId, $active = true){
        try {
            if (empty($openId)) return;
            $user = M('user')->where(array('open_id'=>$openId))->field('id,channel_id')->find();
            if (!empty($user)){
                $redis = new \redis();
                $redis->pconnect('172.16.0.9',6379); // 前端使用到的Redis
                $redis->auth('crs-hrxl79ic:tujie888#@!');
//        $redis->connect('172.16.16.9',6379);
//        $redis->auth('crs-pkviqe1h:tujie888#@!');
                $redis->select(1);
                $key = 'wx:active:users:'.$user['channel_id'];
                
                if ($active) {
                    $time = time() - 48 * 3600;
                    $redis->zAdd($key, NOW_TIME, $user['id']);
                    $redis->zRemRangeByScore($key, 0, $time);
//                    $redis->zDeleteRangeByScore($key, 0, $time);
                } else {
                    $redis->zRem($key, $user['id']);
                }
                
                $redis->close();
            }
            
        } catch (Exception $exception) {
        
        }
    }
   

}
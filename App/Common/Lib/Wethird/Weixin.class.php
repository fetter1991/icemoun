<?php
// +----------------------------------------------------------------------
// |[ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Date: 2016-10-20 下午7:51
// +----------------------------------------------------------------------
// | description: 微信公众号
// +----------------------------------------------------------------------

namespace Common\Lib\Wethird;

class Weixin extends Wxexploit
{
    private $access_token;
    private $appid;
//    private $TokenFile;
    //构造函数，获取Access Token
    public function __construct($appid = NULL)
    {
        parent::__construct();
        if($appid){

            $this->appid = $appid;
//            $this->TokenFile = './api/token/'.$this->appid.'_access_token.json';
            $this->getAuthorizerAccessToken();
        }
    }

    //获取authorizer_access_token
    public function getAAT($refresh=0){
        if($refresh == 1){
            $this->getAuthorizerAccessToken($refresh);
        }
        return $this->access_token;
    }

    //赋值authorizer_access_token
    private function getAuthorizerAccessToken($refresh =0)
    {
        //文件缓存 authorizer_access_token
        //redis缓存
        $redis = new \Redis();
        $redis->connect(C('REDIS.IP'),C('REDIS.PORT'),C('REDIS.TIMEOUT'));
        $redis->auth(C('REDIS.PWD'));
        $redis->select(11);
        $this->access_token = $redis->get('{wechat}:user:access-token:'.$this->appid);
        $expires_time = 0;
        if($refresh == 1){
            $expires_time = $redis->ttl('{wechat}:user:access-token:'.$this->appid);
        }
        //更新component_access_token
        if (empty($this->access_token) || ($refresh ==1 && $expires_time < 6700)){
            $lockKey = '{wechat}:user:access-token:'.$this->appid.'.lock'; // 这里防止两个同时去取，导致最新获取的token被覆盖掉
            $redisLock = $redis->get($lockKey);
            if (!empty($redisLock)) {
                usleep(100000); // 延迟0.1 秒
                $this->access_token = $redis->get('{wechat}:user:access-token:'.$this->appid);
                $redis->close();
                return ;
            }
    
            $redis->setex($lockKey,2,time());
            
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=".$this->component_access_token;
            $data['component_appid'] = $this->component_appid;
            $data['authorizer_appid'] = $this->appid;
            $authorizer_refresh_token = M('channel')->where(array('appid'=>$this->appid))->getField('authorizer_refresh_token');
            $data['authorizer_refresh_token'] = $authorizer_refresh_token;

            $res = $this->http_request($url,json_encode($data));
            $result = json_decode($res, true);

            $this->access_token = $result['authorizer_access_token'];
            if(!empty($this->access_token)){
                $redis->setex('{wechat}:user:access-token:'.$this->appid,7000,$this->access_token);
            }
        }
        $redis->close();
    }

    //登录
    public function login(){

        $code=I('code');
        if(!empty($code)){
            $refresh_token = cookie('refresh_token');
            $openid = cookie('openid');
            if(empty($openid)){
                if(empty($refresh_token)){
                    $access_token = $this->oauth2_access_token($code);
                    cookie('openid',$access_token['openid'],7200);
                    cookie('refresh_token', $access_token['refresh_token'],30*24*3600);
                }else{
                    $access_token = $this->oauth2_refresh_token($refresh_token);
                    if(empty($access_token['openid'])){
                        cookie('refresh_token',null);
                        return $this->login();
                    }
                    cookie('openid',$access_token['openid'],7200);
                }
                $openid = $access_token['openid'];
            }
            $userinfo = $this->get_user_info($openid);
            return $userinfo;
        }else{
            $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appid.'&redirect_uri='.urlencode($redirect_url).'&response_type=code&scope=snsapi_base#wechat_redirect';
            header('Location:'.$url);exit();
        }
    }


    public function get_current_autoreply_info($data=''){
        $url = "https://api.weixin.qq.com/cgi-bin/get_current_autoreply_info?access_token=".$this->access_token;
        $res = $this->http_request($url,$data);
        return json_decode($res, true);
    }
    /*
    测试接口，获取微信服务器IP地址
    */
    public function get_callback_ip()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$this->access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    /*
    *  PART1 用户管理
    */

    //OAuth2
    //生成OAuth2的URL
    public function oauth2_authorize($redirect_url, $scope, $state = NULL)
    {
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=".$scope."&state=".$state."&component_appid=".AppID."#wechat_redirect";
        return $url;
    }

    //生成OAuth2的Access Token
    public function oauth2_access_token($code)
    {   $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$this->appid&secret=$this->appsecret&code=$code&grant_type=authorization_code";
        //$url = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=".$this->appid."&code=$code&grant_type=authorization_code&component_appid=".$this->appid."&component_access_token=".$this->component_access_token."";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //获取用户基本信息（OAuth2 授权的 Access Token 获取 未关注用户，Access Token为临时获取）
    public function oauth2_get_user_info($access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //获取帐号设置的行业信息。
    public function get_industry(){
        $url = 'https://api.weixin.qq.com/cgi-bin/template/get_industry?access_token='.$this->access_token;
        $res = $this->http_request($url);
        return $res;
    }

    //获取帐号的所有模板信息
    public function get_all_Template(){
        $url = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token='.$this->access_token;
        $res = $this->http_request($url);
        return json_decode($res);
    }

    //获取用户基本信息（全局Access Token 获取 已关注用户，注意和OAuth时的区别）
    public function get_user_info($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //批量获取用户基本信息
    public function batchget_user_info($openidlist, $lang = 'zh-CN')
    {
        $openids = array();
        foreach ($openidlist as &$item) {
            $openids[] = array('openid' => $item, 'lang' => $lang);
        }
        $data = json_encode(array('user_list' => $openids));
        $url = "https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=".$this->access_token;
        $res = $this->http_request($url, $data);
        return json_decode($res, true);
    }

    //获取关注者列表
    public function get_user_list($next_openid = NULL)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$this->access_token."&next_openid=".$next_openid;
        $res = $this->http_request($url);
        $list = json_decode($res, true);
        if ($list["count"] == 10000){
            $new = $this->get_user_list($next_openid = $list["next_openid"]);
            $list["data"]["openid"] = array_merge_recursive($list["data"]["openid"], $new["data"]["openid"]); //合并OpenID列表
        }
        return $list;
    }

    /*
    * PART 用户分组
    */
    //创建分组
    public function create_group($name)
    {
        $msg = array('group' => array('name' => $name));
        $url = "https://api.weixin.qq.com/cgi-bin/groups/create?access_token=".$this->access_token;
        $res = $this->http_request($url, json_encode($msg));
        return json_decode($res, true);
    }

    //移动用户分组
    public function update_group_member($openid, $to_groupid)
    {
        $msg = array('openid' => $openid,
            'to_groupid' => $to_groupid);
        $url = "https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=".$this->access_token;
        $res = $this->http_request($url, json_encode($msg));
        return json_decode($res, true);
    }

    //修改分组名
    public function update_group($groupid, $groupname)
    {
        $msg = array('group' => array('id' => $groupid,
            'name' => $groupname)
        );
        $url = "https://api.weixin.qq.com/cgi-bin/groups/update?access_token=".$this->access_token;
        $res = $this->http_request($url, json_encode($msg));
        return json_decode($res, true);
    }

    //查询所有分组
    public function get_groups()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/groups/get?access_token=".$this->access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    /*
    * PART 菜单部分
    */
    //创建菜单
    public function create_menu($button, $matchrule = NULL)
    {
        foreach ($button as &$item) {
            foreach ($item as $k => $v) {
                if (is_array($v)){
                    foreach ($item[$k] as &$subitem) {
                        foreach ($subitem as $k2 => $v2) {
                            $subitem[$k2] = urlencode($v2);
                        }
                    }
                }else{
                    $item[$k] = urlencode($v);
                }
            }
        }

        if (isset($matchrule) && !is_null($matchrule)){
            foreach ($matchrule as $k => $v) {
                $matchrule[$k] = urlencode($v);
            }
            $data = urldecode(json_encode(array('button' => $button, 'matchrule' => $matchrule)));
            $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=".$this->access_token;
        }else{
            $data = urldecode(json_encode(array('button' => $button)));
            $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->access_token;
        }
        $res = $this->http_request($url, $data);
        return json_decode($res, true);
    }

    //获取菜单
    public function get_menu(){
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$this->access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    //删除菜单
    public function del_menu(){
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->access_token;
        $res = $this->http_request($url);
        return json_decode($res, true);
    }

    /* 创建自定义菜单 原始数据
    */
    public function create_menu_raw($menu)
    {
        if (stripos($menu, "matchrule")){
            $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=".$this->access_token;
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$this->access_token;
        }
        $res = $this->http_request($url, $menu);
        return json_decode($res, true);
    }

    /*
    PART 发送消息
    */
    public function send_custom_message($touser, $type, $data)
    {
        $msg = array('touser' =>$touser);
        $msg['msgtype'] = $type;
        switch($type)
        {
            case 'text':
                $msg[$type]    = array('content'=>urlencode($data));
                break;
            case 'news':
                $data2 = array();
                foreach ($data as &$item) {
                    $item2 = array();
                    foreach ($item as $k => $v) {
                        $item2[strtolower($k)] = urlencode($v);
                    }
                    $data2[] = $item2;
                }
                $msg[$type]    = array('articles'=>$data2);
                break;
            case 'music':
            case 'image':
            case 'voice':
            case 'video':
                $msg[$type]    = $data;
                break;
            default:
                $msg['text'] = array('content'=>urlencode("不支持的消息类型 ".$type));
                break;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->access_token;
        return $this->http_request($url, urldecode(json_encode($msg)));
    }


    //高级群发(根据分组)
    public function mass_send_group($groupid, $type, $data)
    {
        $msg = array('filter' => array('group_id'=>$groupid));
        $msg['msgtype'] = $type;

        switch($type)
        {
            case 'text':
                $msg[$type] = array('content'=> $data);
                break;
            case 'image':
            case 'voice':
            case 'mpvideo':
            case 'mpnews':
                $msg[$type] = array('media_id'=> $data);
                break;

        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=".$this->access_token;
        $res = $this->http_request($url, json_encode($msg));
        return json_decode($res, true);
    }

    //发送模版消息
    public function send_template_message($template)
    {
        foreach ($template['data'] as  $k => &$item) {
            $item['value'] = urlencode($item['value']);
        }

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$this->access_token;
        $res = $this->http_request($url, urldecode(json_encode($template)));
        return json_decode($res, true);
    }

    //生成参数二维码
    public function create_qrcode($scene_type, $scene_id)
    {
        switch($scene_type)
        {
            case 'QR_LIMIT_SCENE': //永久
                $msg = array('action_name' => $scene_type,
                    'action_info' => array('scene' => array('scene_id' => $scene_id))
                );
                break;
            case 'QR_SCENE':        //临时
                $msg = array('action_name' => $scene_type,
                    'expire_seconds' => 2592000,   //30天
                    'action_info' => array('scene' => array('scene_id' => $scene_id))
                );
                break;
            case 'QR_LIMIT_STR_SCENE':    //永久字符串
                $msg = array('action_name' => $scene_type,
                    'action_info' => array('scene' => array('scene_str' => strval($scene_id)))
                );
                break;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$this->access_token;
        $res = $this->http_request($url, json_encode($msg));
        $result = json_decode($res, true);
        $imgurl = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($result["ticket"]);
        return $imgurl;
    }


    //长链接转短链接接口
    public function url_long2short($longurl)
    {
        $msg = array('action' => "long2short",
            'long_url' => $longurl
        );
        $url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=".$this->access_token;
        return $this->http_request($url, json_encode($msg));
    }


    /**
     * @return mixed 获取图文信息
     */
    public function get_material_biz($access_token)
    {
        $access_token = $access_token ? $access_token : $this->access_token;
        $data = array(
            'type'=>'news',
            'offset' => 0,
            'count' => 1
        );
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=".$access_token;
        $res = $this->http_request($url, json_encode($data));
        $bizUrl = json_decode($res,true)['item'][0]['content']['news_item'][0]['url'];
        if(preg_match('/biz=(.*)==/is',$bizUrl,$ma)){
            $biz = $ma[1];
        }
        return $biz;
    }


    /**
     * desc:获取微信摇一摇周边的蓝牙信息
     */
    public function get_ibeacon_info($ticket)
    {
        $postData = '{"ticket":"'.$ticket.'","need_poi":1}';
        $url = 'https://api.weixin.qq.com/shakearound/user/getshakeinfo?access_token='.$this->access_token;
        return $this->http_request($url,$postData);
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
        return $output;
    }

    //日志记录
    private function logger($log_content)
    {
        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL
            $max_size = 500000;
            $log_filename = "log.xml";
            if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}
            file_put_contents($log_filename, date('Y-m-d H:i:s').$log_content."\r\n", FILE_APPEND);
        }
    }
}

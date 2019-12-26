<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/18
 * Time: 16:36
 */
namespace Back\Controller;
use Think\Controller;
use Common\Lib\Wethird\Weixin;
use Common\Lib\Wethird\Wxexploit;
class WechatController extends CommonController{
    public function index(){
        $this->display();
    }

    public function custom(){
        $wx = new Weixin();
        $msg = $wx->send_custom_message('oioVjwc8u61bcu38arEjq1T26IIo','text','holle\n word');var_dump($msg);die;
    }

    //公众号登录授权
    public function login()
    {
        $user_id = session('user_id');
        $wxthird = new Wxexploit();
        $info = $wxthird->login();
        if(is_array($info)){
            $data['nick_name'] = $info['authorizer_info']['authorizer_info']['nick_name'];
            $data['original_id'] = $info['authorizer_info']['authorizer_info']['user_name'];
            $data['appid'] = $info['authorization']['authorization_info']['authorizer_appid'];
            $data['wechat_num'] = $info['authorizer_info']['authorizer_info']['alias'];
            $data['authorizer_refresh_token'] = $info['authorization']['authorization_info']['authorizer_refresh_token'];
            $data['avatar'] = $info['authorizer_info']['authorizer_info']['head_img'];

            $authorizer_access_token = $info['authorization']['authorization_info']['authorizer_access_token'];
            $expires_time = time();
            file_put_contents('./api/token/'.$data['appid'].'_access_token.json', '{"authorizer_access_token": "'.$authorizer_access_token.'", "expires_time": '.$expires_time.'}');

            if(isset($channelList[$data['appid']])){
                $count = M('channel')->where(array('appid'=>$data['appid']))->count();
                if(!empty($count)){
                    echo '渠道以同步';
                }

                $data['id'] = $channelList[$data['appid']]['id'];
                $data['password'] = $channelList[$data['appid']]['pwd'];
                $data['account'] = $info['authorizer_info']['authorizer_info']['nick_name'];
                M('channel')->add($data);

                $options['attention_img'] = $info['authorizer_info']['authorizer_info']['qrcode_url'];
                $options['icon'] = $info['authorizer_info']['authorizer_info']['head_img'];
                $options['channel_id'] = $channelList[$data['appid']];

                M('channelOptions')->add($options);
                redirect(U('Channel/user'));
            }else{
                echo '非法渠道';
            }
            redirect(U('Channel/user'));

        }else{
//            $jumpurl = "<a href='$info'><img src='https://open.weixin.qq.com/zh_CN/htmledition/res/assets/res-design-download/icon_button3_1.png' alt=''>";
//            $jumpurl .= "</a>";
            //header('location:'.$info);
            $jumpurl = "<a href='$info' class='btn btn-primary btn-flat'><span>开始授权</span>";
            $jumpurl .= "</a>";
            $this->ajaxReturn(array('url'=>$jumpurl,'status'=>true));
        }
    }

    public function getWeChartUrl() {
        $wxthird = new Wxexploit();
        $info = $wxthird->login();
        if(is_array($info)){
            if($info['authorizer_info']['authorizer_info']['service_type_info']['id'] != 2 || $info['authorizer_info']['authorizer_info']['verify_type_info']['id'] == -1){
                echo '<script>alert("平台目前仅支持认证服务号");window.location.href="'.U("Channel/user").'";</script>';exit();
            }
            $data['nick_name'] = $info['authorizer_info']['authorizer_info']['nick_name'];
            $data['original_id'] = $info['authorizer_info']['authorizer_info']['user_name'];
            $data['appid'] = $info['authorization']['authorization_info']['authorizer_appid'];
            $data['wechat_num'] = $info['authorizer_info']['authorizer_info']['alias'];
            $data['authorizer_refresh_token'] = $info['authorization']['authorization_info']['authorizer_refresh_token'];
            $data['avatar'] = $info['authorizer_info']['authorizer_info']['head_img'];
            $authorizer_access_token = $info['authorization']['authorization_info']['authorizer_access_token'];
            $expires_time = time();
            $Channel = M('channel');
            $find = $Channel->where(array('appid'=>$info['authorization']['authorization_info']['authorizer_appid']))->find();
            if(!empty($find)){
                file_put_contents('./api/token/'.$data['appid'].'_access_token.json', '{"authorizer_access_token": "'.$authorizer_access_token.'", "expires_time": '.$expires_time.'}');
                M('channel')->where(array('appid'=>$info['authorization']['authorization_info']['authorizer_appid']))->save($data);
                $options = array();
                $options['attention_img'] = $info['authorizer_info']['authorizer_info']['qrcode_url'];
                $options['icon'] = $info['authorizer_info']['authorizer_info']['head_img'];
                M('channelOptions')->where(array('channel_id'=>$find['id']))->save($options);
                $this->success('授权成功',U('Channel/user'));
            }else{
                 $this->error('该公众号未绑定后台',U('Channel/user'));
            }
        }else{
            $this->ajaxReturn(array('url'=>$info,'status'=>true));
        }
    }

    public function test(){
        $user_id = '10069';
        $channel = M('channel')->where('id = '.$user_id)->field('appid')->find();
        $wx = new Weixin($channel['appid']);

        $res = $wx->del_menu();
        var_dump($res);
    }
}
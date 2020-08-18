<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/26 0026
 * Time: 16:40
 */

namespace Back\Controller;
use Think\Controller;
use Common\Lib\Wethird\Weixin;
use Exception;
class MaintainedController extends CommonController
{
    public function index(){
        $this->display();
    }

    public function createMenus(){
        $channel = M('channel')->where('status = 1')->field('id,nick_name')->select();
        $this->ajaxReturn($channel);
    }


    public function createMenu(){
        $channel_id = I('post.channel_id');


        $channel = M('channel')->where('id = '.$channel_id)->field('appid,nick_name')->find();
        if(empty($channel['appid']) ){
            $this->ajaxReturn(array('errcode'=>1,'errmsg'=>'appid为空','nick_name'=>$channel['nick_name']));
        }
        $wx = new Weixin($channel['appid']);
        $admin_url = M('channel')->where('id ='.$channel_id)->getField('domen');
        $index_url = !empty($admin_url) ? $admin_url : C('DOMAIN');
        $url = $channel['appid'].'.'.$index_url;
        

        $str = '{"button":[
            {
                "type": "view",
                "name": "阅读记录",
                "url": "https://'.$url.'/index.php?m=Home&c=My&a=index"
            },
            {
                "type": "view",
                "name": "看电影",
                "url": "https://'.$url.'"
            },
            {
                "name": "用户中心",
                "sub_button": [
                    {
                        "type": "view",
                        "name": "个人中心",
                        "url": "https://'.$url.'/index.php?m=Home&c=UC&a=index"
                    },
                    {
                        "type": "view",
                        "name": "我要充值",
                        "url": "https://'.$url.'/index.php?m=Home&c=UC&a=recharge"
                    },
                    {
                        "type": "click",
                        "name": "联系客服",
                        "key": "service"
                    }
                ]
            }
            ]}';
        $res = $wx->create_menu_raw($str);
        $res['nick_name'] = $channel['nick_name'];
        $this->ajaxReturn($res);
    }

    public function abnormalList(){
        $log_file_path      ='./abnormal/';
        $filename = 'orders.log';
        $open = fopen($log_file_path . $filename, 'r');
        $str = trim(fread($open,10*1024*1024));
        $arr = array();
        if(!empty($str)){
            $arr = explode("\n",$str);
        }
        fclose($open);
        $this->assign('list',$arr);
        $this->display();
    }

    public function repairOrder(){
        $value = I('post.value');
        $start = strpos($value,':')+1;
        $end = strpos($value,'|');
        $length = strlen($value)-(strlen($value)-$end)-$start;
        $trade_no= substr($value,$start,$length);
        $trade = M('trade')->where('trade_no = "'.$trade_no.'"')->find();
        if($trade['pay_status'] == 0){
            try{
                M('trade')->where('trade_no = "'.$trade_no.'"')->save(array('pay_status'=>1));
                $userinfo =  M('user_info');
                $user_info = $userinfo->where('user_id = "'.$trade['user_id'].'"')->find();
                if($trade['type'] == 0){
                    $judge=$userinfo->where('user_id = "'.$trade['user_id'].'"')->setInc('gold',$trade['num']);
                }elseif($trade['type'] == 1){
                    if($user_info['is_vip'] == 0){//第一次充值vip
                        $userinfo->where('user_id = "'.$trade['user_id'].'"')->save(array('is_vip'=>2));
                        $vipData = array(
                            'user_id'=>$trade['user_id'],
                            'vip_overdue'=>strtotime('+ 1 year'),
                            'is_annua'=>'1',
                            'annua_overdue'=>strtotime('+ 1 year'),
                            'add_time'=>time(),
                            'pay_time'=>time()
                        );
                        $judge=M('vip')->add($vipData);
                    }elseif($user_info['is_vip'] == 3){//vip过期再次充值
                        $vipData = array(
                            'vip_overdue'=>strtotime('+ 1 year'),
                            'is_annua'=>'1',
                            'annua_overdue'=>strtotime('+ 1 year'),
                            'pay_time'=>time()
                        );
                        $judge=M('vip')->where('user_id = "'.$trade['user_id'].'"')->save($vipData);
                    }elseif($user_info['is_vip'] == 2 || $user_info['is_vip'] == 1){
                        $vip = M('vip')->where('user_id = "'.$trade['user_id'].'"')->find();
                        $vipData = array(
                            'vip_overdue'=>strtotime(date('Y-m-d H:i:s',$vip['vip_overdue']).'+ 1 year'),
                            'is_annua'=>'1',
                            'annua_overdue'=>strtotime(date('Y-m-d H:i:s',$vip['vip_overdue']).'+ 1 year'),
                            'pay_time'=>time()
                        );
                        $judge =M('vip')->where('user_id = "'.$trade['user_id'].'"')->save($vipData);
                    }
                }
                if(!$judge){
                    throw new Exception("error");
                }
            }catch(Exception $exception){
                $this->ajaxReturn(array('code'=>0,'msg'=>'补单失败'));
            }
        }else{
            try{
                $userinfo =  M('user_info');
                $user_info = $userinfo->where('user_id = "'.$trade['user_id'].'"')->find();
                if($trade['type'] == 0){
                    $judge=$userinfo->where('user_id = "'.$trade['user_id'].'"')->setInc('gold',$trade['num']);
                }elseif($trade['type'] == 1){
                    if($user_info['is_vip'] == 0){//第一次充值vip
                        $userinfo->where('user_id = "'.$trade['user_id'].'"')->save(array('is_vip'=>2));
                        $vipData = array(
                            'user_id'=>$trade['user_id'],
                            'vip_overdue'=>strtotime('+ 1 year'),
                            'is_annua'=>'1',
                            'annua_overdue'=>strtotime('+ 1 year'),
                            'add_time'=>time(),
                            'pay_time'=>time()
                        );
                        $judge=M('vip')->add($vipData);
                    }elseif($user_info['is_vip'] == 3){//vip过期再次充值
                        $vipData = array(
                            'vip_overdue'=>strtotime('+ 1 year'),
                            'is_annua'=>'1',
                            'annua_overdue'=>strtotime('+ 1 year'),
                            'pay_time'=>time()
                        );
                        $judge=M('vip')->where('user_id = "'.$trade['user_id'].'"')->save($vipData);
                    }elseif($user_info['is_vip'] == 2 || $user_info['is_vip'] == 1){
                        $vip = M('vip')->where('user_id = "'.$trade['user_id'].'"')->find();
                        $vipData = array(
                            'vip_overdue'=>strtotime(date('Y-m-d H:i:s',$vip['vip_overdue']).'+ 1 year'),
                            'is_annua'=>'1',
                            'annua_overdue'=>strtotime(date('Y-m-d H:i:s',$vip['vip_overdue']).'+ 1 year'),
                            'pay_time'=>time()
                        );
                        $judge =M('vip')->where('user_id = "'.$trade['user_id'].'"')->save($vipData);
                    }
                }
                if(!$judge){
                    throw new Exception("error");
                }
            }catch(Exception $exception){
                $this->ajaxReturn(array('code'=>0,'msg'=>'补单失败'));
            }
        }

        $key = file_get_contents('./abnormal/orders.log');
        $arr = explode(PHP_EOL,$key);
        $k = array_search($value,$arr);
        unset($arr[$k]);
        foreach($arr as $k=>$v){
            if(empty($v)){
                unset($arr[$k]);
            }
        }
        $str = join(PHP_EOL,$arr);
        file_put_contents('./abnormal/orders.log',$str);
        $this->ajaxReturn(array('code'=>200,'msg'=>'补单成功'));

    }

    
}
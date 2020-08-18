<?php

function getReadMoney($val){
    return round($val/100,2);
}

/**
 *
 */
function getLeftNav(){
    $AdminNav = D('AdminNav');
    $map = array('status'=>1, 'pid'=>0);
    $user_id = session('user_id');
    if($user_id!=1){
        $where['a.id']=session('user_id');
        $where['c.status']=1;
        $res=M('admin as a ')
            ->join('yy_auth_group_access as b on a.id=b.uid')
            ->join('yy_auth_group as c on b.group_id =c .id ')
            ->where($where)
            ->field('c.rules')
            ->find();
        if(!empty($res['rules'])){
            $res['rules']='1,'.$res['rules'];
        }
        $map['id']=array('in',$res['rules']);
    }
    $list = $AdminNav->where($map)->order('order_number desc')->select();

    foreach ($list as $key=>$item) {
        $map['pid'] = $item['id'];
        $own = $AdminNav->where($map)->order('order_number desc')->select();
        $list[$key]['active'] = '';
        if (!empty($own)) {
            $list[$key]['href'] = 'javascript:void(0);';
            foreach($own as $k=>$o) {
                $own[$k]['active'] = '';
                $own[$k]['href'] = U($o['mca']);
                $tmp = explode('/',$o['mca']);
                if (CONTROLLER_NAME == $tmp[1] && isset($tmp[2]) && ACTION_NAME == $tmp[2]) {
                    $own[$k]['active'] = 'active open';
                    $list[$key]['active'] = 'active open';
                }
            }
        } else {
            $tmp = explode('/',$item['mca']);
            $list[$key]['href'] = U($item['mca']);
            if (CONTROLLER_NAME == $tmp[1] && isset($tmp[2]) && ACTION_NAME == $tmp[2]) {
                $list[$key]['active'] = 'active open';
            }
        }
        $list[$key]['own'] = $own;
    }


    return $list;
}

function action_AuthCheck($ruleName,$userId,$relation='or'){
    //$relation = or|and; //默认为'or' 表示满足任一条规则即通过验证; 'and'则表示需满足所有规则才能通过验证
    $Auth = new \Think\Auth();
    
    if(empty($userId)){ //用户ID判断，没有就取当前登录的用户ID
        $userId = session('userid');
    }
    $type=1; //分类-具体是什么没搞懂，默认为:1
    $mode='url'; //执行check的模式,默认为:url
    
    return $Auth->check($ruleName,$userId,$type,$mode,$relation);
}


function getFomartDate($time=NOW_TIME, $fomart='Y-m-d H:i:s', $str=''){
    switch ($fomart) {
        case 'd+':
            $fomart = 'Y-m-d 00:00:00'; break;
        case 'd':
            $fomart = 'Y-m-d'; break;
        case 'm+':
            $fomart = 'Y-m-01 00:00:00'; break;
        case 'm':
            $fomart = 'Y-m-01'; break;
    }
    if (!empty($str)) {
        $tmp = strtotime($str, $time);
        return date($fomart, $tmp);
    }
    return date($fomart, $time);
}

/**
 *    作用：以post方式提交xml到对应的接口url
 *
 */
function http_request($url,$data = null,$header=null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if(!empty($header)){
        curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
    }

    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}



//获取所属页面
function  get_adv_page($page_id){
    $page_name='无';
    if($page_id==1){
        $page_name='首页';
    }
    if($page_id==2){
        $page_name='阅读页';
    }
    if($page_id==3){
        $page_name='个人中心';
    }
    return $page_name;
}

//获取播放方式
function  get_adv_show_type($type){
    $show_type='无';
    if($type==1){
        $show_type='图片轮播';
    }
    if($type==2){
        $show_type='单图播放';
    }
    return  $show_type;
}

//获取反馈人的类型
function getTypes($typeid){
    return empty($typeid)?'<label class="am-btn am-btn-secondary am-btn-xs">用户</label>':'<label class="am-btn am-btn-warning am-btn-xs">商户</label>';
}

//获取反馈人名字
function getname($userid){
    if (!empty($userid)){
        $nickname=M('user_info')->field('nick_name')->where("user_id=".$userid)->find();
    }else{
        return '无名氏';
    }
    return $nickname['nick_name'];
}

//获取反馈渠道
function getchannel($channelid){
    $channel=M('channel')->field('nick_name')->where("id=".$channelid)->find();
    return $channel['nick_name'];
}

//获取渠道会员管理的公众号个数
function getChannelNum($memberid){
    $num=M('channel')->where("member_id={$memberid} and status=1")->count();
    return $num;
}

//获取当前广告位使用的数量
function get_adv_position_count($adv_position_id){
    $count=0;
    if($adv_position_id>0){
        $where['status']=1;
        $where['adv_position_id']=$adv_position_id;
        $where['start_time'] = array(array('elt',time()),array('eq',0),'or');
        $where['end_time'] =  array(array('egt',time()),array('eq',0),'or');
        $count=M('advertisement')->where($where)->count();
    }

    return $count;
}
//获取当前广告位排期的数量
function get_adv_position_wait($adv_position_id){
    $count=0;
    if($adv_position_id>0){
        $where['status']=1;
        $where['adv_position_id']=$adv_position_id;
        $where['start_time']=array('gt',time());
       
        $count=M('advertisement')->where($where)->count(1);
    }
    
    return $count;
}

/**
 * 将ip地址转换成int型
 * @param $ip  ip地址
 * @return number 返回数值
 */
function get_iplong($ip){
    //bindec(decbin(ip2long('这里填ip地址')));
    //ip2long();的意思是将IP地址转换成整型 ，
    //之所以要decbin和bindec一下是为了防止IP数值过大int型存储不了出现负数。
    return bindec(decbin(ip2long($ip)));
}

//获取已经关注粉丝的渠道名称
function getChannelName($channelid){
    if (!empty($channelid)){
        $channel=M('channel')->field('nick_name')->where('id='.$channelid)->find();
    }else{
        return '未知渠道';
    }
    return  $channel['nick_name'];
}

//获取已经关注粉丝的渠道名称
function getFeedbackChannelName($userid){
    $channelid=M('user')->field('channel_id')->where('id='.$userid)->find();
    $channelid=$channelid['channel_id'];
    $channel=M('channel')->field('nick_name')->where('id='.$channelid)->find();
    return  $channel['nick_name'];
}
//获取粉丝的id
function getFansId($userid){
    $user=M('user_info')->field('user_id')->where('id='.$userid)->find();
    return $user['user_id'];
}


//获取用户是否关注
function getFollow($userid){
    $userfollow=M('user')->field('is_follow')->where('id='.$userid)->find();
    return empty($userfollow['is_follow'])?'否':'是';
}

//获取用户的充值金额
function getMoney($userid){
    $money=M('trade')->where('user_id='.$userid.' and pay_status=1')->sum('pay');
    $money=$money/100;
    return !empty($money)?'<label style="cursor: default" class="am-btn am-btn-secondary am-btn-xs">'.$money.'</label>':'暂无充值';
}
//获取会员等级
function getLevel($vip){
    return empty($vip)?'普通会员':'超级会员';
}
//获取反馈用户的名字
function getFeedbackName($userid){
    if (!empty($userid)) {
        $userinfo=M('user')->field('channel_id')->where('id='.$userid)->find();
    }
    $channelid=$userinfo['channel_id'];
    if (!empty($channelid)) {
        $channelinfo=M('channel')->field('nick_name')->where('id='.$channelid)->find();
    }
    return $channelinfo['nick_name'];
}
//获取活动名称
function getActivity($activityid){
    if (!empty($activityid)){
        $activity=M('activity')->field('title')->where('id='.$activityid)->find();
    }else{
        return '暂无活动名称';
    }
    return $activity['title'];
}
//获取内推渠道名称
function getInnerChannel($channel_id){
    $channel=M('channel')->field('nick_name')->where('id='.$channel_id)->find();
    return $channel['nick_name'];
}



function getKouStep ($true, $show, $max_v,$bi, $step=1){
    if (empty($bi)) return $step; // 如果比为0，则代表不扣量
    if (($true+$step) <= $max_v) {return $step;}
    $bi = $bi/1000;
    $idend = $true ? $true : 1 ;
    $true_bi = ($true - $show ) / $idend;
    if ($true_bi<$bi) { // 直接扣，没的说
        return  $step-floor($step * $bi);
    }
    return $step;
}

//获取充值用户
function getUser($userid){
    $nick_name=M('user_info')->where('user_id='.$userid)->getField('nick_name');
    return empty($nick_name)?'暂无姓名':$nick_name;
}

function getListUrl(){
    $url = array(
        'yymedias.com',
        'jiayoumei-tech.com',
        'qzwh888.com'
    );
    return $url;
}


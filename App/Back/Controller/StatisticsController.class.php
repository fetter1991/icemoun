<?php

/**
 * 数据分析站
 * 
 *  
 * @author      tsj 作者
 * @version     1.0 版本号
 */

namespace Back\Controller;

use Common\Lib\Redis;

class StatisticsController extends CommonController {

    public $redis = '';

    public function __construct() {
        parent::__construct();
        $this->redis = new Redis();
    }

    /**
     * 日活、日增、日增关注统计页面
     */
    public function activeConsume() {
   
    
        //所有渠道
        $channellist=M('channel')->field('id,nick_name,member_id')->select();
        $vipCont = M('member')->where('status=1')->field('uid,user,pid')->select();
        $newChanel = array();
        $channellistarr = array();
        foreach ($vipCont as $key => $value) {
            foreach ($channellist as $k => $vl) {
                if ($value['uid'] == $vl['member_id']) {
                    $newChanel[$key]['uid'] = $value['uid'];
                    $newChanel[$key]['pid'] = $value['pid'];
                    $newChanel[$key]['username'] = $value['user'];
                    $newChanel[$key]['channel'][] = $vl;
                    $newChanel[$key]['over'] = !empty($newChanel[$key]['over']) ? $newChanel[$key]['over'] . "," . $vl['id'] : $vl['id'];
                }
            }
        }
        foreach($newChanel as $k => &$valVip){
            foreach($newChanel as $valss){
                if($valVip['uid'] == 6 && $valss['pid'] == '6'){
                    $valVip['channel']  = array_merge($valVip['channel'],$valss['channel']);
                    $valVip['over'] =   $newChanel[$k]['over'] . "," . $valss['over'] ;
                }
            }
        }
        foreach($channellist as $val){
            if($val['member_id'] == 0){
                $channellistarr[] = $val;
            }
        }
        $this->assign('channellistNew',$channellistarr);
        $this->assign('Viplist',$newChanel);
        $this->assign('channellist',$channellist);
        
        $this->display();
    }

    /**
     * 金币消费页
     */
    public function goldConsume() {
        $channellist = M('channel')->select();
        $this->assign('channellist', $channellist);
        $this->display();
    }

    /**
     * 活越/新增/引流 数据统计，ajax方法
     */
    public function rihuoConsume() {
        if (!IS_AJAX) {
            $data['code'] = 0;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $channel_id = !empty(I('post.channelId')) ? I('post.channelId') : 0;
        
        $expend_id = I('post.expend');
        $data['code'] = 200;
        $ruhuo = array();
        $drainage = array();
        $follow = array();
        if($channel_id != 0){
            $newRihuo = array();
            $newdrainage = array();
            $newfollow = array();
            $channel_arr = explode(',', $channel_id);
            foreach($channel_arr as $value){
                if($value != ''){
                    $newRihuo[] = $this->getRihuo($value, $expend_id); //日活
                    $newdrainage[] = $this->todayZ($value, $expend_id); //日活
                    $newfollow[] = $this->todayGz($value, $expend_id); //日活
                }
            }
            
         
            foreach($newRihuo as $key => $valueHuo){
                foreach ($valueHuo as $kRh => $vRh) {
                    $ruhuo[$kRh] += $vRh;
                }
            }
            foreach($newdrainage as $key2 => $valuedra){
                foreach ($valuedra as $kdra => $vdra) {
                    $drainage[$kdra] += $vdra;
                }
            }
            foreach($newfollow as $key3 => $valuefollow){
                foreach ($valuefollow as $kfollow => $vfollow) {
                    $follow[$kfollow] += $vfollow;
                }
            }
            
        }else{
            $ruhuo = $this->getRihuo($channel_id, $expend_id); //日活
            $drainage = $this->todayZ($channel_id, $expend_id); //日活
            $follow = $this->todayGz($channel_id, $expend_id); //日活
        }
        $data['ruhuo'] = $ruhuo; //日活
        $data['drainage'] = $drainage; //引流
        $data['follow'] = $follow; //新增关注
        $data['datetime'] = $this->getdatetime();
        $this->ajaxReturn($data);
    }

    /**
     * 获取天数数组
     * @return type
     */
    public function getdatetime($day = 19) {
        $dateY = [];
        for ($i = $day; $i >= 0; $i--) {
            $dateY[] = date('n-j', strtotime('-' . $i . ' day'));
        }
        return $dateY;
    }

    /**
     * 日活统计
     */
    public function getRihuo($channel_id, $expend_id) {
        $contArr = array();
        for ($i = 19; $i >= 0; $i--) {
            $day = date('Ymd', strtotime('-' . $i . ' day'));
            //渠道日活统计
            if (!empty($expend_id)) { //rihuo:20180515:expand:104
                $key = 'rihuo:' . $day . ':expand:' . $expend_id;
                $val = 'total';
                list($code, $result) = $this->redis->hget($key, $val);
            } else if (!empty($channel_id)) {
                $key = 'rihuo:' . $day . ':channel:' . $channel_id;
                $val = 'total';
                list($code, $result) = $this->redis->hget($key, $val);
            } else { //总日活统计 rihuo:20180515
                $bit = 'rihuo:' . $day;
                list($code, $result) = $this->redis->bitcount($bit);
            }
            if ($code == 200) {
                $contArr[$day] = $result;
            } else {
                $contArr[$day] = 0;
            }
        }
   
        $lineDate = array();
        foreach ($contArr as $value) {
            $lineDate[] = $value;
        }
        return $lineDate;
    }

    /**
     * 日增
     */
    public function todayZ($channel_id, $expend_id) {
        $contArr = array();
        for ($i = 19; $i >= 0; $i--) {
            $day = date('Ymd', strtotime('-' . $i . ' day'));
            if (!empty($expend_id)) { //rizeng:20180516:expand:128
                $key = 'rizeng:' . $day . ':expand:' . $expend_id;
            } else if ($channel_id != 0) { //rizeng:20180712:channel:10000
                $key = 'rizeng:' . $day . ':channel:' . $channel_id;
            } else {
                $key = 'rizeng:' . $day . ':total';
            }
            $val = 'total';
            list($code, $result) = $this->redis->hget($key, $val);
            if ($code == 200) {
                $contArr[$day] = $result;
            } else {
                $contArr[$day] = 0;
            }
        }
        $lineDate = array();
        foreach ($contArr as $value) {
            $lineDate[] = $value;
        }
        return $lineDate;
    }

    /**
     * 日增关注
     */
    public function todayGz($channel_id, $expend_id) {
        $contArr = array();
        for ($i = 19; $i >= 0; $i--) {
            $day = date('Ymd', strtotime('-' . $i . ' day'));
            if (!empty($expend_id)) { //rizenggz:20180516:expand:459
                $key = 'rizenggz:' . $day . ':expand:' . $expend_id;
            } else if ($channel_id != 0) { //rizenggz:20180712:channel:10000
                $key = 'rizenggz:' . $day . ':channel:' . $channel_id;
            } else {
                $key = 'rizenggz:' . $day . ':total';
            }
            $val = 'total';
            list($code, $result) = $this->redis->hget($key, $val);
            if ($code == 200) {
                $contArr[$day] = $result;
            } else {
                $contArr[$day] = 0;
            }
        }
        $lineDate = array();
        foreach ($contArr as $value) {
            $lineDate[] = $value;
        }
        return $lineDate;
    }

    /**
     * 近30天渠道金币消耗统计前十
     */
    public function getChannelGold() {
        if (!IS_AJAX) {
            $data['code'] = 200;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $contArr = array();
        $date = I('post.date');
        $dayte = empty($date) ? 0 : $date;
        if ($dayte == 1) {
            $day = date('Ymd', strtotime('-1 day'));
            $key = 'channel:' . $day . ":gold";
            $start = '0';
            $end = '10';
            list($code, $result) = $this->redis->zRevRange($key, $start, $end);
            if ($code == 200) {
                $contArr[$day] = $result;
            } else {
                $contArr[$day] = 0;
            }
        } else {
            for ($i = $dayte; $i >= 0; $i--) {
                $day = date('Ymd', strtotime('-' . $i . ' day'));
                $key = 'channel:' . $day . ":gold";
                $start = '0';
                $end = '10';
                list($code, $result) = $this->redis->zRevRange($key, $start, $end);
                if ($code == 200) {
                    $contArr[$day] = $result;
                } else {
                    $contArr[$day] = 0;
                }
            }
        }
        //将图解30天数据相加
        $newDataArr = [];
        foreach ($contArr as $valu) {
            foreach ($valu as $k => $j) {
                $newDataArr[$k] += $j;
            }
        }
        arsort($newDataArr); //倒序数组
        $gold = [];
        $channelName = [];
        $first = array_slice($newDataArr, 0, 10, true);
        foreach ($first as $keys => $value) {
            $gold[] = $value;
            $channelName[] = M('channel')->where('id=' . $keys)->getField('nick_name');
        }
        $data['code'] = 200;
        $data['line'] = $gold;
        $data['nick_name'] = $channelName;
        $this->ajaxReturn($data);
    }

    /**
     * 电影金币消耗
     */
    public function getMoviesGold() {
        if (!IS_AJAX) {
            $data['code'] = 200;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $contArr = array();
        $date = I('post.date');
        $dayte = empty($date) ? 0 : $date;
        if ($dayte == 1) {
            $day = date('Ymd', strtotime('-1 day'));
            $key = 'movies:' . $day . ":gold";
            $start = '0';
            $end = '29';
            list($code, $result) = $this->redis->zRevRange($key, $start, $end);
            if ($code == 200) {
                $contArr[$i] = $result;
            } else {
                $contArr[$i] = 0;
            }
        } else {
            for ($i = $dayte; $i >= 0; $i--) {
                $day = date('Ymd', strtotime('-' . $i . ' day'));
                $key = 'movies:' . $day . ":gold";
                $start = '0';
                $end = '29';
                list($code, $result) = $this->redis->zRevRange($key, $start, $end);
                if ($code == 200) {
                    $contArr[$i] = $result;
                } else {
                    $contArr[$i] = 0;
                }
            }
        }
        //将图解30天数据相加
        $newDataArr = [];
        foreach ($contArr as $valu) {
            foreach ($valu as $k => $j) {
                $newDataArr[$k] += $j;
            }
        }
        arsort($newDataArr); //倒序数组
        $gold = [];
        $moiveName = [];
        $first = array_slice($newDataArr, 0, 30, true); //取前30位
        foreach ($first as $keys => $value) {
            $gold[] = $value;
            $moive_name = M('movies')->where('id=' . $keys)->field('name,org_name')->find();
            $moiveName[] = !empty($moive_name['org_name']) ? $moive_name['name'] . ":" . $moive_name['org_name'] : $moive_name['name'];
        }
        $data['code'] = 200;
        $data['line'] = $gold;
        $data['name'] = $moiveName;
        $this->ajaxReturn($data);
    }

    /**
     * 用户金币消费榜单
     */
    public function getUserGold() {
        if (!IS_AJAX) {
            $data['code'] = 200;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $contArr = array();
        $date = I('post.date');
        $dayte = empty($date) ? 0 : $date;
        if ($dayte == 1) {
            $day = date('Ymd', strtotime('-1 day'));
            $key = 'users:' . $day . ":gold";
            $start = '0';
            $end = '10';
            list($code, $result) = $this->redis->zRevRange($key, $start, $end);
            if ($code == 200) {
                $contArr[$day] = $result;
            } else {
                $contArr[$day] = 0;
            }
        } else {
            for ($i = $dayte; $i >= 0; $i--) {
                $day = date('Ymd', strtotime('-' . $i . ' day'));
                $key = 'users:' . $day . ":gold";
                $start = '0';
                $end = '10';
                list($code, $result) = $this->redis->zRevRange($key, $start, $end);
                if ($code == 200) {
                    $contArr[$day] = $result;
                } else {
                    $contArr[$day] = 0;
                }
            }
        }
        //将用户30天金币消耗数据相加
        $newDataArr = [];
        foreach ($contArr as $valu) {
            foreach ($valu as $k => $j) {
                $newDataArr[$k] += $j;
            }
        }
        arsort($newDataArr); //倒序数组
        $first = array_slice($newDataArr, 0, 10, true);
        $userInfo = array();
        $j = 0;
        foreach ($first as $key => $value) {
            $userInfo[$j]['id'] = $key;
            $userInfo[$j]['gold'] = $value;
            $j++;
        }
        $this->ajaxReturn($userInfo);
    }

    /**
     * 24小时活跃度统计
     */
    public function getHous() {
        if (!IS_AJAX) {
            $data['code'] = 200;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $onDay = !empty(I('post.day')) ? str_replace("-", "", I('post.day')) : date("Ymd");
        $contArr = array();
        $hous = [];
        $Inhous = $onDay == date("Ymd") ? date('G') : 23;
        for ($i = 0; $i <= $Inhous; $i++) { //rihuo:20180516:total
            $valrihuo = 'rihuo:' . $onDay . ":total";
            $valrz = 'rizeng:' . $onDay . ":total"; //rizeng:20180516:total
            $valgz = 'rizenggz:' . $onDay . ":total"; //rizenggz:20180516:total
            if ($i < 10) {
                $key = $hous[] = str_pad($i, 2, "0", STR_PAD_LEFT);
            } else {
                $key = $hous[] = $i;
            }
            list($coderihuo, $resultrihuo) = $this->redis->hGet($valrihuo, $key); //日活
            list($coderz, $resultrz) = $this->redis->hGet($valrz, $key); //日增
            list($codegz, $resultgz) = $this->redis->hGet($valgz, $key); //关注
            if ($coderihuo == 200) {
                $contArr[$i]['rihuo'] = $resultrihuo;
            } else {
                $contArr[$i]['rihuo'] = 0;
            }
            if ($coderz == 200) {
                $contArr[$i]['drainage'] = $resultrz;
            } else {
                $contArr[$i]['drainage'] = 0;
            }
            if ($codegz == 200) {
                $contArr[$i]['follow'] = $resultgz;
            } else {
                $contArr[$i]['follow'] = 0;
            }
        }
        $lineDate = [];
        foreach ($contArr as $key => $value) {
            $lineDate['rihuo'][$key] = $value['rihuo'];
            $lineDate['drainage'][$key] = $value['drainage'];
            $lineDate['follow'][$key] = $value['follow'];
        }
        $data['code'] = 200;
        $data['rihuo'] = $lineDate['rihuo'];
        $data['drainage'] = $lineDate['drainage'];
        $data['follow'] = $lineDate['follow'];
        $data['hous'] = $hous;
        $this->ajaxReturn($data);
    }

    /**
     * 充值分析页面
     */
    public function rechargeAnalysis() {
        //所有渠道
        $channellist = M('channel')->field('id,nick_name,member_id')->select();
        $vipCont = M('member')->where('status=1')->field('uid,user,pid')->select();
        $user_id = session('user_id');
        $in_Channel = M('admin_channel')->where('user_id =' . $user_id)->field('channel_id,member_id')->find();
        $oml_channel = '';
    
        if($in_Channel['member_id'] != ''){
            $explodeVipId = explode(',',$in_Channel['member_id']);
            $omlVip['member_id'] = array('in',$explodeVipId);
            $selectVipChannel = M('channel')->where($omlVip)->field('id')->select();
            $indexChannel = '';
            if($in_Channel['channel_id'] != ''){
                $explodeInChannel = explode(',', $in_Channel['channel_id']);
                foreach($selectVipChannel as $valueChannel){
                    if(!in_array($valueChannel['id'], $explodeInChannel)){
                        $indexChannel .= $valueChannel['id'].',';
                    }
                }
                $indexChannel = trim($indexChannel,',');
            }else{
                foreach($selectVipChannel as $valueChannel){
                    $indexChannel .= $valueChannel['id'].',';
                }
                $indexChannel = trim($indexChannel,',');
            }
            $oml_channel = $indexChannel.','.$in_Channel['channel_id'];
        }else{
            $oml_channel = $in_Channel['channel_id'];
        }
        
        if ($in_Channel) {
            $this->assign('inAdminChannel', $oml_channel);
        }
        $newChanel = array();
        $channellistarr = array();
        foreach ($vipCont as $key => $value) {
            foreach ($channellist as $k => $vl) {
                if ($value['uid'] == $vl['member_id']) {
                    $newChanel[$key]['uid'] = $value['uid'];
                    $newChanel[$key]['pid'] = $value['pid'];
                    $newChanel[$key]['username'] = $value['user'];
                    $newChanel[$key]['channel'][] = $vl;
                    $newChanel[$key]['over'] = !empty($newChanel[$key]['over']) ? $newChanel[$key]['over'] . "," . $vl['id'] : $vl['id'];
                }
            }
        }
        foreach ($newChanel as $k => &$valVip) {
            foreach ($newChanel as $valss) {
                if ($valVip['uid'] == 6 && $valss['pid'] == '6') {
                    $valVip['channel'] = array_merge($valVip['channel'], $valss['channel']);
                    $valVip['over'] = $newChanel[$k]['over'] . "," . $valss['over'];
                }
            }
        }
        foreach ($channellist as $val) {
            if ($val['member_id'] == 0) {
                $channellistarr[] = $val;
            }
        }
        $this->assign('channellistNew', $channellistarr);
        $this->assign('Viplist', $newChanel);
        $this->assign('day', date('Y-m-d', strtotime('-1 day')));
        $this->display();
    }

    /**
     * vip充值分析，散户充值比例高单独显示，否则列入其它列
     */
    public function getrechargeAnalysis() {
        if (!IS_AJAX) {
            $res = array(
                'code' => 0,
                'res' => '非法访问'
            );
            $this->ajaxReturn($res);
        }
        $day = I('post.dataTime');
        $channel_id = I('post.channel_id');
        $member_id = I('post.member_id');
        $oneDay = $day ? $day : date('Y-m-d', strtotime('-1 day'));

        //不能统计今日和之后的数据
        if (strtotime($day) >= strtotime(date('Y-m-d'))) {
            $res = array(
                'code' => 0,
                'res' => '只能统计今日之前的数据'
            );
            $this->ajaxReturn($res);
        }
        if ($member_id == 'index') {
            $channel_id_arr = array();
            $payArrMember = [];
            $total = 0;

            $where['b.date'] = $oneDay;
            $channel_id = I('post.channel_id');
            $channnel_arr = explode(',', $channel_id);
            $member_arr = M('member as m')->join('left join yy_channel as c on m.uid = c.member_id')->field('m.uid,m.user')->where(array('c.id' => array('in', $channnel_arr)))
                    ->group('m.uid')
                    ->select();


            $where['b.channel_id'] = array('in', $channnel_arr);
            //查询渠道
            $where['member_id'] = array('gt', 0);
            $pay_data = M('pay_data as b')->
                            join('left join yy_channel on yy_channel.id = b.channel_id')->
                            where($where)->field('b.pay as pay,b.channel_id,yy_channel.member_id')->select();
            $where['member_id'] = array('elt', 0);
            $pay_data_dl = M('pay_data as b')->
                            join('left join yy_channel on yy_channel.id = b.channel_id')->
                            where($where)->field('b.pay as pay,b.channel_id,yy_channel.nick_name')->select();
            $newArr = array(); //创建新数组，存放结果
            //将vip充值放入数组中
            foreach ($member_arr as $vipmember) {
                $vipChongz = 0; //将属于vip下的渠道聚合
                foreach ($pay_data as $k => $channel_value) {
                    if ($channel_value['member_id'] == $vipmember['uid']) {
                        $vipChongz += floatval($channel_value['pay']) / 100;
                    }
                }
                if($vipChongz == 0){
                    continue;
                }
                $vipTime['pay'] = $vipChongz;
                $vipTime['vipName'] = $vipmember['user'];
                $vipTime['type'] = 4; // 1：vip 2:渠道 3:其他 4 本号下vip
                $vipTime['id'] = $vipmember['uid']; //渠vip id
                $vipTime['day'] = $oneDay; //当前天数
                $newArr[] = $vipTime;
            }
            //将单个渠道放入数组
            foreach ($pay_data_dl as &$val) {
                if($val['pay'] == 0){
                    continue;
                }
                $val['pay'] = floatval($val['pay']) / 100;
                $val['vipName'] = $val['nick_name'];
                $val['type'] = 2; // 1：vip 2:渠道 3:其他
                $val['id'] = $val['channel_id']; //渠道 id
                $val['day'] = $oneDay; //当前天数
                $newArr[] = $val;
                $total += floatval($val['pay']) / 100;
            }

            $indes_arr = $newArr;
        } elseif (empty($channel_id)) {
            $vip = M('member as a')->where(array('a.status' => 1, 'a.pid' => 0))->field('a.uid,a.user,a.uid')->select();
            foreach ($vip as &$membervalue) {
                $menberArr = M('member')->where('pid = ' . $membervalue['uid'])->field('uid')->select();
                foreach ($menberArr as $memuid) {
                    $membervalue['member_arr'][] = $memuid['uid'];
                }
            }
            $where['b.date'] = $oneDay;
            $sqlPid = M('channel as a')->where('a.id = b.channel_id ')->field('a.member_id')->select(false);
            $data = M('pay_data as b')->where($where)->field('b.pay as show_value,b.channel_id,(' . $sqlPid . ') as pid')->select();
            $payArrMember = [];
            $total = 0;
            foreach ($vip as $vipK => $channelArr) {
                $pay = '';
                foreach ($data as $payData) {
                    if ($payData['pid'] == $channelArr['uid'] || in_array($payData['pid'], $channelArr['member_arr'])) {
                        $pay += $payData['show_value'] / 100;
                        $payArrMember[$vipK]['vipName'] = $channelArr['user'];
                        $payArrMember[$vipK]['pay'] = $pay;
                        $payArrMember[$vipK]['type'] = 1; // 1：vip 2:渠道 3:其他
                        $payArrMember[$vipK]['id'] = $channelArr['uid']; //vip id
                        $payArrMember[$vipK]['day'] = $oneDay; //当前天数
                    }
                }
                $total += $pay;
            }
            $channelName = M('channel as a')->where('a.id = b.channel_id')->field('nick_name')->select(false);
            //查询充值大于等于一千的非vip渠道
            $where['b.pay'] = array('EGT', 100000);
            $pay_data = M('pay_data as b')->
                            join('yy_channel on yy_channel.id = b.channel_id and yy_channel.member_id = 0')->
                            where($where)->field('b.pay as pay,b.channel_id,(' . $channelName . ') as vipName')->select();
            //查询充值小于一千的非vip渠道集合
            $where['b.pay'] = array('LT', 100000);
            $channel_sum = M('pay_data as b')->
                            join('yy_channel on yy_channel.id = b.channel_id and yy_channel.member_id = 0')->
                            where($where)->sum('b.pay');

            $newArr = array(); //创建新数组，存放结果
            //将vip充值放入数组中
            foreach ($payArrMember as &$value) {
                $newArr[] = $value;
            }
            //将大于一千的渠道放入数组
            foreach ($pay_data as &$val) {
                $val['pay'] = floatval($val['pay']) / 100;
                $val['vipName'] = $val['vipname'];
                $val['type'] = 2; // 1：vip 2:渠道 3:其他
                $val['id'] = $val['channel_id']; //渠道 id
                $val['day'] = $oneDay; //当前天数
                $newArr[] = $val;
                $total += floatval($val['pay']) / 100;
            }
            //将小于一千的渠道所有渠道放在其他下标中
            if ($channel_sum) {
                $Other = array(
                    'pay' => $channel_sum / 100,
                    'type' => 3, // 1：vip 2:渠道 3:其他
                    'id' => 0, //容错值，本身没有意义
                    'day' => $oneDay, //当前天数
                    'vipName' => '其他'
                );
                array_push($newArr, $Other);
                $total += $channel_sum / 100;
            }
            $indes_arr = $newArr;
        } else {
            $channel_id_arr = array();
            $payArrMember = [];
            $total = 0;
            if (!empty($member_id)) {
                $vip = M('member as a')->where(array('a.pid' => $member_id))->field('a.uid,a.user,a.uid')->select();

                $channel_arr = array();
                foreach ($vip as $vipK => $channelArr) {
                    $channel_arr[] = M('channel')->where('member_id =' . $channelArr['uid'])->field('id')->select();
                    $pay = '';
                    $where['b.date'] = $day;
                    $dataMember = M('pay_data as b')
                                    ->join('yy_channel on yy_channel.member_id in(' . $channelArr['uid'] . ') and yy_channel.id = b.channel_id ')
                                    ->where($where)->field('b.pay as show_value,b.channel_id,yy_channel.member_id as pid')->select();
                    foreach ($dataMember as $payData) {
                        $pay += $payData['show_value'] / 100;
                    }
                    $payArrMember[$vipK]['vipName'] = $channelArr['user'];
                    $payArrMember[$vipK]['pay'] = $pay;
                    $payArrMember[$vipK]['type'] = 1; // 1：vip 2:渠道 3:其他
                    $payArrMember[$vipK]['id'] = $channelArr['uid']; //vip id
                    $payArrMember[$vipK]['day'] = $day; //当前天数
                    $total += $pay;
                }
                foreach ($channel_arr as $chekey => $cha_id) {
                    foreach ($cha_id as $chilakey => $chilavalue) {
                        if ($chilavalue['id'] != '') {
                            $channel_id_arr[] = $chilavalue['id'];
                        }
                    }
                }
            }
            $payArr = [];
            $where['b.date'] = $oneDay;
            $channel_id = I('post.channel_id');
            $channnel_arr = explode(',', $channel_id);
            foreach ($channnel_arr as $k => $chann_in_id) {
                if (in_array($chann_in_id, $channel_id_arr)) {
                    unset($channnel_arr[$k]);
                }
            }

            $where['b.channel_id'] = array('in', $channnel_arr);

            $channelName = M('channel as a')->where('a.id = b.channel_id')->field('nick_name')->select(false);
            //查询充值大于等于一千的非vip渠道
            $where['b.pay'] = array('EGT', 10000);
            $pay_data = M('pay_data as b')->
                            join('left join yy_channel on yy_channel.id = b.channel_id')->
                            where($where)->field('b.pay as pay,b.channel_id,(' . $channelName . ') as vipName')->select();
            //查询充值小于一千的非vip渠道集合
            $where['b.pay'] = array('LT', 10000);
            $channel_sum = M('pay_data as b')->
                            join('yy_channel on yy_channel.id = b.channel_id')->
                            where($where)->sum('b.pay');

            $newArr = array(); //创建新数组，存放结果
            //将vip充值放入数组中
            foreach ($payArr as &$value) {
                $newArr[] = $value;
            }
            //将大于一千的渠道放入数组
            foreach ($pay_data as &$val) {
                $val['pay'] = floatval($val['pay']) / 100;
                $val['vipName'] = $val['vipname'];
                $val['type'] = 2; // 1：vip 2:渠道 3:其他
                $val['id'] = $val['channel_id']; //渠道 id
                $val['day'] = $oneDay; //当前天数
                $newArr[] = $val;
                $total += floatval($val['pay']) / 100;
            }
            //将小于一千的渠道所有渠道放在其他下标中
            if ($channel_sum) {
                $Other = array(
                    'pay' => $channel_sum / 100,
                    'type' => 2, // 1：vip 2:渠道 3:其他
                    'id' => 0, //容错值，本身没有意义
                    'day' => $oneDay, //当前天数
                    'vipName' => '其他'
                );
                array_push($newArr, $Other);
                $total += $channel_sum / 100;
            }
            $indes_arr = array_merge($payArrMember, $newArr);
        }

        $res = array(
            'code' => 200,
            'res' => $indes_arr,
            'total' => $total
        );
        if (empty($indes_arr)) {
            $res = array(
                'code' => 400,
                'res' => '无数据'
            );
            $this->ajaxReturn($res);
        }
        $this->ajaxReturn($res);
    }

    /**
     * 获取VIP下渠道充值详情
     */
    public function getSubordinateData() {
        $type = I('get.type');
        $id = I('get.id');
        $day = I('get.day');
        if ($type == 1 && !empty($id)) {  //vip
            $vip = M('member as a')->where(array('a.pid' => $id))->field('a.uid,a.user,a.uid')->select();
            $payArr = [];
            $total = 0;
            foreach ($vip as $vipK => $channelArr) {
                $pay = '';
                $where['b.date'] = $day;
                $dataMember = M('pay_data as b')
                                ->join('yy_channel on yy_channel.member_id in(' . $channelArr['uid'] . ') and yy_channel.id = b.channel_id ')
                                ->where($where)->field('b.pay as show_value,b.channel_id,yy_channel.member_id as pid')->select();

                foreach ($dataMember as $payData) {
                    $pay += $payData['show_value'] / 100;
                }
                $payArr[$vipK]['nick_name'] = $channelArr['user'];
                $payArr[$vipK]['show_value'] = $pay;
                $payArr[$vipK]['type'] = 1; // 1：vip 2:渠道 3:其他
                $payArr[$vipK]['id'] = $channelArr['uid']; //vip id
                $payArr[$vipK]['day'] = $day; //当前天数
                $total += $pay;
            }

            $where = array(
                'b.date' => $day,
                'b.pay' => array('EGT', 10000)
            );
            $sqlNickName = M('channel as a')->where('a.id = b.channel_id ')->field('a.nick_name')->select(false);
            $data = M('pay_data as b')->
                            join('yy_channel on yy_channel.member_id in(' . $id . ') and yy_channel.id = b.channel_id ')->
                            where($where)->field('b.pay as show_value,b.channel_id,(' . $sqlNickName . ') as nick_name')->select();
            foreach ($data as &$value) {
                $value['show_value'] = $value['show_value'] / 100;
                $value['type'] = 2;
                $value['id'] = 0;
                $value['day'] = $day;
            }
            //其他
            $where['b.pay'] = array('LT', 10000);
            $ortherdata = M('pay_data as b')->
                            join('yy_channel on yy_channel.member_id in(' . $id . ') and yy_channel.id = b.channel_id ')->
                            where($where)->sum('pay');
            if ($ortherdata) {
                $orther = array(
                    'nick_name' => '其他',
                    'type' => 2,
                    'id' => 0,
                    'day' => $day,
                    'show_value' => $ortherdata / 100
                );
                array_push($data, $orther);
            }
            $showData = array_merge($payArr, $data);
            $json = json_encode($showData);
            $this->assign('data', $json);
        } else if ($type == 3) { //其他
            $where = array(
                'b.date' => $day,
                'b.pay' => array(array('LT', 100000), array('EGT', 10000))
            );
            $sqlNickName = M('channel as a')->where('a.id = b.channel_id ')->field('a.nick_name')->select(false);
            $data = M('pay_data as b')->
                            join('yy_channel on yy_channel.id = b.channel_id and yy_channel.member_id = 0')->
                            where($where)->field('b.pay as show_value,b.channel_id,(' . $sqlNickName . ') as nick_name')->select();
            foreach ($data as &$value) {
                $value['show_value'] = $value['show_value'] / 100;
                $value['type'] = 2;
                $value['id'] = 0;
                $value['day'] = $day;
            }
            //其他
            $where['b.pay'] = array('LT', 10000);
            $ortherdata = M('pay_data as b')->
                            join('yy_channel on yy_channel.id = b.channel_id and yy_channel.member_id = 0')->
                            where($where)->sum('pay');
            if ($ortherdata) {
                $orther = array(
                    'nick_name' => '其他',
                    'type' => 2,
                    'id' => 0,
                    'day' => $day,
                    'show_value' => $ortherdata / 100
                );
                array_push($data, $orther);
            }
            $json = json_encode($data);
            $this->assign('data', $json);
        } else {
            
            $user_id = session('user_id');
            $in_Channel = M('admin_channel')->where('user_id =' . $user_id)->field('channel_id,member_id')->find();
            $oml_channel = '';

            if($in_Channel['member_id'] != ''){
                $explodeVipId = explode(',',$in_Channel['member_id']);
                $omlVip['member_id'] = array('in',$explodeVipId);
                $selectVipChannel = M('channel')->where($omlVip)->field('id')->select();
                $indexChannel = '';
                if($in_Channel['channel_id'] != ''){
                    $explodeInChannel = explode(',', $in_Channel['channel_id']);
                    foreach($selectVipChannel as $valueChannel){
                        if(!in_array($valueChannel['id'], $explodeInChannel)){
                            $indexChannel .= $valueChannel['id'].',';
                        }
                    }
                    $indexChannel = trim($indexChannel,',');
                }else{
                    foreach($selectVipChannel as $valueChannel){
                        $indexChannel .= $valueChannel['id'].',';
                    }
                    $indexChannel = trim($indexChannel,',');
                }
                $oml_channel = $indexChannel.','.$in_Channel['channel_id'];
            }else{
                $oml_channel = $in_Channel['channel_id'];
            }
            
//            $in_Channel = M('admin_channel')->where('user_id =' . $user_id)->getField('channel_id');
            $channel_explod = explode(',', $oml_channel);
            $where = array(
                'b.date' => $day,
                'b.channel_id' => array('in',$channel_explod)
            );
            $sqlNickName = M('channel as a')->where('a.id = b.channel_id ')->field('a.nick_name')->select(false);
            $data = M('pay_data as b')->
                            join('yy_channel on yy_channel.id = b.channel_id and yy_channel.member_id ='.$id)->
                            where($where)->field('b.pay as show_value,b.channel_id,(' . $sqlNickName . ') as nick_name')->select();
            foreach ($data as &$value) {
                $value['show_value'] = $value['show_value'] / 100;
                $value['type'] = 2;
                $value['id'] = 0;
                $value['day'] = $day;
            }
           
            $json = json_encode($data);
            $this->assign('data', $json);
        }
        $this->display();
    }

    /**
     * 近三十天充值走势图
     */
    public function trendOfRecharge() {
        if (!IS_AJAX) {
            $res = array(
                'code' => 0,
                'res' => '非法访问'
            );
            $this->ajaxReturn($res);
        }
        $data = array();
        $date = array();
        $channel_id = I('post.channelId');
        if ($channel_id) {
            $where['channel_id'] = array('in', $channel_id);
        }
       
        $startDay = I('post.startTime');
        $endDay = I('post.endTime');
        session('trendOfRechargeDownChannelId',$channel_id);
        if (!empty($endDay) || !empty($startDay)) {
            session('trendOfRechargeEndDay',$endDay);
            session('trendOfRechargeStartDay',$startDay);
            $timeStr = strtotime($startDay);
            $timeStrend = strtotime($endDay);
            $totol = 0;
            for ($time = $timeStr; $time <= $timeStrend; $time += 86400) {
                $where['date'] = date('Y-m-d', $time);
                $date[] = date('Y/n/j', $time);
                $showvalue = M('pay_data')->where($where)->sum('pay');
                $data[] = $showvalue;
                $totol += $showvalue;
            }
            foreach ($data as &$value) {
                if ($value) {
                    $value = $value / 100;
                }
            }
        } else {
            session('trendOfRechargeEndDay',date('Y-m-d', strtotime('-1 day')));
            session('trendOfRechargeStartDay',date('Y-m-d', strtotime('-30 day')));
            $totol = 0;
            for ($i = 30; $i > 0; $i--) {
                $day = date('Y-m-d', strtotime('-' . $i . ' day'));
                $where['date'] = $day;
                $date[] = date('Y/n/j', strtotime('-' . $i . ' day'));
                $showvalue = M('pay_data')->where($where)->sum('pay');
                $data[] = $showvalue;
                $totol += $showvalue;
            }

            foreach ($data as &$value) {
                if ($value) {
                    $value = $value / 100;
                }
            }
        }
        $returnArr['code'] = 200;
        $returnArr['res'] = array('day' => $date,'data' => $data, 'total' => $totol / 100);
        $this->ajaxReturn($returnArr);
    }

    public function downRecharge() {
        set_time_limit(0);
   
        $channel_id = session('trendOfRechargeDownChannelId');
        $day = I('get.day');
        $startDay = session('trendOfRechargeStartDay');
        $endDay = session('trendOfRechargeEndDay');
        if($channel_id != 0){
            $whereIs['c.id'] = array('in', $channel_id);
            $is = M('member as a')->where($whereIs)->join('left join yy_channel as c on c.member_id = a.uid')->field('a.uid')->group('c.member_id')->select();
            if(count($is) <= 1){
                if($day != 'All'){
                    //查询vip充值
                    $where['a.date'] = date('Y-m-d', strtotime($day));
                    $where['a.channel_id'] = array('in', $channel_id);
                    //渠道
                    $channel_value = M('pay_data as a')->where($where)
                            ->join('left join yy_channel as c on c.id = a.channel_id')
                            ->field('a.channel_id,a.date,a.pay,c.nick_name')->select();
                    $sumArr = $channel_value;
                    $downData = $sumArr;
                    $this->downExcelMoenyc($downData,$day);
                }else{
                    $where['a.date']= array(
                        array('egt',date('Y-m-d', strtotime($startDay))),
                        array('elt',date('Y-m-d', strtotime($endDay)))
                    );
                    
                    $where['a.channel_id'] = array('in', $channel_id);
                    $channel_value = M('pay_data as a')->where($where)
                       ->join('left join yy_channel as c on c.id = a.channel_id')
                       ->field('a.channel_id,sum(a.pay) as pay,c.nick_name')
                       ->group('a.channel_id')
                       ->select();
                }
                $this->downExcelMoenyc($channel_value,$startDay,$endDay);
                return false;
            }
            if($day != 'All'){
                //查询vip充值
                $where['a.date'] = date('Y-m-d', strtotime($day));
                $where['a.channel_id'] = array('in', $channel_id);
                $where['c.member_id'] = array('neq', '');
                $showvalue = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->join('LEFT JOIN yy_member AS m ON c.member_id = m.uid')
                        ->field('a.date,sum(a.pay) as pay,m.account as nick_name,m.uid as channel_id')
                        ->group('c.member_id')
                        ->select();
                //渠道
                $where['c.member_id'] = array('eq', '');
                $channel_value = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->field('a.channel_id,a.date,a.pay,c.nick_name')->select();
                $showvalue[] = array('channel_id'=>'','pay'=>'','nick_name'=>'');
                $sumArr = array_merge($showvalue,$channel_value);
                $downData = $sumArr;
                $this->downExcelMoenyc($downData,$day);
            }else{
                $where['a.date']= array(
                    array('egt',date('Y-m-d', strtotime($startDay))),
                    array('elt',date('Y-m-d', strtotime($endDay)))
                );
                $where['a.channel_id'] = array('in', $channel_id);
                $where['c.member_id'] = array('neq', '');
                $showvalue = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->join('LEFT JOIN yy_member AS m ON c.member_id = m.uid')
                        ->field('sum(a.pay) as pay,m.account as nick_name,m.uid as channel_id')
                        ->group('c.member_id')
                        ->select();
                //渠道
                $where['c.member_id'] = array('eq', '');
                $channel_value = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->field('a.channel_id,sum(a.pay) as pay,c.nick_name')
                        ->group('c.id')
                        ->select();
                $showvalue[] = array('channel_id'=>'','pay'=>'','nick_name'=>'');
                $sumArr = array_merge($showvalue,$channel_value);
                $downData = $sumArr;
                $this->downExcelMoenyc($downData,$startDay,$endDay);
              
            }
        }else{
            if($day != 'All'){
                $where['a.date'] = date('Y-m-d', strtotime($day));
                $where['c.member_id'] = array('neq', '');
                $showvalue = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->join('LEFT JOIN yy_member AS m ON c.member_id = m.uid')
                        ->field('a.date,sum(a.pay) as pay,m.account as nick_name,m.uid as channel_id')
                        ->group('c.member_id')
                        ->select();
                //渠道
                $where['c.member_id'] = array('eq', '');
                $channel_value = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->field('a.channel_id,a.date,a.pay,c.nick_name')->select();
                $showvalue[] = array('channel_id'=>'','pay'=>'','nick_name'=>'');
                $sumArr = array_merge($showvalue,$channel_value);
                $downData = $sumArr;
               
                $this->downExcelMoenyc($downData,$day);
            }else{
                $where['a.date']= array(
                    array('egt',date('Y-m-d', strtotime($startDay))),
                    array('elt',date('Y-m-d', strtotime($endDay)))
                );
                $where['c.member_id'] = array('neq', '');
                $showvalue = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->join('LEFT JOIN yy_member AS m ON c.member_id = m.uid')
                        ->field('sum(a.pay) as pay,m.account as nick_name,m.uid as channel_id')
                        ->group('c.member_id')
                        ->select();
                //渠道
                $where['c.member_id'] = array('eq', '');
                $channel_value = M('pay_data as a')->where($where)
                        ->join('left join yy_channel as c on c.id = a.channel_id')
                        ->field('a.channel_id,sum(a.pay) as pay,c.nick_name')->group('c.id')->select();
                $showvalue[] = array('channel_id'=>'','pay'=>'','nick_name'=>'');
                $sumArr = array_merge($showvalue,$channel_value);
                $downData = $sumArr;
                $this->downExcelMoenyc($downData,$startDay,$endDay);
            }
        }
    }
    
    public function downExcelMoenyc($dataDown,$day,$endDay='') {
        
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getProperties()->setTitle("推广统计");
        $objExcel->getProperties()->setSubject("报表");
      
        $objExcel->setActiveSheetIndex(0);
        //title
//        $objExcel->getActiveSheet()->setCellValue('A1', '日期'); 
        $objExcel->getActiveSheet()->setCellValue('A1', '渠道/vipID'); 
        $objExcel->getActiveSheet()->setCellValue('B1', '渠道/vip名称'); 
        $objExcel->getActiveSheet()->setCellValue('C1', '充值'); 
        $objExcel->getActiveSheet()->setCellValue('D1', '合计'); 
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);

        $i = 2;
        $countPayover = 0;
        foreach($dataDown as $value){
            if($value['channel_id'] == ''){
                $objExcel->getActiveSheet()->mergeCells('A'.$i.':'.'C'.$i);
            }else{
                $objExcel->getActiveSheet()->setCellValue('A' . $i, $value['channel_id']); //渠道id
                $objExcel->getActiveSheet()->setCellValue('B' . $i, $value['nick_name']); //渠道名称
                $objExcel->getActiveSheet()->setCellValue('C' . $i, round($value['pay']/100,2)); //充值数据
                $countPayover += $value['pay']/100;
            }
            $i++;
            
          
        }
        $objExcel->getActiveSheet()->setCellValue('D2', $countPayover); //渠道id
        $objExcel->getActiveSheet()->mergeCells('D2:D' . ($i));
        $objExcel->getActiveSheet()->mergeCells('A'.$i.':'.'C'.$i);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '合计'); //渠道id
        
       
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $objExcel->getActiveSheet()->getStyle( 'A1:D'.$i)->applyFromArray($styleThinBlackBorderOutline);
        $filename = '';
        if($endDay != ''){
            $filename = date('Y-m-d', strtotime($day)).'至'.date('Y-m-d', strtotime($endDay));
        }else{
            $filename = date('Y-m-d', strtotime($day));
        }
        $savefile = $filename."渠道充值详细数据单" . time() . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefile . '"');
        header('Cache-Control: max-age=0');
        // 用户下载excel
        $objWriter->save('php://output');
    }
    
    
    /**
     * 进三十天金币消费
     */
    public function goldThirtyDay() {
        if (!IS_AJAX) {
            $res = array(
                'code' => 0,
                'res' => '非法访问'
            );
            $this->ajaxReturn($res);
        }
        $Id = I("post.Id");
        $type = I("post.type");
        $day = array();
        $line = array();
        for ($i = 29; $i >= 0; $i--) {
            if (!empty($type) && !empty($Id)) {
                $dayTime = date('Ymd', strtotime('-' . $i . 'day'));
                $key = ($type == 1) ? 'channel:' . $dayTime . ':gold' : 'movies:' . $dayTime . ':gold';
                list($code, $res) = $this->redis->zScore($key, $Id);
            } else {
                $key = 'movies:' . date('Ymd', strtotime('-' . $i . 'day')) . ':total-gold';
                list($code, $res) = $this->redis->stringGet($key);
            }
            $day[] = date('n-j', strtotime('-' . $i . 'day'));
            if ($code == 200) {
                $line[] = $res;
            }
        }
        $result['code'] = 200;
        $result['day'] = $day;
        $result['line'] = $line;
        $this->ajaxReturn($result);
    }

}

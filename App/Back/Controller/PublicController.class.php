<?php

namespace Back\Controller;

use Think\Controller;
use Common\Lib\Log;
use Back\Model\woZanPay;
use Exception;

class PublicController extends Controller {

    public function index() {
        redirect('Public/login');
    }

    //自动登录
    public function autoLogin($login) {
        import('Common.Lib.JoDES');
        $des = new \Des\JoDES();
        $login = json_decode($des->decode($login, C('APPC_KEY.LOGIN')), true);
        $data = $login;
        $account = $data['account'];
        $password = $data['password'];
        if(empty(cookie('admin_last_login_time'))){
            cookie('loginUser',null);
            return ;
        }
        if (empty($account) || empty($password)) {
            cookie('loginUser', null);
            return;
        }
        $map = array('account' => $account);
        $one = D('admin')->where($map)->find();
        $Admin = D('Admin');
        if (empty($one)) {
            cookie('loginUser', null);
            return;
        }
        if ($one['password'] !== $Admin->getPwd($password)) {
            cookie('loginUser', null);
            return;
        }
//        import('Common.Lib.JoDES');
//        $des = new \Des\JoDES();
//        $login = $des->encode(json_encode(array('account' => $account, 'password' => $password)), C('APPC_KEY.LOGIN'));
//        cookie('loginUser', $login, time() + 7 * 24 * 3600);
        $saveUser['last_login_time'] = time();
        $saveUser['last_login_ip'] = get_client_ip();
        M('admin')->where('id ='.$one['id'])->save($saveUser);
        session('user_id', $one['id']);
        session('user_info', $one);
        $url = session('_pre_url_');
        if (!empty($url)) {
            $this->redirect($url);
        } else {
            $this->redirect('Index/index');
        }
    }

    public function login() {
        $user_id = session('user_id');
        if (!empty($user_id)) {
            $this->redirect('Index/index');
            exit();
        }
        $login = cookie('loginUser');
        if (!empty($login)) {
            $this->autoLogin($login);
        }
        if (!empty($_POST['verify'])) {
            if (!$this->check_verify($_POST['verify'], 'login')) {
                $this->error('验证码错误！', U('Public/login'));
            }
        }
        if (!empty($_POST)) {
           
            $account = I('account');
            $password = I('password');
            if (empty($account) || empty($password)) {
                $this->error('参数错误', U('Public/login'));
            }
            $Admin = D('Admin');
            $map['a.account'] = $account;
            $map['c.status'] = 1;
            $map['a.status'] = 1;
            $one = M('admin as a ')
                    ->join('left join yy_auth_group_access as b on a.id=b.uid ')
                    ->join('left join yy_auth_group as c on c.id=b.group_id')
                    ->where($map)
                    ->field('a.id,a.password,a.username,a.session_time')
                    ->find();
            if (empty($one)) {
                $this->error('用户不存在', U('Public/login'));
            }
            if ($one['password'] !== $Admin->getPwd($password)) {
                $this->error('密码错误', U('Public/login'));
            }
            if (!empty(I('post.auto'))) {
                import('Common.Lib.JoDES');
                $des = new \Des\JoDES();
                $login = $des->encode(json_encode(array('account' => $account, 'password' => $password)), C('APPC_KEY.LOGIN'));
                cookie('loginUser', $login, 'expire=604800');
            }
            cookie('admin_last_login_time', time(),'expire=604800');
            //记录用户最后登陆的时间和ip
            $data = array(
                'last_login_time' => time(),
                'last_login_ip' => get_client_ip()
            );
            if($one['session_time'] == 0){
                $data['session_time'] = time();
            }
            $id = $one['id'];
            //将当前用户登录时间和ip存入数据库
            M('Admin')->where('id=' . $id)->save($data);
            session('last_login_time', date('Y-m-d H:i:s', $one['last_login_time']));
            session('last_login_ip', $one['last_login_ip']);
            session('user_info', $one, time() + 3600 * 24 * 5);
            session('user_id', $one['id'], time() + 3600 * 24 * 5);
            unset($one['password']);
            $url = session('_pre_url_');
            if (!empty($url)) {
                $this->redirect($url);
            } else {
                $this->redirect('Index/index');
            }
        } else {
            $this->display();
        }
    }

    public function loginOut() {
        session('user_id', null);
        cookie('loginUser', null);
        session_unset();
        $this->redirect('Public/login');
    }

    public function verify() {
        $pwd = I('post.pwd');
        $wozan = C('wover');
        if ($pwd == $wozan) {
            $this->ajaxReturn(array('status' => true));
        }
        $this->ajaxReturn(array('status' => false));
    }

    public function weiwithdrawal() {
        $key = I('get.key');
        $rskey = md5('wozan888');
        if (!$key == $rskey) {
            return;
        }
        $log = new Log(array('log_file_path' => './log/Money/'));
        //昨日时间1
        $Yesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        $channel = M('channel')->field('id')->select();
        // 2018年11月6日15:25:59 修改成 pay_time
        $where['t.pay_time'] = array('BETWEEN', array($Yesterday, $endYesterday)); //时间条件
        // $where['t.add_time'] = array('gt',1541433599); // 过渡期的时间判断 11-05 23:59:59
        // 2018年11月8日16:55:57 增加支付渠道判断 小于100 才会被统计进结算表，其他情况走分账体系。
        $where['t.pay_channel'] = array('lt', 100);
//        $where['t.add_time']=array('BETWEEN',array($Yesterday,$endYesterday));//时间条件
        $where['t.pay_status'] = 1; //状态值
        $where2['sum_pay'] = 0;
        $data2['status'] = 2;

        // 未提现订单汇总 2018年11月6日16:11:27 修改成一次统计查询
        $data = M('trade as t')->join('left join yy_channel as c on t.channel_id=c.id')
                        ->where($where)
                        ->field('sum(t.pay) as sumpay,count(*) as sumn,c.commission_ratio,c.id')
                        ->group('t.channel_id')->select();
        //未提现订单汇总
//        foreach($channel as $v){
//            $where['c.id']=$v['id'];
//            $data[]=M('trade as t')
//                ->join('left join yy_channel as c on t.channel_id=c.id')
////                ->join('left join yy_user as u on t.user_id=u.id')
////                ->join('left join yy_channel as c  on u.channel_id=c.id')
//                ->where($where)
//                ->field('sum(t.pay) as sumpay,count(*) as sumn,t.add_time,c.id,c.commission_ratio,c.id')
//                ->order('t.add_time desc')
//                ->find();
//        }

        $where1['date'] = date('Y-m-d', $Yesterday); //时间条件
        if ($b = M('closing')->where($where1)->count()) {
            $log->log('0', '已生成', date('Y-m-d H:i:s'));
        } else {
            // 存入财务管理数据表
            if (!empty($data)) {
                $addAlldata = array();
                foreach ($data as $v) {
                    if (empty($v['id'])) { // 如果渠道ID为空，则继续
                        $log->log('0', json_encode($v), date('Y-m-d H:i:s'));
                        continue;
                    }
                    $adddata = array();
                    $adddata['channel_id'] = $v['id'];
                    $adddata['count_pay'] = $v['sumn'];
                    $adddata['should_money'] = empty($v['sumpay']) ? 0 : $v['sumpay'] * $v['commission_ratio'] / 100; //
                    $adddata['sum_pay'] = empty($v['sumpay']) ? 0 : $v['sumpay'];
                    $adddata['date'] = date('Y-m-d', $Yesterday);
                    if ($adddata['sum_pay'] > 0) {
                        $adddata['status'] = 1;
                    } else {
                        $adddata['status'] = 2;
                    }
                    $adddata['add_time'] = time();
                    $addAlldata[] = $adddata;
                }
                $res = M('closing')->addAll($addAlldata);
                if ($res === false) {
                    $log->log('0', '插入数据库失败', date('Y-m-d H:i:s'));
                } else {
                    $log->log('0', '生成成功', date('Y-m-d H:i:s'));
                }
            }
        }
//        $this->display('succ');
    }

    public function verify1() {

        $config = array(
            'fontSize' => 18, // 验证码字体大小
            'length' => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'imageW' => 120,
            'useCurve' => false, // 是否画混淆曲线
            'imageH' => 40,
        );
        $verify = new \Think\Verify($config);
        $verify->entry('login');
    }

    function check_verify($code, $id = '') {
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    //修改密码
    public function changepwd() {
        $oldPwd = I("oldpassword");
        $newPwd = I("newpassword");
        $userid = session("user_info.id");

        $Admin = D('Admin');
        $oldPwd = $Admin->getPwd($oldPwd);
        $userPwd = M("admin")->where(array('id' => $userid))->getField("password");
        if (empty($oldPwd) || $userPwd !== $oldPwd) {
            $this->ajaxReturn(array('code' => 2, 'msg' => '旧密码不正确！'));
        }

        if (empty($newPwd)) {
            $this->ajaxReturn(array('code' => 2, 'msg' => '新密码不为空！'));
        }

        $res = M("admin")->where("id = '{$userid}'")->save(array('password' => $Admin->getPwd($newPwd),'session_time'=>0));
        if ($res !== false) {
            //清除session，重新登陆
            session('user_id', null);
            session_unset();
            $this->ajaxReturn(array('code' => 0, 'jump_url' => U('Public/login')));
        } else {
            $this->ajaxReturn(array('code' => 1));
        }
    }

    /**
     * 获取已绑定渠道
     */
    public function getChannel() {
        $user_id = session('user_id');
        $channelArr = M('admin_channel')->where('user_id =' . $user_id)->field('channel_id,member_id as vip_id')->find();
        if ($channelArr) {
            $this->ajaxReturn(array('code' => 200, 'data' => $channelArr));
        } else {
            $this->ajaxReturn(array('code' => 0, 'data' => array('channel_id'=>'','vip_id'=>'')));
        }
    }

    /**
     * 选择绑定渠道
     */
    public function selectChannel() {
        import('Common.Lib.Page');
        $name = I('get.name');
        $data = I('get.val');
        $where['status'] = array('eq', '1');
        if (!empty($name)) {
            if (is_numeric($name)) {
                $where['id'] = $name;
            } else {
                $where['nick_name'] = array('like', '%' . $name . '%');
            }
        }
        $channellistarr = '';
        if (!empty($data)) {
            $this->assign('data', $data);
            $orders = explode(',', $data);
            $channellistarr = $orders;
            if (!empty($orders)) {

                $wheres['id'] = array('in', $orders);
                $moviesin = M('channel')->where($wheres)->field('nick_name,id,member_id')->select();
                $this->assign('listin', $moviesin);
                $where['id'] = array('not in', $orders);
            }
        }
        $where['status'] = 1;
        $count = M('channel')->where($where)->count(1);
        $p = new \Common\Page($count, 20);
        $movies = M('channel')->where($where)->limit($p->firstRow, $p->listRows)->field('nick_name,id,member_id')->select();
        $this->assign('list', $movies);
        $this->assign('page', $p->show());
        $this->assign('channelArr', $channellistarr);
        $this->display('Public/selectChannel');
    }

    /**
     * 储存绑定渠道
     */
    public function saveChannel() {
        if (!IS_AJAX) {
            $this->ajaxReturn(array('code' => 0, 'data' => '非法访问'));
        }
        $user_id = session('user_id');
        $channel_id = I('post.channel_id');
        $channel_id_remove = I('post.channel_id_remove');
        
        $vip_id_id = I('post.vip_id');
        $vip_id_remove = I('post.vip_id_remove');
        
        $channel_arr['user_id'] = $user_id;
        
        $is_channel = M('admin_channel')->where('user_id =' . $user_id)->find();
        if ($is_channel) {
            $inset_channel_id = '';
            
            
            if($channel_id_remove != ''){ //如果有删除渠道ID
                $explodeisChannel = explode(',',$is_channel['channel_id']);
                $explodechannel_id_remove= explode(',',$channel_id_remove);

                foreach($explodeisChannel as $k => $ischannel){
                    if(!in_array($ischannel, $explodechannel_id_remove)){
                        $inset_channel_id .= $ischannel.',';
                    }
                }
                $inset_channel_id = trim($inset_channel_id,',');
            }else{
                $inset_channel_id = $is_channel['channel_id'];
            }
            
            if($channel_id != ''){ //如果有新增渠道ID
                $channel_arr['channel_id'] = $inset_channel_id.','.$channel_id;
            }else{
                 $channel_arr['channel_id'] = $inset_channel_id;
            }
            
            $inset_vip_id = '';
            if($vip_id_remove != ''){
                $explodeisVip = explode(',',$is_channel['member_id']);
                $explodeVip_id_remove= explode(',',$vip_id_remove);

                foreach($explodeisVip as $k => $isVip){
                    if(!in_array($isVip, $explodeVip_id_remove)){
                        $inset_vip_id .= $isVip.',';
                    }
                }
                $inset_vip_id = trim($inset_vip_id,',');
            }else{
                $inset_vip_id = $is_channel['member_id'];
            }
            
            if($vip_id_id != ''){
                $channel_arr['member_id'] = $inset_vip_id.','.$vip_id_id;
            }else{
                $channel_arr['member_id'] = $inset_vip_id;
            }
            $channel_arr['member_id'] = trim($channel_arr['member_id'],',');
            $channel_arr['channel_id'] = trim($channel_arr['channel_id'],',');
            $res3 = M('admin_channel')->where(['user_id' => $user_id])->save($channel_arr);
        } else {
            $channel_arr['member_id'] = $vip_id_id;
            $channel_arr['channel_id'] = $channel_id;
            $res3 = M('admin_channel')->add($channel_arr);
        }
        if ($res3) {
            $this->ajaxReturn(array('code' => 200, 'data' => '绑定成功'));
        } else {
            $this->ajaxReturn(array('code' => 0, 'data' => '绑定失败或没有任何变化'));
        }
    }

}

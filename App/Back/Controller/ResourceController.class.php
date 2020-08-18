<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/16 0016
 * Time: 1:47
 */

namespace Back\Controller;
use Common\Lib\Redis;
use Common\Page;
use Think\Upload;

class ResourceController extends CommonController {

    public function title() {

        $top = M('title')->where('pid = 0')->field('id,title')->select();
        $pid = I('pid', 0);

        $map['pid'] = array('eq', $pid);
        $this->_list('title', $map);

        $this->assign('pid', $pid);
        $this->assign('top', $top);
        $this->display();
    }

    public function doAddTitle() {
        $data = I('post.');
        if ($this->_add('title', $data)) {
            $this->success('添加成功');
        } else {
            $this->error('添加失败');
        }
    }

    public function doEditTitle() {
        $data = I('post.');
        $id = I('post.id');
        if (M('title')->where('id ="' . $id . '"')->save($data)) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    public function delTitle() {
        if (!IS_AJAX) {
            exit('非法入口');
        }
        $id = I('post.id');
        if (M('title')->where('id =' . $id)->delete()) {
            $this->ajaxReturn(array('code' => 200));
        } else {
            $this->ajacReturn(array('code' => 0));
        }
    }

    //后台推广列表
    public function index() {
        
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
        //所有渠道
        $channellist = M('channel')->field('id,nick_name,member_id')->select();
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
  
        foreach ($channellist as $val) {
            if ($val['member_id'] == 0) {
                $channellistarr[] = $val;
            }
        }
        $this->assign('channellistNew', $channellistarr);
        $this->assign('Viplist', $newChanel);
        $this->assign('channellist', $channellist);
        $values = I('get.val');
        if ($values && $values != 'null') {
            if (!empty(I('get.issou'))) {
                $vals = I('get.souVal');
                $where['e.channel_id'] = array('in', $vals);
                $this->assign('val', $vals);
            } else {
                $where['e.channel_id'] = array('in', $values);
                $this->assign('val', $values);
            }
            $this->assign('issou', '1');
        }
        //按照时间区间查询
        if (!empty(I('get.start_time')) && !empty(I('get.end_time')) && I('get.end_time') > I('get.start_time')) {
            $startTime = str_replace("+", " ", I('get.start_time'));
            $endTime = str_replace("+", " ", I('get.end_time'));
            $where['e.add_time'] = array(
                array('egt', strtotime($startTime)),
                array('elt', strtotime($endTime))
            );
            $this->assign('start_time', $startTime);
            $this->assign('end_time', $endTime);
        }
        if (!empty(I('get.remark'))) {
            $remark = I('get.remark');
            $where['e.remark'] = array('like', "%{$remark}%");
            $this->assign('remark', $remark);
        }
        //关键字搜索用户
        if (IS_GET) {
            $keywords = trim(I('get.nick_name'));
            if (!empty($keywords)) {
                $where['e.account'] = array('like', "%{$keywords}%");
                $this->assign('keywords', $keywords);
            }
            $Id = I('get.Id');
            if (!empty($Id)) {
                $where['e.id'] = $Id;
                $this->assign('Id', $Id);
            }
        }
        if(!empty(I('get.movies_id'))){
            $where['e.movies_id'] = I('get.movies_id');
        }
        if(!empty(I('get.movies_name'))){
            $namewhere['m.name'] = array('like', '%' . I('get.movies_name') . '%');
            $namewhere['m.org_name'] = array('like', '%' . I('get.movies_name') . '%', 'or');
            $namewhere['_logic'] = 'or';
            $where['_complex'] = $namewhere;
            $this->assign('movies_name', I('get.movies_name'));
        }
        

        $count = M('expand')->alias('e')->join('left join yy_movies as m on m.id = e.movies_id')->where($where)->count();
        import('Common.Lib.Page');
        $p = new Page($count, 20);
        $lead_num = M('user')->alias('u')->where('e.id = u.expand_id ')->field('count(*)')->buildSql(); //引导人数
        $follow_num = M('user')->alias('u')->where('e.id = u.expand_id and is_follow = 1')->field('count(*)')->buildSql(); //关注人数
        $pay_num = M('trade')->alias('t')->where('t.expand_id = e.id and pay_status = 1')->field('count(*)')->buildSql(); //充值成功笔数
        $pay_sum = M('trade')->alias('t')->where('t.expand_id = e.id and pay_status = 1')->field('SUM(t.pay)')->buildSql(); //充值总金额
        $voList = M('expand')
                ->alias('e')
                ->join('left join yy_movies as m on m.id = e.movies_id')
                ->where($where)
                ->order('add_time desc')
                ->limit($p->firstRow, $p->listRows)
                ->field('e.id,e.channel_id,e.nick_name,e.account,e.remark,' . $lead_num . ' lead_num,'
                        . '' . $follow_num . ' follow_num,e.cost,e.attention,e.add_time,' . $pay_sum . ' pay_sum,'
                        . '' . $follow_num . '/' . $lead_num . '*100 follow_pct,'
                        . '' . $pay_num . '/' . $lead_num . '*100 pay_pct,' . $pay_sum . '/cost answer_cost,cost/' . $follow_num . ' finsMon,'
                        . '(cost - ' . $pay_sum . '/100)/' . $follow_num . ' finsLr  ,indepth,m.name as movies_name,m.org_name')
                ->select();
        $redis = new Redis();
        foreach ($voList as &$value) {
            $key = 'total:expand-gz';
            $tokenKey = 'ExtensionTotal:tokenkey:' . $value['id'];
            $val = $value['id'];
            list($code, $result) = $redis->hget($key, $val);

            if ($code == 200) {
                $value['grandTotal'] = $result;
            } else {
                $value['grandTotal'] = 0;
            }
            $db = 3;
            $redisDb = new Redis($db);
            list($codeToken, $resultToken) = $redisDb->stringGet($tokenKey);
            if ($codeToken == 200) {
                $value['ExtensionTotal'] = $resultToken;
            } else {
                $value['ExtensionTotal'] = '';
            }
        }
        if (empty($voList)) {
            $this->assign('flag', 0);
        } else {
            $this->assign('flag', 1);
        }
        $this->assign('list', $voList);
        $this->assign('page', $p->show());
        $this->display();
    }

    //图库管理
    public function img() {
        $count = M('img')->where('status=1')->count();

        import('Common.Lib.Page');
        $p = new Page($count, 20);
        $data = M('img')
                ->where('status=1')
                ->limit($p->firstRow, $p->listRows)
                ->order('add_time desc')
                ->select();
        $this->assign('page', $p->show());
        $this->assign('data', $data);
        $this->display();
    }

    public function addImg() {

        $this->display();
    }

    public function doAddImg() {
        $data = I('post.');
//        $setting=C('UPLOAD_FILE_QINIU');//七牛配置
//        $setting['savePath'] = 'Resource/';
//        $setting['autoSub'] = false;
//        $Upload=new Upload($setting);
//        $info=$Upload->upload($_FILES);
//        if(empty($info['image']['url'])){
//            $this->error($Upload->getError());
//        }
//        $data['url'] = $info['image']['url'];
        $data['add_time'] = time();
        $result = M('img')->add($data);
        if ($result) {
            $this->success('添加成功');
        } else {
            $this->error('添加失败');
        }
    }

    //删除图片
    public function delImg() {
        if (IS_AJAX) {
            $id = I('post.id');
            $result = M('img')->where('id=' . $id)->setField('status', 0);
            if ($result) {
                $res['code'] = 0;
                $res['msg'] = 'ok';
            } else {
                $res['code'] = 1;
                $res['msg'] = 'error';
            }
            $this->ajaxReturn($res);
        }
    }

    //ajax查询
    public function ajaxedit() {
        if (!IS_AJAX) {
            return false;
        }
        $id = I('get.id');
        if ($data = M('title')->where('id=' . $id)->field('id,title')->find()) {
            $this->ajaxReturn(array('code' => 200, 'data' => $data));
            die;
        }
    }

    public function excelExport() {
        set_time_limit(0);
        $val = I('get.val');
        if ($val && $val != 'null') {
            $where['channel_id'] = array('in', $val);
        }
        //按照时间区间查询
        if (!empty(I('get.start_time')) && !empty(I('get.end_time')) && I('get.end_time') > I('get.start_time')) {
            $start_time = I('get.start_time');
            $end_time = I('get.end_time');
            $where['add_time'] = array(
                array('egt', strtotime($start_time)),
                array('elt', strtotime($end_time))
            );
        }
        if (!empty(I('get.remark'))) {
            $remark = I('get.remark');

            $where['remark'] = array('like', "%{$remark}%");
        }
        //关键字搜索用户
        $keywords = trim(I('get.nick_name'));
        if (!empty($keywords)) {
            $where['account'] = array('like', "%{$keywords}%");
        }
        $movies_id = trim(I('get.movies_id'));
        if (!empty($movies_id)) {
            $where['movies_id'] = $movies_id;
        }
        $lead_num = M('user')->alias('u')->where('e.id = u.expand_id ')->field('count(*)')->buildSql(); //引导人数
        $follow_num = M('user')->alias('u')->where('e.id = u.expand_id and is_follow = 1')->field('count(*)')->buildSql(); //关注人数
        $pay_num = M('trade')->alias('t')->where('t.expand_id = e.id and pay_status = 1')->field('count(*)')->buildSql(); //充值成功笔数
        $pay_sum = M('trade')->alias('t')->where('t.expand_id = e.id and pay_status = 1')->field('SUM(t.pay)')->buildSql(); //充值总金额
        $countNum = M('expand')->where($where)->count(1);
        
        if ($countNum > 100 && session('user_id') != 1) {
            $this->error('导出条数不得超过100条，请重试');
            return false;
        }
        $voList = M('expand')
                ->alias('e')
                ->where($where)
                ->order('id desc')
                ->field('id,channel_id,nick_name,account,remark,' . $lead_num . ' lead_num,' . $follow_num . ' follow_num,cost,attention,add_time,' . $pay_sum . ' pay_sum,' . $follow_num . '/' . $lead_num . '*100 follow_pct,' . $pay_num . ' pay_number,' . $pay_num . '/' . $lead_num . '*100 pay_pct,' . $pay_sum . '/cost answer_cost,indepth,pay_24')
                ->select();
        $time1 = date("Y-m-d");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="推广列表导出' . $time1 . '.xls"');
        header("Content-Transfer-Encoding:binary");
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);

        $objExcel->getProperties()->setTitle("推广统计");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('A1', 'id');
        $objExcel->getActiveSheet()->setCellValue('B1', '推广名称');
        $objExcel->getActiveSheet()->setCellValue('C1', '推广渠道');
        $objExcel->getActiveSheet()->setCellValue('D1', '粉丝量');
        $objExcel->getActiveSheet()->setCellValue('E1', '引导人数');
        $objExcel->getActiveSheet()->setCellValue('F1', '关注人数');
        $objExcel->getActiveSheet()->setCellValue('G1', '关注比例');
        $objExcel->getActiveSheet()->setCellValue('H1', '充值笔数');
        $objExcel->getActiveSheet()->setCellValue('I1', '充值比例');
        $objExcel->getActiveSheet()->setCellValue('J1', '总充值');
        $objExcel->getActiveSheet()->setCellValue('K1', '成本');
        $objExcel->getActiveSheet()->setCellValue('L1', '利润');
        $objExcel->getActiveSheet()->setCellValue('M1', '回本率');
        $objExcel->getActiveSheet()->setCellValue('N1', '24小时回本率');
        $objExcel->getActiveSheet()->setCellValue('O1', '24小时充值');
        $objExcel->getActiveSheet()->setCellValue('P1', '单粉推广成本');
        $objExcel->getActiveSheet()->setCellValue('Q1', '单粉利润成本');
        $objExcel->getActiveSheet()->setCellValue('R1', '推广时间');
        $objExcel->getActiveSheet()->setCellValue('S1', '备注');
        $i = 2;
        foreach ($voList as $key => $value) {
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $value['id']);
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $value['account']);
            $objExcel->getActiveSheet()->setCellValue('C' . $i, getchannel($value['channel_id']));
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $value['nick_name']);
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $value['lead_num']);
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $value['follow_num']);
            $objExcel->getActiveSheet()->setCellValue('G' . $i, round($value['follow_pct'], 2) . "%");
            $objExcel->getActiveSheet()->setCellValue('H' . $i, $value['pay_number']); //充值笔数
            $objExcel->getActiveSheet()->setCellValue('I' . $i, round($value['pay_pct'], 2) . "%");
            $objExcel->getActiveSheet()->setCellValue('J' . $i, getReadMoney($value['pay_sum']));
            $objExcel->getActiveSheet()->setCellValue('K' . $i, $value['cost']);
            $lirun = $value['pay_sum'] / 100 - $value['cost']; //利润
            $objExcel->getActiveSheet()->setCellValue('L' . $i, $lirun); //利润
            $objExcel->getActiveSheet()->setCellValue('M' . $i, round($value['answer_cost'], 2) . "%");
            $hb24 = !is_nan($value['pay_24'] / $value['cost']) && $value['cost'] != 0 ? round($value['pay_24'] / $value['cost'], 2) . "%" : '-';
            $objExcel->getActiveSheet()->setCellValue('N' . $i, $hb24); //24小时回本率
            $objExcel->getActiveSheet()->setCellValue('O' . $i, getReadMoney($value['pay_24'])); //24小时充值
            $objExcel->getActiveSheet()->setCellValue('P' . $i, round($value['cost'] / $value['follow_num'], 2));
            $objExcel->getActiveSheet()->setCellValue('Q' . $i, round($lirun / $value['follow_num'], 2));
            $objExcel->getActiveSheet()->setCellValue('R' . $i, date('Y-m-d H:i:s', $value['add_time']));
            $objExcel->getActiveSheet()->setCellValue('S' . $i, $value['remark']);
            $i++;
        }
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objWriter->save('php://output');
    }

    /**
     * 添加外推链接
     */
    public function addExtension() {
        $data = I('post.');
        if (!IS_AJAX) {
            $data = array('code' => 400, 'res' => '非法访问');
            $this->ajaxReturn($data);
        }
        if ($data['id'] == '' || $data['url'] == '' || $data['name'] == '') {
            $data = array('code' => 400, 'res' => '参数错误');
            $this->ajaxReturn($data);
        }
        $db = 3;
        $token = md5('ExtensionTotal' . time() . rand(10000, 99999));
        $redis = new Redis($db);
        $tokenKey = 'ExtensionTotal:tokenkey:' . $data['id'];
        list($codeToken) = $redis->stringSet($tokenKey, $token); //插入token
//        $lead_num = M('user')->where('expand_id =' . $data['id'])->count('1'); //引导人数
        $tokenHset = 'ExtensionTotal:contentkey:' . $token;
//        list($num_code) = $redis->Hset($tokenHset, 'lead_num', $lead_num); //插入引导人数
        list($url_code) = $redis->Hset($tokenHset, 'url', $data['url']); //插入引导人数
        list($name_code) = $redis->Hset($tokenHset, 'name', $data['name']); //插入引导人数
        list($id_code) = $redis->Hset($tokenHset, 'id', $data['id']); //插入引导人数
        if ($data['end_time'] != '') {
            $setTime = strtotime($data['end_time']);
            $indexTime = time();
            if ($setTime < $indexTime) {
                $data = array('code' => 400, 'res' => '设置时间不能小于当前时间');
                $this->ajaxReturn($data);
            }
            $sxtime = $setTime - $indexTime;
            $redis->expire($tokenKey, $sxtime);
            $redis->expire($tokenHset, $sxtime);
        }
        if ($codeToken == 200 && $id_code == 200 && $url_code == 200 && $name_code == 200) {
            $data = array('code' => 200, 'res' => '设置成功');
            $this->ajaxReturn($data);
        } else {
            $data = array('code' => 200, 'res' => '设置失败');
            $this->ajaxReturn($data);
        }
    }

    /**
     * 查看外推订单详情
     */
    public function details(){
        $inner_id = I('get.expand_id');
        import('Common.Lib.Page');
        $page_size = C('PAGE_LIST_SIZE');
        if(!empty($inner_id)){
            $where['a.expand_id'] = $inner_id;
            $where['a.pay_status'] = 1;
            $count = M('trade as a')->where($where)->count(1);
            $page = new \Common\Page($count, $page_size, $_GET);
            $resSql = M('trade as a')->where($where)->join('yy_user_info as b on a.user_id = b.user_id')
                    ->field('b.nick_name,a.trade_no,a.pay,a.type,a.add_time,a.user_id')
                    ->limit($page->firstRow, $page->listRows)->order('a.id desc')->select();
            $this->assign('list',$resSql);
            $this->assign('page',$page->show());
        }
        $this->display();
    }
}

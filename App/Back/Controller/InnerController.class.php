<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/6 0006
 * Time: 0:55
 */

namespace Back\Controller;
use Appc\Page;
use Think\Controller;

class InnerController extends CommonController {
    //内推列表
    public function index(){
        
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
        
        $where= array();
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
        $keywords=trim(I('get.nick_name'));
        if (!empty($keywords)){
            $where['a.nick_name']=array('like','%'.$keywords.'%');
            $this->assign('keywords',$keywords);
        }
        $remark=trim(I('get.remark'));
        if (!empty($remark)){
            $where['a.remark']=array('like','%'.$remark.'%');
            $this->assign('remark',$remark);
        }
        $val=I('get.val',null);
        if($val== 1){
            $where['c.is_youying']= array('eq',1);
            $this->assign('val',$val);
        }else if($val== 2){
            $where['c.is_youying']= array('neq',1);
            $this->assign('val',$val);
        }else if (!empty ($val) && $val != 0){
            $explode = explode(',', $val);
            $where['a.channel_id']= array('in',$explode);
            $this->assign('val',$val);
            $this->assign('issou','1');
        }else if($val == 0 && $val != null){
        }else if($oml_channel){
            $where['a.channel_id']= array('in',$oml_channel);
            $this->assign('val',$oml_channel);
        }
        $startTime = I('get.start_time');
        $endTime = I('get.end_time');
        if (!empty($startTime) && !empty($endTime)) {
            if ($endTime < $startTime){ $this->error('结束时间不能小于开始时间');}
            $startTime =date('Y-m-d', strtotime($startTime));
            $endTime =date('Y-m-d', strtotime($endTime));
            $where['a.add_time']= array(
                array('egt',strtotime($startTime.' 00:00:00')),
                array('elt',strtotime($endTime.' 23:59:59'))
            );
            $this->assign('startTime',$startTime); $this->assign('endTime', $endTime);
        }
        if(!empty(I('get.movies_id'))){
            $where['a.movies_id'] = I('get.movies_id');
            $this->assign('movies_id',I('get.movies_id'));
        }
        $count=M('innerexpand as a')->join('left join yy_channel as c on c.id = a.channel_id')->join('left join yy_movies as m on m.id = a.movies_id')->where($where)->count(1);
        import('Common.Lib.Page');
        $p=new \Common\Page($count,20);

        $data=M('innerexpand as a')->where($where)->join('left join yy_movies as m on m.id = a.movies_id')
                ->join('left join yy_channel as c on c.id = a.channel_id')
                ->field('a.id,a.channel_id,a.nick_name,a.remark,a.indepth,a.click_num,a.gold_num,a.add_time,m.name as movies_name,m.org_name,a.status')
                ->order('a.add_time desc')->limit($p->firstRow,$p->listRows)->select();
        foreach ($data as $k => $v){
            $data[$k]['pay_sum']=M('trade')->where('innerexpand_id='.$v['id'].' and pay_status = 1')->sum('pay');
            $data[$k]['count']=M('trade')->where('innerexpand_id='.$v['id'].' and pay_status = 1')->count(1);
        }
        if (empty($data)){
            $this->assign('flag',0);
        }else{
            $this->assign('flag',1);
        }
       
        $this->assign('page',$p->show());
        $this->assign('data',$data);
        $this->display();
    }
    
    /**
     * 订单详情
     */
    public function details() {
        import('Common.Lib.Page');
        $id = I('get.innerexpand_id');
     
        $where['b.innerexpand_id'] = $id;
        $where['b.pay_status'] = 1;
        $count = M('trade as b')->where($where)->count(1);
        $p = new \Common\Page($count, 20);
        $sql = M('user_info as a')->where('a.user_id = b.user_id')->field('a.nick_name')->select(false);
        $movies = M('trade as b')->where($where)->limit($p->firstRow, $p->listRows)->field('b.trade_no,b.pay_status,b.add_time,b.type,b.pay,(' . $sql . ') as nick_name,b.user_id')->order('b.add_time desc')->select();
        $this->assign('list', $movies);
        $this->assign('page', $p->show());

        $this->display();
    }
    
    
    public function innerEx(){
        $where= array();
        $channellist = M('channel')->getField('id,nick_name');
        
        $keywords=trim(I('get.nick_name'));
        if (!empty($keywords)){
            $where['a.nick_name']=array('like','%'.$keywords.'%');
        }
        $val=I('get.val',null);
        if($val== 1){
            $where['c.is_youying']= array('eq',1);
            $this->assign('val',$val);
        }else if($val== 2){
            $where['c.is_youying']= array('neq',1);
            $this->assign('val',$val);
        }else if (!empty ($val) && $val != 0){
            $explode = explode(',', $val);
            $where['a.channel_id']= array('in',$explode);
            $this->assign('val',$val);
            $this->assign('issou','1');
        }else if($val == 0 && $val != null){
        }else if($oml_channel){
            $where['a.channel_id']= array('in',$oml_channel);
            $this->assign('val',$oml_channel);
        }
        
        $movies_id=I('get.movies_id');
        if(!empty($movies_id)){
            $where['a.movies_id']= array('in',$movies_id);
        }
        $startTime = I('get.start_time');
        $endTime = I('get.end_time');
        if(empty($startTime) || empty($endTime)){$this->error('请选择时间');}
        if (!empty($startTime) && !empty($endTime)) {
            if ($endTime < $startTime){ $this->error('结束时间不能小于开始时间');}
            $where['a.add_time']= array(
                array('egt',strtotime(date('Y-m-d',strtotime($startTime)))),
                array('elt',strtotime(date('Y-m-d 23:59:59',strtotime($endTime))))
            );
        }
        $inner = M('innerexpand as a')->where($where)
                ->join('left join yy_channel as c on c.id = a.channel_id')
                ->join('yy_movies as m on a.movies_id = m.id')
                ->order('a.add_time desc')->field('a.*,m.name,m.org_name')->limit(5000)->select();
        $time1 =date("Y-m-d");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="内推数据导出'.$time1.'.xls"');
        header("Content-Transfer-Encoding:binary");
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        
        
        $objExcel->getProperties()->setTitle("推广统计");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('A1',$time1.'报表');
        $objExcel->getActiveSheet()->mergeCells('A1:J1');
        
        $objExcel->getActiveSheet()->setCellValue('A2', 'id');
        $objExcel->getActiveSheet()->setCellValue('B2', '渠道');
        $objExcel->getActiveSheet()->setCellValue('C2', '名称');
        $objExcel->getActiveSheet()->setCellValue('D2', '图解名称');
        $objExcel->getActiveSheet()->setCellValue('E2', '备注');
        $objExcel->getActiveSheet()->setCellValue('F2', '观看人数');
        $objExcel->getActiveSheet()->setCellValue('G2', '消费金币数');
        $objExcel->getActiveSheet()->setCellValue('H2', '消费指数');
        $objExcel->getActiveSheet()->setCellValue('I2', '消费金额');
        $objExcel->getActiveSheet()->setCellValue('J2', '充值笔数');
        $objExcel->getActiveSheet()->setCellValue('K2', '添加时间');
        
        //设置宽度
        $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(12);
        $objExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(12);
        $objExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(false);
        $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(12);
        
        $count=count( $inner );
        $Trade = M('trade');
        for ($i = 3; $i <= $count+2; $i++) {
            $bi = 0;$payTotal=0;
            if (!empty($inner[$i-3]['click_num']) && !empty($inner[$i-3]['gold_num'])) {
                $bi = round($inner[$i-3]['gold_num']/$inner[$i-3]['click_num']);

            }
            if(!empty($inner[$i-3]['click_num'])){
                $map = array('innerexpand_id'=>$inner[$i-3]['id'], 'pay_status'=>1);
                $payTotal =  $Trade->where($map)->sum('pay');
                $countNumber = M('trade')->where($map)->count(1);
            }
            $objExcel->getActiveSheet()->setCellValue('A'.$i, $inner[$i-3]['id']);
            $objExcel->getActiveSheet()->setCellValue('B'.$i, $channellist[$inner[$i-3]['channel_id']]);
            $objExcel->getActiveSheet()->setCellValue('C'.$i, $inner[$i-3]['account']);
            $name = $inner[$i-3]['name'].($inner[$i-3]['org_name'] ? "\r\n 原名：".$inner[$i-3]['org_name'] : '');
            $objExcel->getActiveSheet()->setCellValue('D'.$i, $name);
            $objExcel->getActiveSheet()->getStyle('D'.$i)->getAlignment()->setWrapText(true);
            $objExcel->getActiveSheet()->setCellValue('E'.$i, $inner[$i-3]['remark']);
            $objExcel->getActiveSheet()->setCellValue('F'.$i, $inner[$i-3]['click_num']);
            $objExcel->getActiveSheet()->setCellValue('G'.$i, $inner[$i-3]['gold_num']);
            $objExcel->getActiveSheet()->setCellValue('H'.$i, $bi);
            $objExcel->getActiveSheet()->setCellValue('I'.$i, $payTotal/100);
            $objExcel->getActiveSheet()->setCellValue('J'.$i,$countNumber );
            $objExcel->getActiveSheet()->setCellValue('K'.$i,date("Y-m-d,H:i:s",$inner[$i-3]['add_time']) );
        }
        
        
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objWriter->save('php://output');
    }
   
}
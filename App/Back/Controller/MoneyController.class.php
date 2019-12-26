<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/19 0019
 * Time: 10:35
 */

namespace Back\Controller;

use Think\Controller;
use Think\Page;

class MoneyController extends CommonController {

    //订单统计
    public function order() {
        $map = array();
        $trade = D('TradeView');
        $startDate = I('post.start_time');
        $endDate = I('post.end_time');
        $is_youying = I('post.is_youying');
        $limit = '';
        if (!empty($startDate) || !empty($endDate)) {
            $map['pay.date'] = array(array('EGT', $startDate), array('ELT', $endDate));
            $this->assign('start_time', $startDate);
            $this->assign('end_time', $endDate);
        } else {
            $limit = 30;
        }
        $join = '';
        if ($is_youying != '' && $is_youying != 2) {
            $join = 'yy_channel as cha on cha.id = pay.channel_id and cha.is_youying = ' . $is_youying;
            $this->assign('is_youying', $is_youying);
        } else {
            $this->assign('is_youying', 2);
        }
        $result = M('pay_data as pay')
                ->field("sum(pay.pay) sum_pay,
            sum(pay.normal_success_count) sum_success_count,
            sum(pay.normal_error_count) sum_error_count,
            sum(pay.normal_nnt_count) sum_nnt_count,
            sum(pay.normal_pay) sum_normal_pay,
            sum(pay.vip_success_count) sum_vip_success_count,
            sum(pay.vip_error_count) sum_vip_error_count,
            sum(pay.vip_nnt_count) sum_vip_nnt_count,
            sum(pay.vip_pay) sum_vip_pay,date
            ")
                ->where($map)
                ->join($join)
                ->limit($limit)
                ->order('pay.date desc')
                ->group("pay.date")
                ->select();
//        echo "<pre>";
//        print_r($result);die;
        foreach ($result as $k => $v) {
            $result[$k]['normal_finish_rate'] = $v['sum_success_count'] + $v['sum_error_count'] == 0 ? 0 : round(($v['sum_success_count'] / ($v['sum_success_count'] + $v['sum_error_count'])) * 100, 2); //普通充值完成率

            $result[$k]['normal_pay_avgprice'] = $v['sum_nnt_count'] == 0 ? 0 : getReadMoney($v['sum_normal_pay'] / $v['sum_nnt_count']); //普通充值人均

            $result[$k]['vip_finish_rate'] = $v['sum_vip_success_count'] + $v['sum_vip_error_count'] == 0 ? 0 : round(($v['sum_vip_success_count'] / ($v['sum_vip_success_count'] + $v['sum_vip_error_count'])) * 100, 2); //vip充值完成率

            $result[$k]['vip_avgprice'] = $v['sum_vip_nnt_count'] == 0 ? 0 : getReadMoney($v['sum_vip_pay'] / $v['sum_vip_nnt_count']); //vip充值人均
        }
        //当日充值/笔数/完成率
        $day_data = (int) $trade->where(array(
                    'pay_time' => array(
                        array('egt', strtotime(date('Y-m-d', time()))),
                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                    ),
                    'pay_status' => 1,
//            'channel_id'=>$user_id
                ))->sum('pay'); //总充值

        $day_normal_pay = (int) $trade->where(array(
                    'pay_time' => array(
                        array('egt', strtotime(date('Y-m-d', time()))),
                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                    ),
                    'pay_status' => 1,
                    'type' => 0,
//            'channel_id'=>$user_id
                ))->sum('pay'); //普通充值

        $day_pay_count = (int) $trade->where(array(
                    'add_time' => array(
                        array('egt', strtotime(date('Y-m-d', time()))),
                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                    ),
                    'pay_status' => 1,
                    'type' => 0,
//            'channel_id'=>$user_id
                ))->count(); //普通充值支付笔数

        $day_pay_error_count = (int) $trade
                        ->where(
                                array('add_time' => array(
                                        array('egt', strtotime(date('Y-m-d', time()))),
                                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                                    ), 'type' => 0,
                                    'pay_status' => 0,
//                'channel_id'=>$user_id
                        ))->count(); //普通充值未支付笔数

        $day_pay_rate = $day_pay_count + $day_pay_error_count == 0 ? 0 : round($day_pay_count /
                        ($day_pay_count + $day_pay_error_count) * 100, 2); //普通充值完成率
        //vip充值
        $day_vip_pay = (int) $trade->where(array(
                    'pay_time' => array(
                        array('egt', strtotime(date('Y-m-d', time()))),
                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                    ),
                    'type' => 1,
                    'pay_status' => 1,
//            'channel_id'=>$user_id
                ))->sum('pay'); //vip充值

        $day_vip_pay_count = (int) $trade->where(array(
                    'add_time' => array(
                        array('egt', strtotime(date('Y-m-d', time()))),
                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                    ),
                    'type' => 1,
                    'pay_status' => 1,
//            'channel_id'=>$user_id
                ))->count(); //vip充值支付笔数

        $day_vip_pay_error_count = (int) $trade->where(array(
                    'add_time' => array(
                        array('egt', strtotime(date('Y-m-d', time()))),
                        array('lt', strtotime(date('Y-m-d', time()) . '+1 day'))
                    ),
                    'type' => 1,
                    'pay_status' => 0,
//                'channel_id'=>$user_id
                ))->count(); //vip充值未支付笔数

        $day_vip_pay_rate = $day_vip_pay_count + $day_vip_pay_error_count == 0 ? 0 : round(($day_vip_pay_count / ($day_vip_pay_count + $day_vip_pay_error_count)) * 100, 2); //vip充值完成率

        $this->assign('day_vip_pay', $day_vip_pay);
        $this->assign('day_vip_pay_count', $day_vip_pay_count);
        $this->assign('day_vip_pay_error_count', $day_vip_pay_error_count);
        $this->assign('day_vip_pay_rate', $day_vip_pay_rate);

        $this->assign('day_data', $day_data);
        $this->assign('day_pay_count', $day_pay_count);
        $this->assign('day_pay_error_count', $day_pay_error_count);
        $this->assign('day_pay_rate', $day_pay_rate);
        $this->assign('day_normal_pay', $day_normal_pay);

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        //昨日充值额
        $yesterday_data = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('pay');

        //昨日普通充值
        $yesterday_normal_pay = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('normal_pay');

        //昨日普通充值数
        $yesterday_normal_pay_count = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('normal_success_count');

        //昨日充值失败数
        $yesterday_error_pay_count = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('normal_error_count');

        //昨日充值完成率
        $yesterday_normal_pay_rate = $yesterday_normal_pay_count + $yesterday_error_pay_count == 0 ? 0 : round($yesterday_normal_pay_count / ($yesterday_normal_pay_count + $yesterday_error_pay_count) * 100, 2);

        $this->assign('yesterday_data', $yesterday_data);
        $this->assign('yesterday_normal_pay', $yesterday_normal_pay);
        $this->assign('yesterday_normal_pay_count', $yesterday_normal_pay_count);
        $this->assign('yesterday_error_pay_count', $yesterday_error_pay_count);
        $this->assign('yesterday_normal_pay_rate', $yesterday_normal_pay_rate);

        //vip充值额
        $yesterday_vip_pay = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('vip_pay');

        //vip充值成功数
        $yesterday_vip_pay_count = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('vip_success_count');

        //vip充值失败数
        $yesterday_vip_error_pay_count = M('pay_data')
                ->where(array(
                    'date' => array('eq', $yesterday),
//                'channel_id'=>$user_id
                ))
                ->sum('vip_error_count');

        //vip充值完成率
        $yesterday_vip_pay_rate = $yesterday_vip_pay_count + $yesterday_vip_error_pay_count == 0 ? 0 : round($yesterday_vip_pay_count / ($yesterday_vip_pay_count + $yesterday_vip_error_pay_count) * 100, 2);
        $this->assign('yesterday_data', $yesterday_data);
        $this->assign('yesterday_normal_pay', $yesterday_normal_pay);
        $this->assign('yesterday_normal_pay_count', $yesterday_normal_pay_count);
        $this->assign('yesterday_error_pay_count', $yesterday_error_pay_count);
        $this->assign('yesterday_normal_pay_rate', $yesterday_normal_pay_rate);

        $this->assign('yesterday_vip_pay', $yesterday_vip_pay);
        $this->assign('yesterday_vip_pay_count', $yesterday_vip_pay_count);
        $this->assign('yesterday_vip_error_pay_count', $yesterday_vip_error_pay_count);
        $this->assign('yesterday_vip_pay_rate', $yesterday_vip_pay_rate);

        $start_day = date('Y-m-01');
        $end_day = date('Y-m-d');
        //当月充值额
        $month_data = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('pay');

        //当月普通充值
        $month_normal_pay = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('normal_pay');

        //当月普通充值成功数
        $month_pay_count = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('normal_success_count');

        //当月普通充值失败数
        $month_pay_error_count = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('normal_error_count');

        //当月普通充值完成率
        $month_pay_rate = $month_pay_count + $month_pay_error_count == 0 ? 0 : round($month_pay_count / ($month_pay_count + $month_pay_error_count) * 100, 2);

        //vip充值额
        $month_vip_pay = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('vip_pay');

        //vip充值成功数
        $month_vip_pay_count = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('vip_success_count');

        //vip充值失败数
        $month_vip_pay_error_count = M('pay_data')
                ->where(array(
                    'date' => array(array('egt', $start_day), array('elt', $end_day)),
//                'channel_id'=>$user_id
                ))
                ->sum('vip_error_count');

        //vip充值完成率
        $month_vip_pay_rate = $month_vip_pay_count + $month_vip_pay_error_count == 0 ? 0 : round($month_vip_pay_count / ($month_vip_pay_count + $month_vip_pay_error_count) * 100, 2);

        $this->assign('month_data', $month_data);
        $this->assign('month_normal_pay', $month_normal_pay);
        $this->assign('month_pay_count', $month_pay_count);
        $this->assign('month_pay_error_count', $month_pay_error_count);
        $this->assign('month_pay_rate', $month_pay_rate);

        $this->assign('month_vip_pay', $month_vip_pay);
        $this->assign('month_vip_pay_count', $month_vip_pay_count);
        $this->assign('month_vip_pay_error_count', $month_vip_pay_error_count);
        $this->assign('month_vip_pay_rate', $month_vip_pay_rate);

        //总充值
        $sum_pay = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('pay');
        $this->assign('sum_pay', $sum_pay);
        //普通充值
        $normal_pay = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('normal_pay');
        $this->assign('normal_pay', $normal_pay);
        //普通支付成功笔数
        $normal_pay_count = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('normal_success_count');
        $this->assign('normal_pay_count', $normal_pay_count);
        //普通未支付笔数
        $normal_error_count = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('normal_error_count');
        $this->assign('normal_error_count', $normal_error_count);
        //普通充值完成率
        $normal_finish_rate = round(($normal_pay_count / ($normal_pay_count + $normal_error_count)) * 100, 2);
        $this->assign('normal_finish_rate', $normal_finish_rate);
        //普通人均充值
        $people_count = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('normal_nnt_count');
        $average_pay = $people_count == 0 ? 0 : getReadMoney($sum_pay / $people_count);
        $this->assign('average_pay', $average_pay);
        //vip充值金额
        $vip_pay = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('vip_pay');
        $this->assign('vip_pay', $vip_pay);
        //vip充值笔数
        $vip_pay_count = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('vip_success_count');
        $this->assign('vip_pay_count', $vip_pay_count);
        //vip未支付笔数
        $vip_error_count = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('vip_error_count');
        $this->assign('vip_error_count', $vip_error_count);
        //vip充值完成率
        $vip_finish_rate = round(($vip_pay_count / ($vip_pay_count + $vip_error_count)) * 100, 2);
        $this->assign('vip_finish_rate', $vip_finish_rate);
        //vip充值人数
        $vip_people_count = M('pay_data')
//            ->where(array('channel_id'=>$user_id))
                ->sum('vip_nnt_count');
        //vip人均充值
        $vip_average_pay = $vip_people_count == 0 ? 0 : getReadMoney($vip_pay / $vip_people_count);
        $this->assign('vip_average_pay', $vip_average_pay);
        $this->assign('list', $result);
        session('download_word', $result);
        $this->display();
    }

    /**
     * 导出Word
     */
    public function downloadWord() {
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="订单统计' . date('YmdHis') . '.xls"');
        header("Content-Transfer-Encoding:binary");
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        $objExcel->getProperties()->setTitle("订单统计");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('A1', '日期');
        $objExcel->getActiveSheet()->setCellValue('B1', '充值金额');
        $objExcel->getActiveSheet()->setCellValue('C1', '普通充值');
        $objExcel->getActiveSheet()->setCellValue('D1', '普通充值人数');
        $objExcel->getActiveSheet()->setCellValue('E1', '普通人均');
        $objExcel->getActiveSheet()->setCellValue('F1', '普通充值支付订单数');
        $objExcel->getActiveSheet()->setCellValue('G1', '普通充值支付订单数未支付笔数');
        $objExcel->getActiveSheet()->setCellValue('H1', '普通充值支付订单数完成率');
        $objExcel->getActiveSheet()->setCellValue('I1', '年费vip会员');
        $objExcel->getActiveSheet()->setCellValue('J1', '年费vip会员充值人数');
        $objExcel->getActiveSheet()->setCellValue('K1', '年费vip会员人数人均');
        $objExcel->getActiveSheet()->setCellValue('L1', '年费vip会员支付订单数');
        $objExcel->getActiveSheet()->setCellValue('M1', '年费vip会员支付订未支付笔数');
        $objExcel->getActiveSheet()->setCellValue('N1', '年费vip会员支付完成率');
        $i = 2;
        $voList = session('download_word');
        if (empty($voList)) {
            $this->error('数据为空，请筛选后进行导出操作');
        }
        foreach ($voList as $value) {
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $value['date']);
            $objExcel->getActiveSheet()->setCellValue('B' . $i, getReadMoney($value['sum_pay']));
            $objExcel->getActiveSheet()->setCellValue('C' . $i, getReadMoney($value['sum_normal_pay']));
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $value['sum_nnt_count']);
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $value['normal_pay_avgprice']);
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $value['sum_success_count']);
            $objExcel->getActiveSheet()->setCellValue('G' . $i, $value['sum_error_count']);
            $objExcel->getActiveSheet()->setCellValue('H' . $i, $value['normal_finish_rate'] . '%');
            $objExcel->getActiveSheet()->setCellValue('I' . $i, getReadMoney($value['sum_vip_pay']));
            $objExcel->getActiveSheet()->setCellValue('J' . $i, $value['sum_vip_nnt_count']);
            $objExcel->getActiveSheet()->setCellValue('K' . $i, $value['vip_avgprice']);
            $objExcel->getActiveSheet()->setCellValue('L' . $i, $value['sum_vip_success_count']);
            $objExcel->getActiveSheet()->setCellValue('M' . $i, $value['sum_vip_error_count']);
            $objExcel->getActiveSheet()->setCellValue('N' . $i, $value['vip_finish_rate'] . '%');
            $i++;
        }
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objWriter->save('php://output');
    }

    //提现中汇总
    public function withdrawaling() {
        $channel = D('channel')->field('id,nick_name')->select();
        //按照时间筛选
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        if (!empty($start_time) && !empty($end_time)) {
            $where1['a.date'] = array('BETWEEN', array($start_time, $end_time));
            $this->assign('start_time', I('get.start_time'));
            $this->assign('end_time', I('get.end_time'));
        }

        $where['number'] = array('NEQ', '');
        $shoukuannum = M('gathering_information')->field('number,name')->distinct('number')->where($where)->select();
        foreach($shoukuannum as &$value){
            $numwhere['number'] = $value['number'];
            $idarr = M('gathering_information')->where($numwhere)->find();
            $value['id'] = $idarr['id'];
        }
        $number = I('shoukuan');
        if ($number > 0) {
            $number = str_replace('+', ' ', $number);
            $where1['c.number'] = $number;
        }

        $id = I('channel_id');
        if ($id > 0) {
            $where1['c.channel_id'] = $id;
        }
        if ($number > 0 && !empty($start_time) && !empty($end_time)) {

            $jsonArr['start_time'] = $start_time;
            $jsonArr['end_time'] = $end_time;
            $jsonArr['number'] = $number;
            $jsonArr['channel_id'] = $id;
            $jsonstr = json_encode($jsonArr);
            $this->assign('openbatch', $jsonstr);
        }
        $Pagenum = 20;
        import('Common.Lib.Page');
        $where1['a.status'] = 1;
        $count1 = M('closing as a')
                ->where($where1)
                ->join('yy_channel as b on a.channel_id=b.id')
                ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->count();

        $page1 = new \Common\Page($count1, $Pagenum);
        $data1 = M('closing as a')
                ->where($where1)
                ->join('yy_channel as b on a.channel_id=b.id')
                ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->field('b.nick_name,a.add_time,a.should_money,a.sum_pay,a.count_pay,a.status,c.bankname,c.number,c.name,a.id,c.channel_id,a.date,c.pay_type')
                ->order('a.date desc,a.id desc')
                ->limit($page1->firstRow . ',' . $page1->listRows)
                ->select();
        foreach ($data1 as $k => $v) {
            $data1[$k]['new_sum_pay'] = $v['sum_pay'] / 100;
            $data1[$k]['new_should_money'] = $v['should_money'] / 100;
        }

        $sum = M('closing as a')
                ->join('yy_channel as b on a.channel_id=b.id')
                ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->where($where1)
                ->field('sum(sum_pay) as new_sun_money ,sum(should_money) as sum_shold_money, sum(count_pay) as sum_num')
                ->find();
        $this->assign('sum', $sum);

        $show1 = $page1->show();
        $this->assign('shoukuannum', $shoukuannum);
        $this->assign('id', $id);
        $this->assign('channel', $channel);
        $this->assign('number', $number);
        $this->assign('page1', $show1);

        if (empty($data1)) {
            $this->assign('flag', 0);
        } else {
            $this->assign('flag', 1);
        }
        $this->assign('data1', $data1);
        $this->display();
    }

    //完成打款（确认）
    public function Transfer_money() {
        if (!IS_AJAX) {
            return;
        }
        $channelId = I('post.channel_id');
        $reslut = M('gathering_information')->where('channel_id = ' . $channelId)->field('pay_type,bankname,name,number')->find();
        $data['time'] = time();
        $data['operation'] = session('user_id');
        $data['status'] = $_POST['status'];
        $newData = array_merge($data, $reslut);
        $where['id'] = $_POST['id'];
        if (M('closing')->where($where)->save($newData)) {
            $this->ajaxReturn(array('code' => 200));
        } else {
            $this->ajaxReturn(array('code' => 0));
        }
    }

    //批量完成打款（确认）
    public function Transfer_money_batch() {
        if (!IS_AJAX) {
            return;
        }
        $data['time'] = time();
        $data['operation'] = session('user_id');
        $data['status'] = I('post.status');
        $jsonArr = I('post.dataArr');
        $start_time = $jsonArr['start_time'];
        $end_time = $jsonArr['end_time'];
        $number = $jsonArr['number'];
        $id = $jsonArr['channel_id'];
        if (empty($number) || empty($start_time) || empty($end_time)) {
            $this->ajaxReturn(array('code' => 0));
        }
        $where['a.date'] = array('BETWEEN', array($start_time, $end_time));
        $where['c.number'] = $number;
        if (!empty($id)) {
            $where['c.channel_id'] = $id;
        }
        $where['a.status'] = 1;
        $Id = M('closing as a')
                        ->where($where)
                        ->join('yy_channel as b on a.channel_id=b.id')
                        ->join('yy_gathering_information as c on c.channel_id=a.channel_id')->field('a.id,b.id as channel_id')->select();
        $newarr = [];
        M()->startTrans();
        foreach ($Id as $k => $val) {
            $newarr[$k]['id'] = $val['id'];
            $newarr[$k]['channel_id'] = $val['channel_id'];
        }
        foreach ($newarr as $value) {
            $result = M('gathering_information')->where('channel_id = ' . $value['channel_id'])->field('pay_type,bankname,name,number')->find();
            $newData = array_merge($data, $result);
            $res = M('closing')->where('id =' . $value['id'])->save($newData);
            if (!$res) {
                M()->rollback();
                $this->ajaxReturn(array('code' => 0));
            }
        }
        M()->commit();
        $this->ajaxReturn(array('code' => 200));
    }

    //已提现汇总
    public function withdrawal_off() {

        //按照时间筛选
        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $start_time1 = strtotime($start_time);
        $end_time1 = strtotime($end_time);
        if (!empty($start_time) && $start_time1 && $end_time1) {
            $where2['a.date'] = array('BETWEEN', array($start_time, $end_time));
            $this->assign('start_time', I('get.start_time'));
            $this->assign('end_time', I('get.end_time'));
        }
        //按照渠道筛选
        $id = I('channel_id');
        if ($id > 0) {
            $where2['b.id'] = $id;
            $this->assign('id', $id);
        }
        $channel = D('channel')->field('id,nick_name')->select();
        //按照渠道筛选
        $number = I('shoukuan');
        if (!empty($number) && $number != 1) {
            $where2['a.number'] = $number;
            $this->assign('number', $number);
        }
        $where['number'] = array('NEQ', '');
        $shoukuannum = M('gathering_information')->distinct('number')->field('name,number')->where($where)->select();


        $where2['a.status'] = 2;
        $count2 = M('closing as a')
                ->join('yy_channel as b on a.channel_id=b.id')
//            ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->where($where2)
                ->count();
        $Pagenum = 20;
        import('Common.Lib.Page');
        $page2 = new \Common\Page($count2, $Pagenum, $_GET);
        $data2 = M('closing as a')
                ->join(' yy_channel as b on a.channel_id=b.id')
//            ->join(' yy_gathering_information as c on c.channel_id=a.channel_id')
                ->join('left join  yy_admin as d on a.operation=d.id')
                ->where($where2)
                ->field('a.add_time,a.sum_pay,a.count_pay,b.nick_name,a.status,a.should_money,a.date,a.id,a.time,a.name,a.bankname,a.number,d.account')
                ->order('a.date DESC ,a.id desc')
                ->limit($page2->firstRow . ',' . $page2->listRows)
                ->select();

        foreach ($data2 as $k => $v) {
            $data2[$k]['new_sum_pay'] = $v['sum_pay'] / 100;
            $data2[$k]['new_should_money'] = $v['should_money'] / 100;
        }

        $sum = M('closing as a')
                ->join('yy_channel as b on a.channel_id=b.id')
                ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->where($where2)
                ->field('sum(sum_pay) as new_sun_money ,sum(should_money) as sum_shold_money, sum(count_pay) as sum_num')
                ->find();
        $this->assign('sum', $sum);

        $show2 = $page2->show();
        $this->assign('page2', $show2);
        $this->assign('channel', $channel);
        $this->assign('shoukuannum', $shoukuannum);
        if (empty($data2)) {
            $this->assign('flag', 0);
        } else {
            $this->assign('flag', 1);
        }
        $this->assign('data2', $data2);
        $this->display();
    }

    //修改收款信息
    public function edit() {
        $get_id = $_GET['id'];
        if (empty($get_id)) {
            $this->redirect('Member/index');
        }
        $red = M('gathering_information')->where(['channel_id' => $get_id])->field('channel_id,name,number,bankname,pay_type')->find();
        $this->assign('red', $red);
        if (IS_POST) {
            $post = I('post.');
            if (!empty($post['bankname'])) {
                $data['pay_type'] = 0;
                $data['name'] = $post['name'];
                $data['number'] = $post['number'];
                $data['bankname'] = $post['bankname'];
                $data['add_time'] = time();
                $where['channel_id'] = $post['channel_id'];
            }
            if (!empty($post['name1'])) {
                $data['pay_type'] = 1;
                $data['name'] = $post['name1'];
                $data['number'] = $post['number1'];
                $data['add_time'] = time();
                $data['bankname'] = null;
                $where['channel_id'] = $post['channel_id'];
            }
            if (!empty($post['name2'])) {
                $data['pay_type'] = 2;
                $data['name'] = $post['name2'];
                $data['number'] = $post['number2'];
                $data['add_time'] = time();
                $data['bankname'] = null;
                $where['channel_id'] = $post['channel_id'];
            }
            if ($res = M('gathering_information')->where(['channel_id' => $post['channel_id']])->save($data)) {
                $this->success('修改成功');
                die;
            } else {
                $this->error('非法参数');
                die;
            }
        }
        $this->display();
    }

    //充值查询
    public function laundry_list() {
        //渠道id
        $channel_id = I('get.channel_id', 0);
        if ($channel_id != 0) {
            $where['c.id'] = $channel_id;
            $this->assign('val', $channel_id);
        }
        //订单号模糊查询
        $trade = I('get.trade_no');
        if (!empty($trade)) {
            $where['t.trade_no'] = strlen($trade) > 10 ? $trade : array('like', '%' . trim($trade) . '%');
        }
        $this->assign('trade_no', $trade);
        $where['t.pay_status'] = array('eq', 1);

        //根据时间区间搜索
        $start_time = urldecode(I('get.start_time'));
        $end_time = urldecode(I('get.end_time'));
        if (!empty($start_time) && !empty($end_time)) {
            $where['t.pay_time'] = array(
                array('egt', strtotime($start_time)),
                array('elt', strtotime($end_time))
            );
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
        }
        if ($where['c.id']) {
            $count = M('trade as t')
                    ->join('left join yy_user as u on t.user_id=u.id')
                    ->join('left join yy_channel as c  on u.channel_id=c.id')
                    ->where($where)
                    ->count(1);
        } else {
            $count = M('trade as t')
                    ->where($where)
                    ->count(1);
        }
        import('Common.Lib.Page');
        //订单数据
        $page = new \Common\Page($count, 20, $_GET);
        $fin = M('trade as t')
                ->join('left join yy_user as u on t.user_id=u.id')
                ->join('left join yy_channel as c  on u.channel_id=c.id')
                ->where($where)
                ->field('t.user_id,t.pay_time as add_time,t.pay,t.pay_status,c.nick_name,t.trade_no,t.from_str')
                ->order('t.pay_time desc')
                ->limit($page->firstRow, $page->listRows)
                ->select();
        $returnList = $this->getPayForm($fin);
        $this->assign('data', $returnList);
        $this->assign('page', $page->show());


        //渠道信息
        $channel = M('channel')->field('id,nick_name')->select();
        $this->assign('channel_id', $channel_id);
        $this->assign('channel', $channel);
        $this->display();
    }
    
    /**
     * 解析充值来源
     * @param type $list
     * @return string
     */
    public function getPayForm($list) {
        $returnData = [];
        foreach ($list as $key => $value) {
            $form = explode(';', $value['from_str']);
            $form_str = explode('.', $form[0]);
            $from_desc = '';
            $push_str = '';
            if (strstr($value['from_str'], 'push')) {
                $push_arr = explode('push', $value['from_str']);
                $push_ex = explode('.', $push_arr[1]);
                $push_id = $push_ex[1];
                $push_str = '推送ID：' . $push_id . '；';
            }

            switch ($form_str[0]) {
                case 'uc':
                    $from_desc = '个人中心';
                    break;
                case 'txt':
                    $getInfo['movies_id'] = $form_str[1];
                    $getInfo['chapter_id'] = $form_str[2];
                    $movies_name = M('movies')->where('id =' . $getInfo['movies_id'])->getField('name');
                    $chapter_name = M('chapter')->where('id =' . $getInfo['chapter_id'])->getField('name');
                    $from_desc = '影片：' . $movies_name . '，章节：' . $chapter_name;
                    break;
                case 'ios':
                    if ($form_str[1] == 'uc') {
                        $from_desc = 'iOS个人中心';
                    } else if ($form_str[1] == 'txt') {
                        $getInfo['movies_id'] = $form_str[2];
                        $getInfo['chapter_id'] = $form_str[3];
                        $movies_name = M('movies')->where('id =' . $getInfo['movies_id'])->getField('name');
                        $chapter_name = M('chapter')->where('id =' . $getInfo['chapter_id'])->getField('name');
                        $from_desc = $push_str . 'iOS（影片：' . $movies_name . '，章节：' . $chapter_name . '）';
                    }
                    break;
                case 'android_uc':
                    $from_desc = '安卓个人中心';
                    break;
                case 'android':
                    if ($form_str[1] == 'txt') {
                        $getInfo['movies_id'] = $form_str[2];
                        $getInfo['chapter_id'] = $form_str[3];
                        $movies_name = M('movies')->where('id =' . $getInfo['movies_id'])->getField('name');
                        $chapter_name = M('chapter')->where('id =' . $getInfo['chapter_id'])->getField('name');
                        $from_desc = $push_str . 'android（影片：' . $movies_name . '，章节：' . $chapter_name . '）';
                    } else {
                        $from_desc = '安卓个人中心';
                    }
                    break;
                case 'banner':
                    $from_desc = 'banner,Id:' . $form_str[1];
                    break;
                case '':
                    $from_desc = '充值来源' + $form_str[0];
                    break;
                default:
                    $from_desc = '未知';
                    break;
            }
            $returnData[$key] = $value;
            $returnData[$key]['from_desc'] = $from_desc;
        }
        return $returnData;
    }

    public function showSum() {
        //渠道id
        $channel_id = I('post.channel_id', 0);
        if ($channel_id != 0) {
            $where['c.id'] = $channel_id;
        }
        //订单号模糊查询
        $trade = I('post.trade_no');
        if (!empty($trade)) {
            $where['t.trade_no'] = array('like', '%' . trim($trade) . '%');
        }
        $where['t.pay_status'] = array('eq', 1);

        //根据时间区间搜索
        $start_time = urldecode(I('post.start_time'));
        $end_time = urldecode(I('post.end_time'));
        if (!empty($start_time) && !empty($end_time)) {
            $where['t.pay_time'] = array(
                array('egt', strtotime($start_time)),
                array('elt', strtotime($end_time))
            );
        }
        //总金额
        $summary = M('trade as t')
                        ->join('left join yy_user as u on t.user_id=u.id')
                        ->join('left join yy_channel as c  on u.channel_id=c.id')
                        ->where($where)->sum('pay');
        $this->ajaxReturn(array('sum' => getReadMoney($summary)));
    }

    //渠道收款
    public function receive() {
        //渠道会员

        $members = M('Member')->select();
        $this->assign('members', $members);

        $nick_name = urldecode(I('get.nick_name'));
        $num = urldecode(I('get.num'));

        $where['number'] = array('NEQ', '');
        $shoukuannum = M('gathering_information')->distinct('number')->field('number,name')->where($where)->select();

        //搜索单条数据
        if (!empty($nick_name)) {
            //多条件查询or
            $map['c.nick_name|c.id'] = array('like', "%{$nick_name}%");

            $this->assign('nick_name', $nick_name);
        }

        if (!empty($num)) {
            $map['g.number'] = $num;
            $this->assign('num', $num);
        }

        $count = M('channel as c')
                ->join('left join yy_gathering_information as g on g.channel_id=c.id')
                ->where($map)
                ->count('1');

        import('Common.Lib.Page');
        $p = new \Common\Page($count, 20);
        $show = $p->show(); // 分页显示输出
        $voList = M('channel as c')
                ->join('left join yy_gathering_information as g on g.channel_id=c.id')
                ->where($map)
                ->limit($p->firstRow, $p->listRows)
                ->field('c.id,c.tel,c.nick_name,g.number,g.name,g.id_card,g.id_card_img')
                ->order('c.add_time desc')
                ->select();
        $this->assign('shoukuannum', $shoukuannum);
        $this->assign('list', $voList);
        $this->assign('page', $show);
        $this->display();
    }

    public function detail() {
        if (!IS_AJAX) {
            exit('非法操作');
        }
        $id = I('get.id');
        $result = M('gathering_information')->where('channel_id=' . $id)->find();
        if ($result) {
            $res['code'] = 0;
            $res['msg'] = 'ok';
            $res['data'] = $result;
        } else {
            $res['code'] = 1;
            $res['msg'] = 'error';
        }
        $this->ajaxReturn($res);
    }

    //导出表单
    public function expanex() {
        $time1 = date("Y-m-d");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $time1 . '.xls"');
        header("Content-Transfer-Encoding:binary");
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");


        $number = I('number');
        if ($number > 0) {
            $where['c.number'] = $number;
        }
        $where['a.channel_id'] = I('get.channel_id');
        if (!$where['a.channel_id'] > 0) {
            unset($where['a.channel_id']);
        }

        $start_time = I('get.start_time');
        $end_time = I('get.end_time');
        $start_time1 = strtotime($start_time);
        $end_time1 = strtotime($end_time);
        if (!empty($start_time) && $start_time1 && $end_time1) {
            $where['a.date'] = array('BETWEEN', array($start_time, $end_time));
            $this->assign('start_time', I('get.start_time'));
            $this->assign('end_time', I('get.end_time'));
        }

        $where['a.sum_pay'] = array('gt', 0);
        $where['a.status'] = I('get.status');

        $expand = M('closing as a')
                ->join('yy_channel as b on a.channel_id=b.id')
                ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->where($where)
                ->field('a.add_time,a.sum_pay,a.count_pay,b.nick_name,a.status,a.should_money,a.date,a.time,a.id')
                ->order('a.date DESC,a.id desc')
                ->select();
        foreach ($expand as $k => $value) {
            if (!empty($value['time'])) {

                $expand[$k]['ktime'] = date('Y-m-d H:i:s', $value['time']);
            } else {
                $expand[$k]['ktime'] = null;
            }
        }

        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);

        $objExcel->getProperties()->setTitle("推广统计");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('A1', $time1 . '财务报表');
        $objExcel->getActiveSheet()->mergeCells('A1:G1');

        $objExcel->getActiveSheet()->setCellValue('A2', 'id');
        $objExcel->getActiveSheet()->setCellValue('B2', '时间');
        $objExcel->getActiveSheet()->setCellValue('C2', '总金额');
        $objExcel->getActiveSheet()->setCellValue('D2', '应转金额');
        $objExcel->getActiveSheet()->setCellValue('E2', '总笔数');
        $objExcel->getActiveSheet()->setCellValue('F2', '渠道');
        $objExcel->getActiveSheet()->setCellValue('G2', '打款时间');


        $count = count($expand);
        for ($i = 3; $i <= $count + 2; $i++) {
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $expand[$i - 3]['id']);
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $expand[$i - 3]['date']);
            $objExcel->getActiveSheet()->setCellValue('C' . $i, $expand[$i - 3]['sum_pay'] / 100);
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $expand[$i - 3]['should_money'] / 100);
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $expand[$i - 3]['count_pay']);
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $expand[$i - 3]['nick_name']);
            $objExcel->getActiveSheet()->setCellValue('G' . $i, $expand[$i - 3]['ktime']);
        }
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objWriter->save('php://output');
    }

    //ajax查询收款信息
    public function ajaxchannel_withdrawal() {
        $data = I('get.');
        if (!IS_AJAX) {
            return false;
        }
        $where['a.id'] = $data['id'];
        if ($data = M('closing as a')
                        ->join('left join yy_channel as c on a.channel_id=c.id ')
                        ->join('left join  yy_gathering_information as b on a.channel_id=b.channel_id')
                        ->where($where)
                        ->field('a.should_money,a.count_pay,a.id,c.nick_name,b.bankname,b.name,b.number,a.date,c.id as channel_id')->find()) {
            $this->ajaxReturn(array('code' => 200, 'data' => $data));
        }
    }

    //Excel导出充值流水
    public function export() {
        $time1 = date("Y-m-d");
        //时间区间
        $where = array();
        $start_time = urldecode(I('get.start_time'));
        $end_time = urldecode(I('get.end_time'));
        if (!empty($start_time) && !empty($end_time)) {
            $diff = strtotime($end_time) - strtotime($start_time);
            if ($diff > 86400) {
                $this->error('导出数据时间请不要超过一天');
            }
            $where['t.pay_time'] = array(
                array('egt', strtotime($start_time)),
                array('elt', strtotime($end_time))
            );
        } else {
            if (isset($where['a.channel_id'])) {
                $threeDays = strtotime(date('Y-m-d', strtotime("-1 day")) . ' 00:00:00');
                $where['t.pay_time'] = array('BETWEEN', array($threeDays, time()));
            } else {
                $threeDays = strtotime(date('Y-m-d', strtotime("-1 day")) . ' 00:00:00');
                $where['t.pay_time'] = array('BETWEEN', array($threeDays, time()));
            }
        }
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $time1 . '.xls"');
        header("Content-Transfer-Encoding:binary");
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");

        //渠道id
        $where['u.channel_id'] = I('get.channel_id');
        if (!$where['u.channel_id'] > 0) {
            unset($where['u.channel_id']);
        }

        //订单号
        $trade = I('trade_no');
        $where['t.trade_no'] = array('like', '%' . trim($trade) . '%');


        $data = M('trade as t')
                ->join('left join yy_user as u on t.user_id=u.id')
                ->join('left join yy_channel as c  on u.channel_id=c.id')
                ->where($where)
                ->field('t.user_id,t.pay_time,t.pay,t.pay_status,c.nick_name,t.trade_no ')
                ->order('t.pay_time desc')
                ->select();
        foreach ($data as $k => $v) {
            $data[$k]['user_name'] = M('user_info')->where('user_id=' . $v['user_id'])->getField('nick_name');
        }
        $phpExcel = new \PHPExcel();
        $writer = new \PHPExcel_Writer_Excel5($phpExcel);

        $phpExcel->getProperties()->setTitle("推广统计");
        $phpExcel->getProperties()->setSubject("报表");
        $phpExcel->setActiveSheetIndex(0);
        $phpExcel->getActiveSheet()->setCellValue('A1', $time1 . '订单数据报表');
        $phpExcel->getActiveSheet()->mergeCells('A1:G1');


        $phpExcel->getActiveSheet()->setCellValue('A2', '充值订单号');
        $phpExcel->getActiveSheet()->setCellValue('B2', '用户ID');
        //$phpExcel->getActiveSheet()->setCellValue('C2','用户名称');
        $phpExcel->getActiveSheet()->setCellValue('D2', '充值金额');
        $phpExcel->getActiveSheet()->setCellValue('E2', '渠道');
        $phpExcel->getActiveSheet()->setCellValue('F2', '状态');
        $phpExcel->getActiveSheet()->setCellValue('G2', '充值时间');
        foreach ($data as $key => $value) {
            $key += 3;
            $phpExcel->getActiveSheet()->setCellValue('A' . $key, $value['trade_no']);
            $phpExcel->getActiveSheet()->setCellValue('B' . $key, $value['user_id']);
            //$phpExcel->getActiveSheet()->setCellValue('C'.$key,$value['user_name']);
            $phpExcel->getActiveSheet()->setCellValue('D' . $key, getReadMoney($value['pay']));
            $phpExcel->getActiveSheet()->setCellValue('E' . $key, $value['nick_name']);
            $phpExcel->getActiveSheet()->setCellValue('F' . $key, ($value['pay_status'] == 1) ? '充值成功' : '充值失败');
            $phpExcel->getActiveSheet()->setCellValue('G' . $key, date('Y-m-d H:i:s', $value['pay_time']));
//            $phpExcel->getActiveSheet()->setCellValue('H'.$key,$key);
        }

        $phpExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $writer->save('php://output');

//        $phpExcel->createSheet();//创建表格节点
//        $writer=\PHPExcel_IOFactory::createWriter($phpExcel,'Excel2007');
//        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
//        header('Content-Disposition: attachment;filename='.date('Y-m-d').'.xls');
//        header('Cache-Control: max-age=0');
//        $writer->save('php://output');
        // $this->display();
    }

    public function downSelect() {
        set_time_limit(0);
        $getForm = I('get.');
        $moneyNumber = $getForm['number']; //账户 
        $start_time = $getForm['start_time']; //账户 
        $end_time = $getForm['end_time']; //账户 
        $type = $getForm['type']; //1 对公 2： 对私 

        if ($getForm['id'] == '') {
            $this->error('必须选择收款人');
        } else if (empty($start_time) || empty($end_time)) {
            $this->error('必须选择时间');
        }
        $whereinfo['id'] = $getForm['id'];
        $shoukuannum = M('gathering_information')->field('bankname,name,number')->where($whereinfo)->find();
        if ($moneyNumber > 0) {
            $where['c.number'] = $moneyNumber;
        }
        $where['a.channel_id'] = I('get.channel_id');
        if (!$where['a.channel_id'] > 0) {
            unset($where['a.channel_id']);
        }

        $start_time1 = strtotime($start_time);
        $end_time1 = strtotime($end_time);
        if (!empty($start_time) && $start_time1 && $end_time1) {
            $where['a.date'] = array('BETWEEN', array($start_time, $end_time));
        }
        $where['a.sum_pay'] = array('gt', 0);
        $where['a.status'] = I('get.status');

        $expand = M('closing as a')
                ->join('yy_channel as b on a.channel_id=b.id')
                ->join('yy_gathering_information as c on c.channel_id=a.channel_id')
                ->where($where)
                ->field('a.add_time,a.sum_pay,a.count_pay,b.nick_name,a.status,a.should_money,a.date,a.time,a.id')
                ->order('a.date DESC,a.id desc')
                ->select();
        if ($type == 1) {
            $this->downExcelMoenyc($shoukuannum, $expand, $start_time, $end_time);
        } else {
            $this->downExcelMoenye($shoukuannum, $expand, $start_time, $end_time);
        }
    }

    public function downExcelMoenyc($userInfo, $list, $start_time, $end_time) {
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $demoExcel = './Public/excelmode/demox.xlsx';
        $objExcel = \PHPExcel_IOFactory::createReader("Excel2007")->load($demoExcel);
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('E3', $userInfo['name']); //公司名称
        $date = date('Y.m.d', strtotime($start_time)) . '-' . date('Y.m.d', strtotime($end_time));
        $objExcel->getActiveSheet()->setCellValue('D4', $date); //日期
        $user_info = '户名：' . $userInfo['name'] . "\n";
        $user_info .= '开户行：' . $userInfo['bankname'] . "\n";
        $user_info .= '账号：' . $userInfo['number'];
        $objExcel->getActiveSheet()->setCellValue('B5', $user_info); //乙方账户信息
        $objExcel->getActiveSheet()->setCellValue('E10', $userInfo['name']); //公司名称
        
        $YI_info1 = "甲方：深圳有影传媒有限公司 \n";
        $YI_info1 .= '日期：' . date('Y年m月d日');
        $objExcel->getActiveSheet()->setCellValue('A20', $YI_info1); //公司名称
        
        $YI_info = '乙方：' . $userInfo['name'] . "\n";
        $YI_info .= '日期：' . date('Y年m月d日');
        $objExcel->getActiveSheet()->setCellValue('D20', $YI_info); //公司名称
        //对账单
        $objExcel->setActiveSheetIndex(1);
        $count = count($list);
        $objExcel->getActiveSheet(1)->getStyle('A1:I1200')->getAlignment()->setWrapText(true);
        $countPay = 0;
        $countshould_money = 0;
        $count_pay = 0;
        $jsTime = '结算周期：' . date('Y年m月d日', strtotime($start_time)) . '至' . date('Y年m月d日', strtotime($end_time));
        $objExcel->getActiveSheet()->setCellValue('A3', $jsTime); //id
        for ($i = 6; $i <= (int) $count + 5; $i++) {
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $list[$i - 6]['id']); //id
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $list[$i - 6]['date']); //时间
            $countPay += getReadMoney($list[$i - 6]['sum_pay']);
            $countshould_money += getReadMoney($list[$i - 6]['should_money']);
            $objExcel->getActiveSheet()->setCellValue('C' . $i, getReadMoney($list[$i - 6]['sum_pay'])); //总金额
            $objExcel->getActiveSheet()->setCellValue('D' . $i, getReadMoney($list[$i - 6]['should_money'])); //应转金额
            $count_pay += $list[$i - 6]['count_pay'];
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $list[$i - 6]['count_pay']); //总笔数
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $list[$i - 6]['nick_name']); //渠道
        }

        $i++;
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '总计'); //id
        $objExcel->getActiveSheet()->setCellValue('B' . $i, ''); //时间
        $objExcel->getActiveSheet()->setCellValue('C' . $i, $countPay); //总金额
        $objExcel->getActiveSheet()->setCellValue('D' . $i, $countshould_money); //应转金额
        $objExcel->getActiveSheet()->setCellValue('E' . $i, $count_pay); //总笔数
        $objExcel->getActiveSheet()->setCellValue('F' . $i, ''); //渠道

        $i++;
        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':F' . $i);

        $i++;
        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':F' . $i);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '*********************************************************************');

        $i++;
        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':F' . $i);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '以上金额确认无误，请盖章后和发票一同寄回');

        $i++;

        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':C' . $i);
        $objExcel->getActiveSheet()->mergeCells('D' . $i . ':F' . $i);

        $objExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(200);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, "联系 人 ".$userInfo['name']." \n联系电话： \n邮寄地址：\n收款银行：".$userInfo['bankname']." \n银行账号：".$userInfo['number']);
        $objExcel->getActiveSheet()->setCellValue('D' . $i, "联系 人 ：王梦琳 \n联系电话：18503005251 \n邮寄地址：深圳市南山区沙河街道侨香路侨城坊五号楼（力高大厦）7楼 \n发票抬头：深圳有影传媒有限公司 \n发票类型：增值税专用发票 \n发票内容：信息服务费 ");
        
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $objExcel->getActiveSheet()->getStyle( 'A1:F'.$i)->applyFromArray($styleThinBlackBorderOutline);
        
        $objExcel->setActiveSheetIndex(0);
        $numberH = $this->num_to_rmb($countshould_money);
        
        $showNumber = $countshould_money.'元（'.$numberH.'）整';
        $objExcel->getActiveSheet()->setCellValue('B12', $showNumber); //公司名称



        $savefile = "有影结算单" . time() . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefile . '"');
        header('Cache-Control: max-age=0');
        // 用户下载excel
        $objWriter->save('php://output');
    }

    public function downExcelMoenye($userInfo, $list,$start_time, $end_time) {
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $demoExcel = './Public/excelmode/demos.xlsx';
        $objExcel = \PHPExcel_IOFactory::createReader("Excel2007")->load($demoExcel);
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('E3', $userInfo['name']); //公司名称
        $date = date('Y.m.d', strtotime($start_time)) . '-' . date('Y.m.d', strtotime($end_time));
        $objExcel->getActiveSheet()->setCellValue('D4', $date); //日期
        $user_info = '户名：' . $userInfo['name'] . "\n";
        $user_info .= '开户行：' . $userInfo['bankname'] . "\n";
        $user_info .= '账号：' . $userInfo['number'];
        $objExcel->getActiveSheet()->setCellValue('B5', $user_info); //乙方账户信息
        
        
        $YI_info1 = "甲方：深圳有影传媒有限公司 \n";
        $YI_info1 .= '日期：' . date('Y年m月d日');
        $objExcel->getActiveSheet()->setCellValue('A10', $YI_info1); //公司名称
        
        $YI_info = '乙方：' . $userInfo['name'] . "\n";
        $YI_info .= '日期：' . date('Y年m月d日');
        $objExcel->getActiveSheet()->setCellValue('D10', $YI_info); //公司名称
        //对账单
        $objExcel->setActiveSheetIndex(1);
        $count = count($list);
        $objExcel->getActiveSheet(1)->getStyle('A1:I1200')->getAlignment()->setWrapText(true);
        $countPay = 0;
        $countshould_money = 0;
        $count_pay = 0;
        $jsTime = '结算周期：' . date('Y年m月d日', strtotime($start_time)) . '至' . date('Y年m月d日', strtotime($end_time));
        $objExcel->getActiveSheet()->setCellValue('A3', $jsTime); //id
        for ($i = 6; $i <= (int) $count + 5; $i++) {
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $list[$i - 6]['id']); //id
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $list[$i - 6]['date']); //时间
            $countPay += getReadMoney($list[$i - 6]['sum_pay']);
            $countshould_money += getReadMoney($list[$i - 6]['should_money']);
            $objExcel->getActiveSheet()->setCellValue('C' . $i, getReadMoney($list[$i - 6]['sum_pay'])); //总金额
            $objExcel->getActiveSheet()->setCellValue('D' . $i, getReadMoney($list[$i - 6]['should_money'])); //应转金额
            $count_pay += $list[$i - 6]['count_pay'];
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $list[$i - 6]['count_pay']); //总笔数
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $list[$i - 6]['nick_name']); //渠道
        }

        $i++;
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '和计'); //id
        $objExcel->getActiveSheet()->setCellValue('B' . $i, ''); //时间
        $objExcel->getActiveSheet()->setCellValue('C' . $i, $countPay); //总金额
        $objExcel->getActiveSheet()->setCellValue('D' . $i, $countshould_money); //应转金额
        $objExcel->getActiveSheet()->setCellValue('E' . $i, $count_pay); //总笔数
        $objExcel->getActiveSheet()->setCellValue('F' . $i, ''); //渠道

        $i++;
        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':C' . $i);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '扣除税点[4个点]后实际结算金额'); //id
        $objExcel->getActiveSheet()->setCellValue('D' . $i, '*0.96'); //id
        $i++;
        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':F' . $i);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '*********************************************************************');

        $i++;
        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':F' . $i);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, '以上金额确认无误，请盖章后和发票一同寄回');

        $i++;

        $objExcel->getActiveSheet()->mergeCells('A' . $i . ':C' . $i);
        $objExcel->getActiveSheet()->mergeCells('D' . $i . ':F' . $i);

        $objExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(120);
        $objExcel->getActiveSheet()->setCellValue('A' . $i, "联系 人 ： \n联系电话： \n邮寄地址：\n收款银行： \n银行账号：");
        $objExcel->getActiveSheet()->setCellValue('D' . $i, "联系 人 ：吴沛 \n联系电话：13823130979 \n邮寄地址：深圳市南山区沙河街道侨香路侨城坊五号楼（力高大厦）7楼 \n发票抬头：深圳有影传媒有限公司 \n发票类型：增值税专用发票 \n发票内容：信息服务费 ");
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $objExcel->getActiveSheet()->getStyle( 'A1:F'.$i)->applyFromArray($styleThinBlackBorderOutline);
        $objExcel->setActiveSheetIndex(0);
        $numberH = $this->num_to_rmb($countshould_money);
        
        $showNumber = $countshould_money.'元（'.$numberH.'）整';
        $objExcel->getActiveSheet()->setCellValue('B9', $showNumber); //公司名称



        $savefile = "有影结算单" . time() . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefile . '"');
        header('Cache-Control: max-age=0');
        // 用户下载excel
        $objWriter->save('php://output');
    }

    // 阿拉伯数字转中文大写金额

    /**
     * 数字金额转换成中文大写金额的函数
     * String Int $num 要转换的小写数字或小写字符串
     * return 大写字母
     * 小数位为两位
     * */
    public function num_to_rmb($num) {
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $ARRW = array(
            '佰',
            '拾',
            '万',
            '仟',
            '佰',
            '拾',
            '元'
        );
        
        $c2 = "分角元拾佰仟万拾佰仟亿";
        //精确到分后面就不要了，所以只留两个小数位
        $num = round($num, 2);
        //将数字转化为整数
        $nums = $num * 100;
        if (strlen($nums) > 10) {
            return "金额太大，请检查";
        }
        $expand = explode( '.',$num);
        if($expand[0]){
            $splitZ = str_split($expand[0]);
            $array_reverse = array_reverse($splitZ);
            $znum  = [];
            foreach(array_reverse($ARRW) as $k => $val){
                if($array_reverse[$k]){
                    $znum[] = $this->number2chinese((int)$array_reverse[$k]).$val;
                }else{
                    $znum[] = $this->number2chinese(0).$val;
                }
            }
            $honum = '';
            foreach(array_reverse($znum) as $number ){
                $honum .= $number;
            }
        }
        if($expand[1]){
             $ARR = array(
                '角',
                '分'
            );
            $jfstr = '';
            $split0 =  str_split($expand[1]);
            foreach($ARR as $k => $j){
                if(!empty($split0[$k])){
                    $jfstr .= $this->number2chinese((int)$split0[$k]).$j;
                }else{
                    $jfstr .= $this->number2chinese(0).$j;
                }
            }
        }
        return $honum.$jfstr;
    }
    
    /**
 * 数字转换为中文
 * @param  integer  $num  目标数字
 */
public function number2chinese($num)
{
    if (is_int($num) && $num < 100) {
        $char = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        $unit = ['', '十', '百', '千', '万'];
        $return = '';
        if ($num < 10) {
            $return = $char[$num];
        } elseif ($num%10 == 0) {
            $firstNum = substr($num, 0, 1);
            if ($num != 10) $return .= $char[$firstNum];
            $return .= $unit[strlen($num) - 1];
        } elseif ($num < 20) {
            $return = $unit[substr($num, 0, -1)]. $char[substr($num, -1)];
        } else {
            $numData = str_split($num);
            $numLength = count($numData) - 1;
            foreach ($numData as $k => $v) {
                if ($k == $numLength) continue;
                $return .= $char[$v];
                if ($v != 0) $return .= $unit[$numLength - $k];
            }
            $return .= $char[substr($num, -1)];
        }
        return $return;
    }
}

}

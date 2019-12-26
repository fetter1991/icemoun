<?php

/**
 * 有影每月数据统计
 * 
 *  
 * @author      tsj 作者
 * @version     1.0 版本号
 */

namespace Back\Controller;

class RiseFinsController extends CommonController {

    public function index() {
        $channellist = M('channel')->select(); //查询渠道列表
        $this->assign('channellist', $channellist);
        if (!$_POST) { //如果不是提交
            $this->display();
        } else {
            $form = I('post.');
            $channel = $form['channellist'];
            $date = $form['datetime'];
            $where['channel_id'] = $channel;
            $where['day_time'] = array('like', $date . "%");
            $data = M('baobiao_yunying')->where($where)->field('add_time')->find(); //查询报表是否存在
            if (count($data) >= 1) {
                $this->redirect('RiseFins/tableIndex', array('day_time' => $date, 'channel_id' => $channel));
            } else {
                $days = $this->getMonthDays($date);
                foreach ($days as &$value) {
                    $value['content'] = '{"follow_num":"","unfollow_num":"","follow_total":"","innerexpand_ids":"","pay_sum":"","date":"' . $value['year'] . '"}';
                }
                $this->assign("datetime", $date);
                $this->assign("channeId", $channel);
                $this->assign("list", $days);
                $this->display();
            }
        }
    }

    /**
     * 获取指定月份的所有日期
     * @param type $month 年月
     * @param type $format 格式
     * @return type
     */
    public function getMonthDays($month = "this month", $format = "Y-m-d", $type = 1) {
        $start = strtotime("first day of $month");
        $end = strtotime("last day of $month");
        $days = array();
        $monh = $type == 1 ? "m-d" : "Y-m-d";
        $j = 0;
        for ($i = $start; $i <= $end; $i += 24 * 3600) {
            $days[$j]['moth'] = date($monh, $i);
            $days[$j]['year'] = date($format, $i);
            $j++;
        }
        return $days;
    }

    /**
     * 修改展示页面
     */
    public function addIndex() {
        $from = I('get.');
        $this->assign('id', $from['id']);
        $this->assign('year', $from['year']);
        $this->assign('channle_id', $from['channle_id']);
        $this->assign('data_time', $from['datetime']);
        $this->assign('day_pay', $from['day_pay']);
        if ($from['follow_num'] != "") {
            $this->assign('follow_num', $from['follow_num']);
        }
        if ($from['unfollow_num'] != "") {
            $this->assign('unfollow_num', $from['unfollow_num']);
        }
        if ($from['follow_total'] != "") {
            $this->assign('follow_total', $from['follow_total']);
        }
        if ($from['innerexpand_ids'] != "") {
            $this->assign('innerexpand_ids', $from['innerexpand_ids']);
        }
        $this->display();
    }

    /**
     * ajax获取内推数据
     */
    public function getInnerexpandId() {
        $from = I('post.');
        $id = $from['innerexpand_ids'];
        $dataArr['follow_num'] = $from['follow_num'];
        $dataArr['unfollow_num'] = $from['unfollow_num'];
        $dataArr['follow_total'] = $from['follow_total'];
        $dataArr['channel_id'] = $from['channle_id'];
        $dataArr['innerexpand_ids'] = $id;
        $dataArr['day_time'] = $from['datetime'];
        $dataArr['remarks'] = $from['remarks'];
        //当日充值
        $traWhere['a.innerexpand_id'] = array('GT', 0);
        $traWhere['a.pay_status'] = 1;
        $inmonth = $from['datetime'];
        $startime = strtotime(date("Y-m-d 0:0:0", strtotime($inmonth)));
        $lasttime = strtotime(date("Y-m-d 0:0:0", strtotime("$inmonth +1 day")));
        $traWhere['a.pay_time'] = array(array('EGT', $startime), array('ELT', $lasttime), 'and');
        $traWhere['b.channel_id'] = $from['channle_id'];
        $paySum = M('trade as a')->join('LEFT join yy_user as b  on a.user_id=b.id')->
                        where($traWhere)->field('sum(pay) as pay')->find();
        $dataArr['pay_sum'] = !empty($paySum['pay']) ? $paySum['pay'] : "0";
        //查询字段是否存在
        $whereArr['day_time'] = $from['datetime'];
        $whereArr['channel_id'] = $from['channle_id'];
        $count = M('baobiao_yunying')->where($whereArr)->count('1');
        $newId = "";
        if ($count >= 1) {
            $chanwhere['id'] = $from['id'];
            $this->change($dataArr, $chanwhere);
        } else {
            $newId = $this->addData($dataArr);
        }
        //查询出内推数据
        $arrexplo = explode(',', $id);
        if (count($arrexplo) > 1) {
            $where['id'] = array('in', $id);
        } else {
            $where['id'] = $id;
        }
        $data = M('innerexpand')->where($where)->field('movies_id,click_num,gold_num,nick_name,remark,id')->order('add_time desc')->select();
        $newData = array();
        if (count($data) >= 1) {
            foreach ($data as $k => $v) {
                $pay_sum = M('trade')->where('innerexpand_id=' . $v['id'] . ' and pay_status = 1')->sum('pay');
                $data[$k]['pay_sum'] = !empty($pay_sum) ? $pay_sum : "0";
                $proportion = round($v['gold_num'] / $v['click_num'], 1);
                $data[$k]['payProportion'] = !is_nan($proportion) ? $proportion : 0;
                if (!empty($v['movies_id'])) {
                    $moviesName = M('movies')->where('id=' . $v['movies_id'])->field('name,org_name')->find();
                    $data[$k]['movies_name'] = $data[$k]['movies_name'] = !empty($moviesName['org_name']) ? $moviesName['name'] . "<br />[原名:" . $moviesName['org_name'] . "]" : $moviesName['name'];
                } else {
                    $data[$k]['movies_name'] = "";
                }
            }

            foreach ($arrexplo as $key => $value) {
                foreach ($data as $val) {
                    if ($value == $val['id']) {
                        $newData[] = $val;
                    }
                }
            }
        }
        $newData['newId'] = $newId;
        $newData['pay_sum'] = !empty($paySum['pay']) ? $paySum['pay'] : "0";
        $this->ajaxReturn($newData);
    }

    /**
     * 添加月份数据  
     * 注：已废弃
     */
    public function add() {
        $from = I('post.');
        $contarr = $from['content'];
        $datetime = $from['datetime'];
        $channleID = $from['channel_id'];
        $modelRes = M('baobiao_yunying');
        $modelRes->startTrans();
        foreach ($contarr as $key => $value) {
            if ($value != "") {
                $jsonstr = json_decode(htmlspecialchars_decode($value), true);
                $data = $jsonstr;
                $data['day_time'] = $datetime[$key];
                $data['add_time'] = time();
                $data['channel_id'] = $channleID;
                $model = $modelRes->add($data);
                if (!$model) {
                    $modelRes->rollback();
                    $this->error('插入失败');
                }
            } else {
                $datat['day_time'] = $datetime[$key];
                $datat['channel_id'] = $channleID;
                $datat['add_time'] = time();
                $model = $modelRes->add($datat);
                if (!$model) {
                    $modelRes->rollback();
                    $this->error('插入失败');
                }
            }
        }
        $modelRes->commit();
        $this->success("插入成功");
    }

    /**
     * 报表列表
     */
    public function select() {
       
        $channellist = M('channel')->where('is_youying = 1')->select();
        $channellistNto = M('channel')->select();
        $this->assign('channellist', $channellist);
        $this->assign('channellistnot', $channellistNto);
        $from = I('post.');
        $dateTime = [];
        foreach ($channellist as $key => $value) {
            $dateTime[$key]['day_time'] = date("Y-m");
            $dateTime[$key]['nick_name'] = $value['nick_name'];
            $dateTime[$key]['channel_id'] = $value['id'];
        }
        $datetime = $from['datetime'];
        $channelId = $from['channellist'];
        if (!empty($channelId) && !empty($datetime)) {
            $this->redirect('RiseFins/tableIndex', array('day_time' => $datetime, 'channel_id' => $channelId));
        }
        $this->assign('list', $dateTime);
        $this->display();
    }

    /**
     * 报表详情
     */
    public function tableIndex() {
        $get = I('get.');
        $addTime = $get['day_time'];
        $channleID = $get['channel_id'];
        if (empty($addTime) || empty($channleID)) {
            $this->error('非法访问');
        } else {
            $where = array();
            $where['day_time'] = array("like", $addTime . "%");
            $where['channel_id'] = $channleID;
            $model = M('baobiao_yunying')->where($where)->select(); //查询出当月报表所有列
            foreach ($model as &$val) {
                if (!empty($val['innerexpand_ids'])) { //如果内推id不为空，则查询出内推信息
                    $arrexplo = explode(',', $val['innerexpand_ids']);
                    if (count($arrexplo) > 1) {
                        $whereIn['id'] = array('in', $val['innerexpand_ids']);
                    } else {
                        $whereIn['id'] = $val['innerexpand_ids'];
                    }
                    //查询出内推数据
                    $data = M('innerexpand')->where($whereIn)->field('movies_id,click_num,gold_num,nick_name,remark,id')->order('add_time desc')->select();
                    foreach ($data as $k => $v) {
                        $pay_sum = M('trade')->where('innerexpand_id=' . $v['id'] . ' and pay_status = 1')->sum('pay'); //总充值
                        $data[$k]['pay_sum'] = !empty($pay_sum) ? $pay_sum : "0";
                        $proportion = round($v['gold_num'] / $v['click_num'], 1); //金币消费指数
                        $data[$k]['payProportion'] = !is_nan($proportion) ? $proportion : 0;
                        //查询出电影名称
                        if (!empty($v['movies_id'])) {
                            $moviesName = M('movies')->where('id=' . $v['movies_id'])->field('name,org_name')->find();
                            $data[$k]['movies_name'] = $data[$k]['movies_name'] = !empty($moviesName['org_name']) ? $moviesName['name'] . "<br />[原名:" . $moviesName['org_name'] . "]" : $moviesName['name'];
                        } else {
                            $data[$k]['movies_name'] = "";
                        }
                    }
                    $newData = [];
                    foreach ($arrexplo as $key => $value) {
                        foreach ($data as $vals) {
                            if ($value == $vals['id']) {
                                $newData[] = $vals;
                            }
                        }
                    }
                    $val['innerexpand'] = $newData;
                } else {
                    $val['innerexpand'] = "";
                }
                $jsonArr = array();
                $jsonArr['follow_num'] = $val['follow_num']; //新关注
                $jsonArr['unfollow_num'] = $val['unfollow_num']; //取消关注
                $jsonArr['follow_total'] = $val['follow_total']; //累计关注
                $jsonArr['innerexpand_ids'] = $val['innerexpand_ids']; //内推id
                $jsonArr['date'] = $val['day_time']; //内推id
                $jsonArr['pay_sum'] = $val['pay_sum']; //总充值
                $val['json'] = json_encode($jsonArr);
                $val['paydayNum'] = !is_nan(($val['pay_sum'] / 100) / $val['follow_total']) && ($val['follow_total'] != 0) ? round(($val['pay_sum'] / 100) / $val['follow_total'], 2) : "-";
                $val['pay_sum'] = $val['pay_sum'] / 100;
            }
            //获得中文的年月数据
            if (!empty($model)) {
                $days = $this->getMonthDays($model[0]['day_time'], "Y-m-d", 2);
                $datemonth = date('Y年m月', strtotime($days[0]['moth']));
            } else {
                $days = $this->getMonthDays($addTime, "Y-m-d", 2);
                $datemonth = date('Y年m月', strtotime($addTime));
            }
            //排列在日期的数组中
            $content = array();
            foreach ($days as $key => $value) {
                $json = '{"follow_num":"","unfollow_num":"","follow_total":"","innerexpand_ids":"","pay_sum":"","date":"' . $value['year'] . '"}';
                if (!empty($model)) {
                    foreach ($model as &$coutent) {
                        if ($value['year'] == $coutent['day_time']) {
                            $content[$key] = $coutent;
                            break;
                        } else {
                            $content[$key] = [
                                'day_time' => $value['year'],
                                'follow_num' => "",
                                'pay_sum' => "",
                                'unfollow_num' => "",
                                'follow_total' => "",
                                'paydayNum' => "",
                                'json' => $json,
                                'id' => "Id" . $key
                            ];
                        }
                    }
                } else {
                    $content[$key] = [
                        'day_time' => $value['year'],
                        'pay_sum' => "",
                        'follow_num' => "",
                        'unfollow_num' => "",
                        'follow_total' => "",
                        'paydayNum' => "",
                        'json' => $json,
                        'id' => "Id" . $key
                    ];
                }
            }
            $channleName = M('channel')->where('id=' . $channleID)->field('nick_name')->find();
            $this->assign('month', $datemonth);
            $this->assign('channeName', $channleName['nick_name']);
            $this->assign('channeId', $channleID);
            $this->assign('list', $content);
            $this->display();
        }
    }

    /**
     * 增加
     * @param array $data
     * @return boolean
     */
    public function addData($data) {
        $modelRes = M('baobiao_yunying');
        $data['add_time'] = time();
        $model = $modelRes->add($data);
        if (!$model) {
            return false;
        } else {
            return $model;
        }
    }

    /**
     * 修改
     */
    public function change($data, $chanwhere) {
        $modelRes = M('baobiao_yunying');
        $model = $modelRes->where($chanwhere)->save($data);
        if (!$model) {
            return false;
        } else {
            return true;
        }
    }

    public function selectInner() {
        $name = I('name');
        $channel_id = I('channel_id');
        $innerexpand_ids = I('data');
        $innerId = explode(',', $innerexpand_ids);
        $isEmpty = !empty($innerId) ?  $innerId : '';
        $where['channel_id']= $channel_id;
        if(!empty($name)){
            $where['nick_name']=array('like','%'.$name.'%');
        }
        $count=M('innerexpand')->where($where)->count(1);
        import('Common.Lib.Page');
        if (!empty($isEmpty)) {
            $this->assign('data',$innerexpand_ids);
            $wheres['id'] = array('in', $isEmpty);
            $moviesin = M('innerexpand')->where($wheres)->field('id,nick_name,remark')->order('add_time desc')->select();
            $this->assign('listin', $moviesin);
            $where['id'] = array('not in', $isEmpty);
        }
        $p=new \Common\Page($count,20);
        $data=M('innerexpand')->where($where)->field('id,nick_name,remark')->order('add_time desc')->limit($p->firstRow,$p->listRows)->select();
        $this->assign('list',$data);
        $this->assign('page',$p->show());
        $this->assign('channel_id',$channel_id);
        $this->assign('data',$innerexpand_ids);
        $this->assign('innerId',$isEmpty);
        $this->display('selectUrl');
    }
    
    
    /**
     * 导出excel
     */
    public function saveExcel() {
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $demoExcel = './Public/excelmode/demo.xlsx';
        $objExcel = \PHPExcel_IOFactory::createReader("Excel2007")->load($demoExcel);
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        $objExcel->getProperties()->setTitle("推广统计");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $content = I('post.');
        //查询出渠道名称
        $channelWhere['id'] = $content['channel_id'];
        $channelArr = M('channel')->where($channelWhere)->field('nick_name')->find();
        $nick_name = !empty($channelArr['nick_name']) ? $channelArr['nick_name'] : "无";
        $objExcel->getActiveSheet()->setCellValue('I1', $nick_name); //渠道名称
        $contentArr = $content['content'];
        $col = 5;
        foreach ($contentArr as $value) {
            $jsonArr = json_decode(htmlspecialchars_decode($value), true);
            $date_time = !empty($jsonArr['date']) ? date("n月j日", strtotime($jsonArr['date'])) : '';
            $follow_num = !empty($jsonArr['follow_num']) ? $jsonArr['follow_num'] : 0; //新关注
            
            $unfollow_num = !empty($jsonArr['unfollow_num']) ? $jsonArr['unfollow_num'] : 0; //取关
            $jinzeng = $follow_num - $unfollow_num; //净增
            $follow_total = !empty($jsonArr['follow_total']) ? $jsonArr['follow_total'] : "0"; //总关注
            $innerexpand_ids = !empty($jsonArr['innerexpand_ids']) ? $jsonArr['innerexpand_ids'] : ""; //内推id
            $pay_sum = !empty($jsonArr['pay_sum']) ? getReadMoney($jsonArr['pay_sum']) : "0"; //日充值
            $day_quguancXinz = !is_nan($unfollow_num / $follow_num) ? round($unfollow_num / $follow_num, 2) : "-"; //每日取关除以每日新增
            $day_quguanclei = !is_nan($unfollow_num / $follow_total) ? round($unfollow_num / $follow_total, 2) : "-"; //每日取关除以累计人数
            $danfenrc = !is_nan($pay_sum / $follow_total) && ($follow_total != 0) ? round($pay_sum / $follow_total, 2) : "-"; //单粉日产出
            $innerWhere['id'] = !empty($innerexpand_ids) ? array('in', $innerexpand_ids) : ""; //内推查询条件
            $innerexpandArr = M('innerexpand')->where($innerWhere)->field('movies_id,click_num,gold_num,nick_name,remark,id')->order('add_time desc')->select();
            $innerCount = count($innerexpandArr); //内推的条数
            $objExcel->getActiveSheet()->setCellValue('A' . $col, $date_time); //日期
            $objExcel->getActiveSheet()->setCellValue('B' . $col, $follow_num); //新关注
            $objExcel->getActiveSheet()->setCellValue('D' . $col, $jinzeng); //净增
            $objExcel->getActiveSheet()->setCellValue('C' . $col, $unfollow_num); //取关
            $objExcel->getActiveSheet()->setCellValue('E' . $col, $follow_total); //总关注
            $objExcel->getActiveSheet()->setCellValue('F' . $col, $day_quguancXinz); //每日取关除以每日新增
            $objExcel->getActiveSheet()->setCellValue('G' . $col, $day_quguanclei); //每日取关除以累计人数
            $objExcel->getActiveSheet()->setCellValue('H' . $col, $pay_sum); //日充值
            $objExcel->getActiveSheet()->setCellValue('I' . $col, $danfenrc); //单粉日产出
            if ($innerCount == 0) {
                $col++;
                continue;
            } else if ($innerCount > 1) {
                //合并单元格
                $nextCol = $col + ($innerCount - 1);
                $objExcel->getActiveSheet()->mergeCells('A' . $col . ':A' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('B' . $col . ':B' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('C' . $col . ':C' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('D' . $col . ':D' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('E' . $col . ':E' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('F' . $col . ':F' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('G' . $col . ':G' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('H' . $col . ':H' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('I' . $col . ':I' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('Q' . $col . ':Q' . $nextCol);
            }
            $exInnerId = explode(',', $innerexpand_ids);
            $newData = [];
            foreach ($exInnerId as $vals) {
                foreach ($innerexpandArr as $val) {
                    if ($vals == $val['id']) {
                        $newData[] = $val;
                    }
                }
            }
            foreach ($newData as &$v) {
                $pay_sum = M('trade')->where('innerexpand_id=' . $v['id'] . ' and pay_status = 1')->sum('pay');
                $pay_sum_now = !empty($pay_sum) ? $pay_sum : "0"; //总充值
                $proportion = round($v['gold_num'] / $v['click_num'], 1);
                $gold = !is_nan($proportion) ? $proportion : 0; //金币消费指数
                $movies_name = "";
                if (!empty($v['movies_id'])) {
                    $moviesName = M('movies')->where('id=' . $v['movies_id'])->field('name,org_name')->find();
                    $movies_name = $data[$k]['movies_name'] = !empty($moviesName['org_name']) ? $moviesName['name'] . " [原名:" . $moviesName['org_name'] . "]" : $moviesName['name'];
                } else {
                    $movies_name = "";
                }
                $objExcel->getActiveSheet()->setCellValue('K' . $col, $v['click_num']); //观看人数
                $objExcel->getActiveSheet()->setCellValue('L' . $col, getReadMoney($pay_sum_now)); //总充值
                $objExcel->getActiveSheet()->setCellValue('M' . $col, $gold); //金币消费指数
                $overNumberPeo = !is_nan($v['pay_sum'] / $v['click_num']) ? round(getReadMoney($pay_sum_now) / $v['click_num'], 2) : "-";  //总充值/观看人数
                $objExcel->getActiveSheet()->setCellValue('N' . $col, $overNumberPeo); //总充值/观看人数
                $objExcel->getActiveSheet()->setCellValue('O' . $col, $movies_name); //电影名称
                $objExcel->getActiveSheet()->setCellValue('P' . $col, $v['remark']); //标题
                $col++;
            }
        }

        $savefile = "有影涨粉表_" . time() . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefile . '"');
        header('Cache-Control: max-age=0');
        // 用户下载excel
        $objWriter->save('php://output');
    }

    /**
     * 批量导出页面
     */
    public function batchSave() {
        set_time_limit(0);
        $form = I('post.');
        if (empty($form)) {
            $channellist = M('channel')->where('is_youying = 1')->select();
            $dateTime = [];
            foreach ($channellist as $key => $value) {
                $dateTime[$key]['day_time'] = date("Y-m");
                $dateTime[$key]['nick_name'] = $value['nick_name'];
                $dateTime[$key]['channel_id'] = $value['id'];
            }
            $this->assign('list', $dateTime);
            $this->display();
        } else {
            $channel_list = $form['channel_id'];
            $time = $form['datetime'];
            if (empty($time)) {
                $this->error('日期不能为空');
            }

            Vendor("phpexcel.Classes.PHPExcel");
            Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
            $objExcel = new \PHPExcel();
            $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
            $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getProperties()->setTitle("推广统计");
            $objExcel->getProperties()->setSubject("报表");
            $i = 0;
            foreach ($channel_list as $value) {
                if ($i >= 1) {
                    $objExcel->createSheet();
                }
                $objExcel->setActiveSheetIndex($i);
                $col = 5;
                $channelWhere['id'] = $value;
                $channelArr = M('channel')->where($channelWhere)->getField('nick_name');
                $nick_name = !empty($channelArr) ? $channelArr : "无";
                $objExcel->getActiveSheet()->setCellValue('J1', $nick_name); //渠道名称
                $objExcel->getActiveSheet()->setTitle($nick_name);
                $date = $this->getMonthDays($time);
                $this->excelHeadSet($objExcel);
                foreach ($date as $dateTime) {
                    $where['channel_id'] = $value;
                    $where['day_time'] = $dateTime['year'];
                    $valExcl = M('baobiao_yunying')->where($where)->field('follow_num,unfollow_num,follow_total,pay_sum,innerexpand_ids')->find();
                    $date_time = !empty($dateTime['year']) ? date("n月j日", strtotime($dateTime['year'])) : '';
                    $follow_num = !empty($valExcl['follow_num']) ? $valExcl['follow_num'] : 0; //新关注
                    $unfollow_num = !empty($valExcl['unfollow_num']) ? $valExcl['unfollow_num'] : 0; //取关
                    $jinzeng = $follow_num-$unfollow_num; //净增
                    $follow_total = !empty($valExcl['follow_total']) ? $valExcl['follow_total'] : "0"; //总关注
                    $innerexpand_ids = !empty($valExcl['innerexpand_ids']) ? $valExcl['innerexpand_ids'] : ""; //内推id
                    $pay_sum = !empty($valExcl['pay_sum']) ? getReadMoney($valExcl['pay_sum']) : "0"; //日充值
                    $day_quguancXinz = !is_nan($unfollow_num / $follow_num) ? round($unfollow_num / $follow_num, 2) : "-"; //每日取关除以每日新增
                    $day_quguanclei = !is_nan($unfollow_num / $follow_total) ? round($unfollow_num / $follow_total, 2) : "-"; //每日取关除以累计人数
                    $danfenrc = !is_nan($pay_sum / $follow_total) && ($follow_total != 0) ? round($pay_sum / $follow_total, 2) : "-"; //单粉日产出
                    $innerWhere['id'] = !empty($innerexpand_ids) ? array('in', $innerexpand_ids) : ""; //内推查询条件
                    $innerexpandArr = M('innerexpand')->where($innerWhere)->field('movies_id,click_num,gold_num,nick_name,remark,id')->order('add_time desc')->select();
                    $innerCount = count($innerexpandArr); //内推的条数
                    $objExcel->getActiveSheet()->setCellValue('A' . $col, $date_time); //日期
                    $objExcel->getActiveSheet()->setCellValue('B' . $col, $follow_num); //新关注
                    $objExcel->getActiveSheet()->setCellValue('D' . $col, $jinzeng); //净增
                    $objExcel->getActiveSheet()->setCellValue('C' . $col, $unfollow_num); //取关
                    $objExcel->getActiveSheet()->setCellValue('E' . $col, $follow_total); //总关注
                    $objExcel->getActiveSheet()->setCellValue('F' . $col, $day_quguancXinz); //每日取关除以每日新增
                    $objExcel->getActiveSheet()->setCellValue('G' . $col, $day_quguanclei); //每日取关除以累计人数
                    $objExcel->getActiveSheet()->setCellValue('H' . $col, $pay_sum); //日充值
                    $objExcel->getActiveSheet()->setCellValue('I' . $col, $danfenrc); //单粉日产出
                    if ($innerCount == 0) {
                        $col++;
                        continue;
                    } else if ($innerCount > 1) {
                        //合并单元格
                        $nextCol = $col + ($innerCount - 1);
                        $objExcel->getActiveSheet()->mergeCells('A' . $col . ':A' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('B' . $col . ':B' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('C' . $col . ':C' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('D' . $col . ':D' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('E' . $col . ':E' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('F' . $col . ':F' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('G' . $col . ':G' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('H' . $col . ':H' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('I' . $col . ':I' . $nextCol);
                        $objExcel->getActiveSheet()->mergeCells('Q' . $col . ':Q' . $nextCol);
                    }
                    $exInnerId = explode(',', $innerexpand_ids);
                    $newData = [];
                    foreach ($exInnerId as $vals) {
                        foreach ($innerexpandArr as $val) {
                            if ($vals == $val['id']) {
                                $newData[] = $val;
                            }
                        }
                    }
                    foreach ($newData as &$v) {
                        $pay_sum = M('trade')->where('innerexpand_id=' . $v['id'] . ' and pay_status = 1')->sum('pay');
                        $pay_sum_now = !empty($pay_sum) ? $pay_sum : "0"; //总充值
                        $proportion = round($v['gold_num'] / $v['click_num'], 1);
                        $gold = !is_nan($proportion) ? $proportion : 0; //金币消费指数
                        $movies_name = "";
                        if (!empty($v['movies_id'])) {
                            $moviesName = M('movies')->where('id=' . $v['movies_id'])->field('name,org_name')->find();
                            $movies_name = $data[$k]['movies_name'] = !empty($moviesName['org_name']) ? $moviesName['name'] . " [原名:" . $moviesName['org_name'] . "]" : $moviesName['name'];
                        } else {
                            $movies_name = "";
                        }
                        $objExcel->getActiveSheet()->setCellValue('K' . $col, $v['click_num']); //观看人数
                        $objExcel->getActiveSheet()->setCellValue('L' . $col, getReadMoney($pay_sum_now)); //总充值
                        $objExcel->getActiveSheet()->setCellValue('M' . $col, $gold); //金币消费指数
                        $overNumberPeo = !is_nan($v['pay_sum'] / $v['click_num']) ? round(getReadMoney($pay_sum_now) / $v['click_num'], 2) : "-";  //总充值/观看人数
                        $objExcel->getActiveSheet()->setCellValue('N' . $col, $overNumberPeo); //总充值/观看人数
                        $objExcel->getActiveSheet()->setCellValue('O' . $col, $movies_name); //电影名称
                        $objExcel->getActiveSheet()->setCellValue('P' . $col, $v['remark']); //标题
                        $col++;
                    }
                }
                $i++;
            }




            $savefile = "有影涨粉表_" . time() . ".xls";
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $savefile . '"');
            header('Cache-Control: max-age=0');
            // 用户下载excel
            $objWriter->save('php://output');
        }
    }

    public function excelHeadSet($objPHPExcel) {
        $objPHPExcel->getActiveSheet()->mergeCells('A1:I2');

        $objPHPExcel->getActiveSheet()->mergeCells('A3:A4');
        $objPHPExcel->getActiveSheet()->mergeCells('B3:B4');
        $objPHPExcel->getActiveSheet()->mergeCells('C3:C4');
        $objPHPExcel->getActiveSheet()->mergeCells('D3:D4');
        $objPHPExcel->getActiveSheet()->mergeCells('E3:E4');
        $objPHPExcel->getActiveSheet()->mergeCells('F3:F4');
        $objPHPExcel->getActiveSheet()->mergeCells('G3:G4');
        $objPHPExcel->getActiveSheet()->mergeCells('H3:H4');
        $objPHPExcel->getActiveSheet()->mergeCells('I3:I4');

        $objPHPExcel->getActiveSheet()->mergeCells('J1:Q2');
        $objPHPExcel->getActiveSheet()->mergeCells('J3:P3');
        //设置宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setAutoSize(false);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(12);
        //自动换行
        $objPHPExcel->getActiveSheet()->getStyle('O4:O1000')->getAlignment()->setWrapText(TRUE);
        $objPHPExcel->getActiveSheet()->getStyle('N4:N1000')->getAlignment()->setWrapText(TRUE);
        //设置字体
        $objPHPExcel->getActiveSheet()->getStyle('I1')->getFont()->setName('宋体') //字体
                ->setSize(22) //字体大小
                ->setBold(true); //字体加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1:P4')->getFont()->setName('宋体') //字体
                ->setSize(12) //字体大小
                ->setBold(true); //字体加粗
        $objPHPExcel->getActiveSheet()->setCellValue('A3', '日期'); //日期
        $objPHPExcel->getActiveSheet()->setCellValue('B3', '新关注'); //新关注
        $objPHPExcel->getActiveSheet()->setCellValue('D3', '净增'); //新关注
        $objPHPExcel->getActiveSheet()->setCellValue('C3', '取消关注'); //取关
        $objPHPExcel->getActiveSheet()->setCellValue('E3', '总关注'); //总关注
        $objPHPExcel->getActiveSheet()->setCellValue('F3', '每日取关/每日新增'); //每日取关除以每日新增
        $objPHPExcel->getActiveSheet()->setCellValue('G3', '每日取关/累计人数'); //每日取关除以累计人数
        $objPHPExcel->getActiveSheet()->setCellValue('H3', '日充值'); //日充值
        $objPHPExcel->getActiveSheet()->setCellValue('I3', '单粉日产出'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('J3', '客服消息和内推数据'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('J4', '时间段'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('K4', '观看人数'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('L4', '总充值'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('M4', '金币消费指数'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('M4', '总充值/观看人数'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('O4', '电影名称'); //单粉日产出
        $objPHPExcel->getActiveSheet()->setCellValue('P4', '标题'); //单粉日产出
        $objPHPExcel->getActiveSheet()->mergeCells('Q3:Q4');
        $objPHPExcel->getActiveSheet()->setCellValue('Q3', '备注'); //单粉日产出
        //加边框
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array(//设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:Q200')->applyFromArray($styleThinBlackBorderOutline);
    }

    public function setStyle($objPHPExcel) {
        $objPHPExcel->getActiveSheet()->getStyle('A3')->getFont()->setName('宋体') //字体
                ->setSize(12) //字体大小
                ->setBold(true); //字体加粗
    }

}

<?php

/**
 * 。。。
 * @time         2019-5-13
 * @author       tsj
 * @version     1.0
 */

namespace Back\Controller;
use Common\Lib\Redis;
use \Back\Controller\CommentController;

class MoviesDataController extends CommentController {

    /**
     * Redis的连接句柄
     * @var object
     */
    private $redis;

    public function __construct() {
        parent::__construct();
        // 连接数据库
        $this->redis = new \Redis();
//        $this->redis->connect('127.0.0.1', 6380, 0);
//        $this->redis->auth('tsj');

        $this->redis->connect('172.16.16.9', 6379, 0);
        $this->redis->auth('crs-pkviqe1h:tujie888#@!');
        $this->redis->select(0);
    }

    public function index() {
        $this->display();
    }

    /**
     * 下载
     */
    public function downWord() {
        $accurateDay = array();
        $formData = I('post.');
        $selectTime = array();

        if (!$formData['start_time'] != '' || !$formData['end_time'] != '') {
            $this->error('时间段必须选择');
        }
        if(empty($formData['comic_id'])){
             $this->error('图解ID必须输入');
        }
        $timeStr = strtotime($formData['start_time']);
        $timeStrend = strtotime($formData['end_time']);
        $selectTime['add_time'] = array(
            array('egt', $timeStr),
            array('elt', strtotime(date("Y-m-d 23:59:59", $timeStrend)))
        );
        for ($timeStr; $timeStr <= $timeStrend; $timeStr += 86400) {
            $accurateDay[] = date('Ymd', $timeStr);
        }

        $explode_movies_id = explode(',', $formData['comic_id']);
        $index_movies_id_arr = array_filter($explode_movies_id);
        if(count($index_movies_id_arr) > 200){
            $this->error('图解ID不能超过200条');
        }
      
        $where['id'] = ['in', $index_movies_id_arr];
        $chapter_num = M('chapter')->where('movies_id = movies.id')->fetchSql(true)->count(1);
        $resData = M('movies as movies')->where($where)->order('movies.add_time desc')
                ->field('movies.id,name,org_name,sex,form,begin_pay,(' . $chapter_num . ') as chapter_num,mold,price,status')
                ->select();
        foreach ($resData as &$value) {
            $selectTime['movies_id'] = $value['id'];
            $expand_num = M('expand')->where($selectTime)->count(1);
            $innerexpand_num = M('innerexpand')->where($selectTime)->count(1);
            $value['expand_num'] = $expand_num;
            $value['innerexpand_num'] = $innerexpand_num;
        }

        $form = M('form')->field('id,name')->select();
        //精确天数
        $idArr = array();
        foreach ($resData as $k => &$v) {
            $json = json_decode($v['form'], true);
            $str = '';
            foreach ($form as $m) {
                if (in_array($m['id'], $json)) {
                    $str .= '|' . $m['name'];
                }
            }
            $resData[$k]['form'] = trim($str, '|');

            $data = array();
            foreach ($accurateDay as $day) {
                $data[] = $this->Statistics($v['id'], $day);
            }
            $idArr[$k]['id'] = $v['id'];
            $idArr[$k]['playCount'] = $data['playCount'];
            $listRedis = $this->sum($data);
            $resData[$k]['sum'] = $listRedis; //浏览数 活跃用户数 搜索数 收藏数 点赞数
            $resData[$k]['pay_num'] = $listRedis['TradeSuccessCount'] ? $listRedis['TradeSuccessCount'] : 0;  //订单数
            $resData[$k]['pay_count'] = $listRedis['TradePay'] ? $listRedis['TradePay'] / 100 : 0; //订单金额
            $resData[$k]['pay_Proportion'] = $listRedis['TradeSuccessCount'] && $listRedis['user'] ? round(($listRedis['TradeSuccessCount'] / $listRedis['user']) * 100, 2) . '%' : ''; //付费率
            $resData[$k]['ARPPU'] = $listRedis['TradePay'] && $listRedis['TradeTotalCount'] ? round(($listRedis['TradePay'] / 100) / $listRedis['TradeTotalCount'], 2) : 0; //平均每付费用户金额
            $resData[$k]['ARPU'] = $listRedis['TradePay'] && $listRedis['user'] ? round(($listRedis['TradePay'] / 100) / $listRedis['user'], 2) : 0; //平均用户收入
            $idArr[$k]['id'] = $v['id'];
            $idArr[$k]['playCount'] = $data['playCount'];
            $resData[$k]['expand_num'] = $v['expand_num'];
            $resData[$k]['innerexpand_num'] = $v['innerexpand_num'];
        }
        $this->redis->close();
        foreach ($resData as $k => &$v) {
            $redis = new Redis();
            $gold = '';
            foreach ($accurateDay as $days) {
                $key = 'movies:' . $days . ":gold";
                list($code, $result) = $redis->zScore($key, $v['id']);
                if ($code == 200) {
                    $gold += $result;
                }
            }
            $resData[$k]['gold'] = $gold;
            $resData[$k]['consumption'] = $gold > 0 && $v['sum']['playCount'] > 0 ? round($gold / $v['sum']['playCount'], 2) : 0;
        }
        $time1 = date("Y-m-d");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="影片分析表' . $time1 . '.xls"');
        header("Content-Transfer-Encoding:binary");
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);


        $objExcel->getProperties()->setTitle("影片分析表");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->getStyle('A1:I1200')->getAlignment()->setWrapText(true);
      

        $objExcel->getActiveSheet()->setCellValue('A1', "名称");
        $objExcel->getActiveSheet()->setCellValue('B1', "状态");
        $objExcel->getActiveSheet()->setCellValue('C1', "活跃用户数/浏览用户数");
        $objExcel->getActiveSheet()->setCellValue('D1', "外推条数");
        $objExcel->getActiveSheet()->setCellValue('E1', "内推条数");
        $objExcel->getActiveSheet()->setCellValue('F1', "订单数");
        $objExcel->getActiveSheet()->setCellValue('G1', "订单金额");
        $objExcel->getActiveSheet()->setCellValue('H1', "付费率");
        $objExcel->getActiveSheet()->setCellValue('I1', "ARPPU");
        $objExcel->getActiveSheet()->setCellValue('J1', "ARPU");
        $objExcel->getActiveSheet()->setCellValue('K1', "消耗金币");
        $objExcel->getActiveSheet()->setCellValue('L1', "消费指数（金币/浏览）");
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        $i = 2;
        foreach ($resData as $k => $values) {
            $nick_name = $values['org_name'] ? '原名：' . $values['org_name'] : '';
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $values['id'] . "\n" . $values['name'] . $nick_name);
            $sex = $values['status'] == 1 ? '已上架' : '已下架';
            $objExcel->getActiveSheet()->setCellValue('B' . $i,$sex);
            $playCount = $values['sum']['playCount'] ? $values['sum']['playCount'] : 0;
            $user = $values['sum']['user'] ? $values['sum']['user'] : 0;
            $objExcel->getActiveSheet()->setCellValue('C' . $i, $user . "/" . $playCount );
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $values['expand_num']);
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $values['innerexpand_num']);
            $objExcel->getActiveSheet()->setCellValue('F' . $i, $values['pay_num']);
            $objExcel->getActiveSheet()->setCellValue('G' . $i, $values['pay_count']);
            $objExcel->getActiveSheet()->setCellValue('H' . $i, $values['pay_Proportion']);
            $objExcel->getActiveSheet()->setCellValue('I' . $i, $values['ARPPU']);
            $objExcel->getActiveSheet()->setCellValue('J' . $i, $values['ARPU']);
            $objExcel->getActiveSheet()->setCellValue('K' . $i, $values['gold'] );
            $objExcel->getActiveSheet()->setCellValue('L' . $i, $values['consumption']);
            $i++;
        }
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $objExcel->getActiveSheet()->getStyle( 'A1:L'.($i-1))->applyFromArray($styleThinBlackBorderOutline);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objWriter->save('php://output');
    }

    /**
     * 将数组累加
     * @param type $data
     */
    public function sum($data) {
        $repeat_arr = array();
        foreach ($data as $value) {
            foreach ($value as $key => $num) {
                if ($value[$key]) {
                    $repeat_arr[$key] += $value[$key];
                }
            }
        }
        return $repeat_arr;
    }

    public function Statistics($movies_id, $date) {

        //浏览数
        $data['playCount'] = $this->redis->zScore('movies:' . $date . ':playCount:zset', $movies_id);
        //活跃数
        $data['user'] = $this->redis->rawcommand('PFCOUNT', 'movies:' . $date . ':follows:pf:' . $movies_id);
        //搜索数
        $data['searchCount'] = $this->redis->zScore('movies:' . $date . ':searchCount:zset', $movies_id);
        //收藏数
        $data['collectCount'] = $this->redis->zScore('movies:' . $date . ':collectCount:zset', $movies_id);
        //点赞数
        $data['dingCount'] = $this->redis->zScore('movies:' . $date . ':dingCount:zset', $movies_id);
        //订单总量
        $data['TradeTotalCount'] = $this->redis->zScore('movies:' . $date . ':TradeTotalCount:zset', $movies_id);
        //订单总金额
        $data['TradePay'] = $this->redis->zScore('movies:' . $date . ':TradePay:zset', $movies_id);
        //订单支付成功量
        $data['TradeSuccessCount'] = $this->redis->zScore('movies:' . $date . ':TradeSuccessCount:zset', $movies_id);

        return $data;
    }

}

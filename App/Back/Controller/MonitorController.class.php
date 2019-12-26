<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Back\Controller;

set_time_limit(0);

class MonitorController {

    public $UserId = 155960;
    public $secret = '0df0340d-ffeb-40cf-bb7e-c32917dee7e3';
    public $ScrollId = '';

    public function getArticleData($wpa_url, $dbName) {
        $url = 'http://openapi2.xiguaji.com/v3/ArticleSearch/GetArticlesByLink';
        $data['KeyWord'] = $wpa_url;
        $data['FromDateCode'] = date('Ymd', strtotime('-1 month'));
        $data['ToDateCode'] = date('Ymd', time());
        $data['ScrollId'] = '';
        do {
            $json_str = json_encode($data, JSON_UNESCAPED_SLASHES);
            $CheckSum = $this->genCheckSum($json_str, $this->secret);
            $header = array(
                'Content-Type:application/json',
                'Checksum:' . $CheckSum,
                'UserId:' . $this->UserId,
            );
            $res = $this->http_post($url, $json_str, $header);
            $getData = json_decode($res, true);
            dump($getData);
            if ($getData['Articles'] != '') {
                $data['ScrollId'] = $getData['ScrollId'];
            } else {
                $data['ScrollId'] = '';
            }
            foreach ($getData['Articles'] as &$v) {
                $v['expand_id'] = $this->checkUrl($v['LinkUrl']);
            }
            M($dbName)->addAll($getData['Articles']);
        } while ($data['ScrollId'] != '');
        echo 'ok';
    }

    public function text() {
        set_time_limit(0);
        dump(date('Ymd', strtotime('-1 month')));
        dump(date('Ymd', time()));

        $arr = array(
            'c.flgwx.com' => 'monitor_flgwx',
            'pala666.com' => 'monitor_pala666',
            'kongchengmeng.com' => 'monitor_kongchengmeng',
            'jiaman-tech.com' => 'monitor_jiaman',
            'chiyue888.com' => 'monitor_chiyue888',
            'chiyue666.com' => 'monitor_chiyue666',
            'jiayoumei-tech.com' => 'monitor'
        );
        foreach ($arr as $key => $value) {
            $this->getArticleData($key, $value);
        }
        echo 'this ok';
    }

    /**
     * 获取子评论
     */
    public function getChildrenComment() {
        $id = 16;
        $where['a.comments_id'] = $id;
        $index = M('movies_comments as a')->where(array('a.id' => $id))
                ->join('left join yy_user_info as i on i.user_id = a.user_id')
                ->field('a.id,a.user_id,a.to_user_id,a.movies_id,a.reply_comments_id,a.comments_id,a.comments,a.num_oo,a.num_xx,a.add_time,a.status,i.avatar,i.nick_name')
                ->find();
        $list = M('movies_comments as a')->where($where)
                ->join('left join yy_user_info as i on i.user_id = a.user_id')
                ->field('a.id,a.user_id,a.to_user_id,a.movies_id,a.reply_comments_id,a.comments_id,a.comments,a.num_oo,a.num_xx,a.add_time,a.status,i.avatar,i.nick_name')
                ->order('add_time desc')
                ->select();
        $data = array();
        $i = 0;
        foreach ($list as $value) {
            if ($value['reply_comments_id'] == $value['comments_id']) {

                $this->publicCommentArr = array();
                $data[$i] = $value;
                $data[$i]['children'] = $this->getChildren($list, $value['id']);
                $i++;
            }
        }
        $this->assign('index', $index);
        $this->assign('list', $data);
        $this->display();
    }

    public function checkUrl($url) {
        $url = urldecode($url);
        $par_url = parse_url($url);
        if (!$par_url['query']) {
            $expand_id = '';
            $queryParts = explode('/', $par_url['path']);
            foreach ($queryParts as $k => $pah) {
                if ($pah == 'expand_id') {
                    $expand_id = $queryParts[$k + 1];
                }
            }
            return $expand_id;
        } else {
            $strque = str_replace('&amp;', '&', $par_url['query']);
            $queryParts = explode('&', $strque);
            $expand_id = '';
            foreach ($queryParts as $value) {
                if (strpos($value, 'redirect_uri=') !== false) {
                    $redirect_url = str_replace('redirect_uri=', '', $value);
                    $decodeUrl = urldecode($redirect_url);
                    $redirect_parse_url = parse_url($decodeUrl);
                    $redirect_urlencode = $redirect_parse_url['query'];
                    $redirect_expand = explode('%2F', $redirect_urlencode);
                    foreach ($redirect_expand as $k => $v) {
                        if ($v == 'expand_id') {
                            $expand_id = $redirect_expand[$k + 1];
                        }
                    }
                } else if (strpos($value, 'expand_id=') !== false) {
                    $expand_id = str_replace('expand_id=', '', $value);
                }
            }
            return $expand_id;
        }
    }

    private function genCheckSum($body, $secret) {
        $encryStr = $body . $secret;
        $checkSum = strtolower(substr(md5($encryStr), 14, 4));
        return $checkSum;
    }

    public function http_post($url, $data_string, $header) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($header)) {
            $head = array(
                'X-AjaxPro-Method:ShowList',
                'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36'
            );
            $headArr = array_merge($head, $header);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headArr);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function showdata() {

        $i = I('page',1);
        $number = 100;
        $page = ($i - 1) * $number;

        $user_info = M('app_user_info as app')
                ->join('yy_user_info as u on u.user_id = app.user_id')
                ->where(' u.total >  0')
                ->field('app.user_id,u.is_vip,(select sum(t.pay) from yy_trade as t where t.user_id = app.user_id and t.pay_status = 1 and t.type = 1 and t.trade_no like "YYAPP%")/100 as vippay,'
                        . '(select sum(f.pay) from yy_trade as f where f.user_id = app.user_id and f.pay_status = 1 and f.type = 0 and f.trade_no like "YYAPP%")/100 as goldpay,'
                        . '(select count(1) from yy_trade as g where g.user_id = app.user_id and g.pay_status = 1 and g.type = 0 and g.trade_no like "YYAPP%") as goldsum')
                ->limit($page, $number)
                ->select();

        $downdata = array();
        foreach ($user_info as $k => $val) {
            $downdata[$k] = $val;
            $data = M('user_chapter as uc')
                    ->field('uc.movies_id,c.name,ch.name chapter,FROM_UNIXTIME(uc.add_time,"%Y-%m-%d %H:%i:%s") as add_time,(select count(1) from yy_chapter as ca where ca.movies_id = c.id) as mvoies_count')
                    ->join('yy_movies as c on uc.movies_id=c.id')
                    ->join('yy_chapter as ch on uc.chapter_id=ch.id')
                    ->where('uc.user_id=' . $val['user_id'])
                    ->order('uc.add_time desc')
                    ->select();
            $data1 = $this->array_unset_tt($data,'movies_id');
            foreach($data1 as $v){
                dump($v);
            }
//            $downdata[$k]['chapter_info'] = $data;
        }
        dump($downdata);
    }

    public function downword() {
        $pages = I('page', 1);
        $number = 100;
        $page = ($pages - 1) * $number;
        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);

        $objExcel->getProperties()->setTitle("推广统计");
        $objExcel->getProperties()->setSubject("报表");
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setCellValue('A1', "用户ID");
        $objExcel->getActiveSheet()->setCellValue('B1', "是否为VIP");
        $objExcel->getActiveSheet()->setCellValue('C1', "VIP充值金额");
        $objExcel->getActiveSheet()->setCellValue('D1', "书币充值笔数");
        $objExcel->getActiveSheet()->setCellValue('E1', "书币充值总额");
        $objExcel->getActiveSheet()->setCellValue('F1', "消费图解名称");
        $objExcel->getActiveSheet()->setCellValue('G1', "消费日期");
        $objExcel->getActiveSheet()->setCellValue('H1', "消费话数");
        $objExcel->getActiveSheet()->setCellValue('I1', "该图解总话数");
        $user_info = M('app_user_info as app')->
                join('yy_user_info as u on u.user_id = app.user_id')
                ->where(' u.total >  0')
                ->field('app.user_id,u.is_vip,(select sum(t.pay) from yy_trade as t where t.user_id = app.user_id and t.pay_status = 1 and t.type = 1 and t.trade_no like "YYAPP%")/100 as vippay,'
                        . '(select sum(f.pay) from yy_trade as f where f.user_id = app.user_id and f.pay_status = 1 and f.type = 0 and f.trade_no like "YYAPP%")/100 as goldpay,'
                        . '(select count(1) from yy_trade as g where g.user_id = app.user_id and g.pay_status = 1 and g.type = 0 and g.trade_no like "YYAPP%") as goldsum')
                ->limit($page, $number)
                ->select();
        $downdata = array();
        $col = 2;
        foreach ($user_info as $k => $val) {
            $downdata[$k] = $val;
            $objExcel->getActiveSheet()->setCellValue('A' . $col, $val['user_id']); //
            $objExcel->getActiveSheet()->setCellValue('B' . $col, $val['is_vip'] == 1 ? '是' : '否');
            $objExcel->getActiveSheet()->setCellValue('C' . $col, $val['vippay'] ? $val['vippay'] : 0); //
            $objExcel->getActiveSheet()->setCellValue('D' . $col, $val['goldpay'] ? $val['goldpay'] : 0); //
            $objExcel->getActiveSheet()->setCellValue('E' . $col, $val['goldsum'] ? $val['goldsum'] : 0); //
            $data = M('user_chapter as uc')
                    ->field('uc.movies_id,uc.price,c.name,ch.name chapter,uc.add_time,FROM_UNIXTIME(uc.add_time,"%Y-%m-%d %H:%i:%s") as add_time,(select count(1) from yy_chapter as ca where ca.movies_id = c.id) as mvoies_count')
                    ->join('yy_movies as c on uc.movies_id=c.id')
                    ->join('yy_chapter as ch on uc.chapter_id=ch.id')
                    ->where('uc.user_id=' . $val['user_id'])
                    ->order('uc.add_time desc')
                    ->select();
            if (empty($data)) {
                $col++;
                continue;
            } else {
                $datas = $this->array_unset_tt($data,'movies_id');
                $innerCount = count($datas);
             
                //合并单元格
                $nextCol = $col + ($innerCount - 1);
                $objExcel->getActiveSheet()->mergeCells('A' . $col . ':A' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('B' . $col . ':B' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('C' . $col . ':C' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('D' . $col . ':D' . $nextCol);
                $objExcel->getActiveSheet()->mergeCells('E' . $col . ':E' . $nextCol);
                foreach ($datas as $value) {
                    $objExcel->getActiveSheet()->setCellValue('F' . $col, $value['name']); //
                    $objExcel->getActiveSheet()->setCellValue('G' . $col, $value['add_time']); //
                    $objExcel->getActiveSheet()->setCellValue('H' . $col, $value['chapter']); //
                    $objExcel->getActiveSheet()->setCellValue('I' . $col, $value['mvoies_count']); //
                    $col++;
                }
            }
        }
        $savefile = "用户充值、阅读记录表_" . $pages . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefile . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    public function array_unset_tt($arr,$key){     
        //建立一个目标数组  
        $res = array();        
        foreach ($arr as $value) {           
           //查看有没有重复项  
           if(isset($res[$value[$key]])){  
              unset($value[$key]);  //有：销毁  
           }else{    
              $res[$value[$key]] = $value;  
           }    
        }  
        return $res;  
    }


}

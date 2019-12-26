<?php

/**
 * 数据分析站
 * 
 *  
 * @author      tsj 作者
 * @version     1.0 版本号
 */

namespace Back\Controller;

class testController extends CommonController {


    /**
     * 近三十天日活统计
     */
    public function tongji() {
        $channellist = M('channel')->select();
        $this->assign('channellist', $channellist);
        $this->display();
    }

    public function getRihuo() {
//        if (!IS_AJAX) {
//            $data['code'] = 200;
//            $data['error'] = '非法访问';
//            $this->ajaxReturn($data);
//        }
        $url = 'https://auth-graphmovie.yymedias.com/back/Redistest/getbit';
        $urlTow = 'https://auth-graphmovie.yymedias.com/back/Redistest/hget';
        $contArr = array();
        $dateY = '';
        $channel_id = I('post.channelId');
        for ($i = 14; $i >= 0; $i--) {
            $day = date('Ymd', strtotime('-' . $i . ' day'));
            $dateY .= date('n-j', strtotime('-' . $i . ' day')) . ",";
            $arr = array();
            if(!empty($channel_id)){
                $post_data['key'] = 'rihuo:c'.$channel_id.'-' . $day;
                $post_data['val'] = 'total';
                $res = http_request($urlTow, $post_data);
                $arr = json_decode($res, true);
            }else{
                $post_data['bit'] = 'rihuo:' . $day;
                dump($post_data);
                $res = http_request($url, $post_data);
                dump($res);
                $arr = json_decode($res, true);
            }
            print_r($res);
            if ($arr['code'] == 200) {
                $contArr[$day] = $arr['res'];
            }else{
                $contArr[$day] = 0;
            }
        }
        $dateCont = trim($dateY, ',');
        $lineDate = "";
        foreach ($contArr as $value) {
            $lineDate .= $value . ",";
        }
        $lineDate = trim($lineDate, ',');
        $data['code'] = 200;
        $data['line'] = $lineDate;
        $data['dateTime'] = $dateCont;
        $this->ajaxReturn($data);
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
        $url = 'https://auth-graphmovie.yymedias.com/back/Redistest/getzRevRange';
        $inDay = array();
        $contArr = array();
        $date = I('post.date');
        $dayte = empty($date) ? 0 : $date;
        for ($i = $dayte; $i >= 0; $i--) {
            $inDay[$i] = $day = date('Ymd', strtotime('-' . $i . ' day'));
            $post_data['key'] = 'channel:' . $day . ":gold";
            $post_data['start'] = '0';
            $post_data['end'] = '10';
            $res = http_request($url, $post_data);
            $arr = json_decode($res, true);
            if ($arr['code'] == 200) {
                $contArr[$day] = $arr['res'];
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
     * 近30天电影金币消耗
     */
    public function getMoviesGold() {
        if (!IS_AJAX) {
            $data['code'] = 200;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $url = 'https://auth-graphmovie.yymedias.com/back/Redistest/getzRevRange';
        $inDay = array();
        $contArr = array();
        $dateY = '';
        $date = I('post.date');
        $dayte = empty($date) ? 0 : $date;
        for ($i = $dayte; $i >= 0; $i--) {
            $inDay[$i] = $day = date('Ymd', strtotime('-' . $i . ' day'));
            $dateY .= date('n月j号', strtotime('-' . $i . ' day'));
            $post_data['key'] = 'movies:' . $day . ":gold";
            $post_data['start'] = '0';
            $post_data['end'] = '15';
            $res = http_request($url, $post_data);
            $arr = json_decode($res, true);
            if ($arr['code'] == 200) {
                $contArr[$i] = $arr['res'];
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
        $first = array_slice($newDataArr, 0, 15, true); //取前30位
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

    public function getUserGold() {
        if (!IS_AJAX) {
            $data['code'] = 200;
            $data['error'] = '非法访问';
            $this->ajaxReturn($data);
        }
        $url = 'https://auth-graphmovie.yymedias.com/back/Redistest/getzRevRange';
        $inDay = array();
        $contArr = array();
        $dateY = '';
        $date = I('post.date');
        $dayte = empty($date) ? 0 : $date;
        for ($i = $dayte; $i >= 0; $i--) {
            $inDay[$i] = $day = date('Ymd', strtotime('-' . $i . ' day'));
            $dateY .= date('n月j号', strtotime('-' . $i . ' day'));
            $post_data['key'] = 'users:' . $day . ":gold";
            $post_data['start'] = '0';
            $post_data['end'] = '10';
            $res = http_request($url, $post_data);
            $arr = json_decode($res, true);
            if ($arr['code'] == 200) {
                $contArr[$day] = $arr['res'];
            } else {
                $contArr[$day] = 0;
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

}

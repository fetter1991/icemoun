<?php

namespace Home\Controller;

use Common\Lib\TransApi;
use QL\QueryList;

class PlayerController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function test()
    {
        $json = file_get_contents(ROOT_PATH . 'jav.json');
        $data = json_decode($json, true);
        foreach ($data as $item) {
            $isExist = M('jav_movie')->where('censored_id = "' . $item['censored_id'] . '"')->find();
            if (!$isExist) {
                $addData           = $item;
                $addData['status'] = 0;
                M('jav_movie')->add($addData);
            }
        }
        
        $list = M('jav_movie')->where('status = 0')->select();
        foreach ($list as $v) {
            $result    = $this->getMovieInfo($v['censored_id']);
            $movieInfo = $result[0];

            $saveData['status']          = 1;
            $saveData['movie_pic_cover'] = $movieInfo['movie_pic_cover'];
            $saveData['release_date']    = $movieInfo['release_date'];
            $saveData['movie_length']    = str_replace('分鐘', "", $movieInfo['movie_length']);

            $res = M('jav_waterfall')->where('censored_id = "' . $v['censored_id'] . '"')->save($saveData);
            if ($res) {
                $poster = str_replace('-1.jpg', '', $movieInfo['list'][0]);;
                $waterfall['waterfall'] = $poster['src'];
                $waterfall['count']     = count($movieInfo['list']);
                $postAdd                = M('jav_waterfall')->add($waterfall);
            }
        }
    }

    /**
     * 首页
     */
    public function index()
    {
        $list = M('jav_movie')->where('status != 2')->select();
        $this->assign("sever", CDN_MOVIES);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     *详情页
     */
    public function detail()
    {
        $id        = I('id');
        $info      = M('jav_movie')->where('censored_id = "' . $id . '"')->find();
        $waterfall = M('jav_waterfall')->where('censored_id = "' . $info['censored_id'] . '"')->find();
        $this->assign("sever", CDN_MOVIES);
        $this->assign('info', $info);
        $this->assign('fall', $waterfall);
        $this->display();
    }

    /**
     *更改状态
     */
    public function changeType()
    {
        $data = I('get.');

        $save[$data['type']] = $data['value'];
        $res                 = M('jav_movie')->where('censored_id = "' . $data['id'] . '"')->save($save);
        if ($res) {
            $this->ajaxReturn(array('code' => 200, 'msg' => '修改成功'));
        } else {
            $this->ajaxReturn(array('code' => 0, 'msg' => '修改失败'));
        }
    }

    /**
     * 播放器
     */
    public function player()
    {
        $this->display();
    }


    /**
     * 采集影片信息
     * @param $id
     * @return mixed
     */
    public function getMovieInfo($id)
    {
        $sever = 'https://www.cdnbus.one/';
//        $sever = "https://www.dmmsee.one/";
        $url = $sever . $id;

        //采集规则
        $rules = [
            //标题
            'movie_pic_cover' => ['.bigImage', 'href'],
            'release_date' => ['.info>p:eq(1)', 'text', '-span'],
            'movie_length' => ['.info>p:eq(2)', 'text', '-span'],
            'list' => ['#sample-waterfall', 'html'],
        ];

        $result = QueryList::Query($url, $rules)->getData(function ($item) {
            $item['list'] = QueryList::Query($item['list'], array(
                'src' => array('a', 'href'),
            ))->data;
            return $item;
        });

        return $result;
    }


    //翻译
    public function getZh()
    {
        $title     = I('title');
        $translate = new TransApi();
        $title     = $translate->translate($title, 'jp', 'zh');
        $this->ajaxReturn(array('code' => 200, 'data' => $title));
    }

    /**
     * curl请求
     * @param $sUrl
     * @param $aHeader
     * @param $aData
     * @return bool|string
     */
    public function httpRequest($sUrl, $aHeader, $aData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aData));
        $sResult = curl_exec($ch);
        if ($sError = curl_error($ch)) {
            die($sError);
        }
        curl_close($ch);

        return $sResult;
    }

    //TODO  测试方法1
    public function test1()
    {
        $list = M('jav_movie')->select();
        foreach ($list as $key => $item) {
            $isExist = M('fan_check')->where('fanid = "' . $item['censored_id'] . '"')->find();
            if ($isExist) {
                $save['status'] = 1;
            } else {
                $save['status'] = 2;
            }
            $res = M('jav_movie')->where('censored_id = "' . $item['censored_id'] . '"')->save($save);
        }
    }

    //TODO  测试方法2
    public function test2()
    {
        $list = M('jav_movie')->select();
        foreach ($list as $item) {
            $isExist = M('jav_waterfall')->where('censored_id = "' . $item['censored_id'] . '"')->find();
            if (!$isExist) {
                $result = $this->getMovieInfo($item['censored_id']);
                $data   = $result[0];

                $add['censored_id'] = $item['censored_id'];
                $waterfall          = str_replace('-1.jpg', '', $data['list'][0]);;
                $add['waterfall'] = $waterfall['src'];
                $add['count']     = count($data['list']);
//            print_r($add);
                $res = M('jav_waterfall')->add($add);
            }
        }
    }
}
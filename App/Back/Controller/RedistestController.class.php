<?php
/**
 * 测试使用
 * User: Administrator
 * Date: 2017/9/27
 * Time: 16:23
 */

namespace Back\Controller;
use Think\Controller;
use Back\Controller\RedisclassController;
class RedistestController extends Controller{
    
    public $redis = '';


    public function __construct() {
        parent::__construct();
        $this->redis = new RedisclassController();
    }
    
    /**
     * 获取
     */
    public function getzRevRange() {
        try{
            $get  = I('post.');
            $key = $get['key'];
            $start = $get['start'];
            $end = $get['end'];
            list($code,$relust) = $this->redis->zRevRange($key,$start,$end);
            $arr = [
                'code' => $code,
                'res' =>$relust
            ];
            $json = json_encode($arr);
            echo $json;
        } catch (Exception $ex) {
            $arr = array('code'=>'0','res'=>$ex->getMessage());
            $json = json_encode($arr);
            echo $json;
        }
    }
    
    /**
     * 获取字符串类型数据
     */
    public function getString() {
        try{
            $get  = I('post.');
            $string = $get['string'];
            list($code,$relust) = $this->redis->stringGet($string);
            $arr = [
                'code' => $code,
                'res' =>$relust
            ];
            $json = json_encode($arr);
            echo $json;
        } catch (Exception $ex) {
            $arr = array('code'=>'0','res'=>$ex->getMessage());
            $json = json_encode($arr);
            echo $json;
        }
    }    
    
    /**
     * 获取bit类型数据
     */
    public function getbit() {
        try{
            $get  = I('post.');
            $string = $get['bit'];
            list($code,$relust) = $this->redis->bitcount($string);
            $arr = [
                'code' => $code,
                'res' =>$relust
            ];
            $json = json_encode($arr);
            echo $json;
        } catch (Exception $ex) {
            $arr = array('code'=>'0','res'=>$ex->getMessage());
            $json = json_encode($arr);
            echo $json;
        }
    }
    
    /**
     * 获取bit类型数据
     */
    public function getzScore() {
        $get  = I('post.');
        $string = $get['key'];
        $val = $get['val'];
        list($code,$relust) = $this->redis->zScore($string,$val);
        $arr = [
            'code' => $code,
            'res' =>$relust
        ];
        $json = json_encode($arr);
        echo $json;
    }
    
    
    public function hget() {
        $get  = I('post.');
        $string = $get['key'];
        $val = $get['val'];
        list($code,$relust) = $this->redis->hGet($string,$val);
        $arr = [
            'code' => $code,
            'res' =>$relust
        ];
        $json = json_encode($arr);
        echo $json;
    }

    
}
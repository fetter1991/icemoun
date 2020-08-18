<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/5/8
 * Time: 11:44
 */
namespace Common\Lib;
use Think\Exception;

class AnalyseRedis {
    private $_conn = null;
    private $_db = 0;
    private $_day = ''; // 日期
    private $_yesterday = ''; // 昨日
    private $_h = ''; // 小时
    
    public function __construct(){//注意：以下是配置文件中的常量，请读者自行更改
        $redis = new \redis();
        $redis->connect('172.16.16.9',6379);
        $redis->auth('crs-pkviqe1h:tujie888#@!');
        $this->_conn = $redis;
        $this->_day = date('Ymd', NOW_TIME);
        $this->_yesterday = date('Ymd', NOW_TIME-86400);
        $this->_h = date('H', NOW_TIME);
        $this->_db = 1;
    }
    
    /**
     * 影片观看统计
     * @param $movieId
     * @param $user
     */
    public function moviePlay($movieId,$user){
        try{
            $playCountKey = 'movies:'.$this->_day.':playCount:zset'; // 影片播放量
            $this->_conn->zIncrBy($playCountKey,1,$movieId);
    
            $playUsersKey = 'movies:'.$this->_day.':users:pf:'.$movieId; // 影片观看人数
            $this->_conn->pfAdd($playUsersKey, [$user['id']]);
    
            if ($user['is_follow']) {
                $followUsersKey = 'movies:'.$this->_day.':follows:pf:'.$movieId; // 影片观看粉丝数
                $this->_conn->pfAdd($followUsersKey, [$user['id']]);
            }
        } catch (Exception $exception) {
        
        }
    }
    
    /**
     * 影片搜索统计
     * @param $movieId
     */
    public function movieSearch($movieId) {
        try {
            $searchCountKey = 'movies:'.$this->_day.':searchCount:zset'; // 影片搜索量
            $this->_conn->zIncrBy($searchCountKey, 1,$movieId);
        } catch (Exception $exception) {
        
        }
    }
    
    /**
     * 影片收藏量
     * @param $movieId
     */
    public function movieCollect($movieId)  {
        try {
            $collectCountKey = 'movies:'.$this->_day.':collectCount:zset'; // 影片收藏量
            $this->_conn->zIncrBy($collectCountKey, 1,$movieId);
        } catch (Exception $exception) {
        
        }
    }
    
    /**
     * 影片点赞量
     * @param $movieId
     */
    public function movieDing($movieId) {
        try {
            $dingCountKey = 'movies:'.$this->_day.':dingCount:zset'; // 影片点赞量
            $this->_conn->zIncrBy($dingCountKey, 1,$movieId);
        } catch (Exception $exception) {
        
        }
    }
    
    /**
     * 影片订单成功统计
     * @param $movieId
     * @param $pay
     */
    public function movieTradeSuccess($movieId, $pay, $payTime=0) {
        try {
            $payTime = empty($payTime) ? $this->_day : $payTime;
            $tradeCountKey = 'movies:'.$payTime.':TradeSuccessCount:zset'; // 影片订单量
            $this->_conn->zIncrBy($tradeCountKey, 1,$movieId);
            
            $tradeKey = 'movies:'.$payTime.':TradePay:zset'; // 影片订单金额
            $this->_conn->zIncrBy($tradeKey, $pay, $movieId);
        } catch (Exception $exception) {
        
        }
    }
    
    /**
     * 影片订单量
     * @param $movieId
     */
    public function movieRecharge($movieId) {
        try {
            $tradeCountKey = 'movies:'.$this->_day.':TradeTotalCount:zset'; // 影片订单总数
            $this->_conn->zIncrBy($tradeCountKey,1,$movieId);
        } catch (Exception $exception) {
        
        }
    }
    
    
    
    /**
     * 金币消耗统计
     * @param $movieId 电影ID
     * @param $gold 金币数量
     */
    public function movieGold($movieId, $gold){
        try {
            $TradeCountKey = 'movies:'.$this->_day.':gold:zset'; // 影片订单金额
            $this->_conn->zIncrBy($TradeCountKey, $gold,$movieId);
        } catch (Exception $exception) {
        
        }
    }

    
    public function close(){
        $this->_conn->close();
    }
    
}
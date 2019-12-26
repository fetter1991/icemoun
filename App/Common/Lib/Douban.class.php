<?php

/**
 * Created by PhpStorm.
 * User: Nosirc
 * Date: 2019/5/27
 * Time: 16:27
 */

namespace Common\Lib;

class Douban {

    private $xpath;
    private $name;
    private $year;
    private $img_url;
    private $score;
    private $director;
    private $screenwriter;
    private $actor;
    private $tags;
    private $region;
    private $languages;
    private $web_url;
    private $release_date;
    private $season;
    private $episode;
    private $duration;
    private $also_called;
    private $imdb_url;

    public function __construct($db_id) {
        $movie = $this->curl_get_https('https://movie.douban.com/subject/' . $db_id . '/');
        //创建一个DomDocument对象，用于处理一个HTML
        $dom = new \DOMDocument();
        //从一个字符串加载HTML
        @$dom->loadHTML($movie);
        //使该HTML规范化
        $dom->normalize();
        //用DOMXpath加载DOM，用于查询
        $this->xpath = new \DOMXPath($dom);

        //设置属性
        self::setAttrs();
    }

    /**
     *    作用：以get方式提交xml到对应的接口url
     *
     */
    public function curl_get_https($url) {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;    //返回json对象
    }

    /**
     * 获取全部属性
     * @return array
     */
    public function getAttributes($object): array {
        $attributes = [];
        $className = get_class($object);

        foreach ((array) $object as $name => $value) {
            $name = explode("\0", (string) $name);

            if (count($name) === 1) {
                $name = $name[0];
            } else {
                if ($name[1] !== $className) {
                    $name = $name[1] . '::' . $name[2];
                } else {
                    $name = $name[2];
                }
            }

            if ($name != 'xpath') {
                $attributes[$name] = $value ?: '';
            }
        }

        return $attributes;
    }

    //设置影片名称
    private function setName() {
        $name = $this->xpath->evaluate("//*[@id='content']/h1/span[1]/text()");
        if (!empty($name->length)) {
            $this->name = $name->item(0)->nodeValue;
        }
    }

    //设置评分
    public function setScore() {
        $scores = $this->xpath->evaluate("//*[@id='interest_sectl']/div/div[2]/strong/text()");
        if (!empty($scores->length)) {
            $this->score = $scores->item(0)->nodeValue;
        }
    }

    //设置海报url
    public function setImgUrl() {
        $img = $this->xpath->evaluate("//*[@id='mainpic']/a/img/@src");
        if (!empty($img->length)) {
            $this->img_url = $img->item(0)->nodeValue;
        }
    }

    //设置影片年份
    private function setYear() {
        $years = $this->xpath->evaluate("//*[@id='content']/h1/span[2]/text()");
        if (!empty($years->length)) {
            $this->year = rtrim(ltrim($years->item(0)->nodeValue, '('), ')');
        }
    }

    //获取名称
    public function getName() {
        return $this->name ?: '';
    }

    //获取年份
    public function getYear() {
        return $this->year ?: '';
    }

    //获取海报
    public function getImgUrl() {
        return $this->img_url ?: '';
    }

    //获取豆瓣评分
    public function getScore() {
        return $this->score ?: '';
    }

    //获取导演
    public function getDirectors() {
        return $this->director ?: '';
    }

    //获取编剧
    public function getScreenwriter() {
        return $this->screenwriter ?: '';
    }

    //获取演员
    public function getActor() {
        return $this->actor ?: '';
    }

    //获取标签
    public function getTags() {
        return $this->tags ?: '';
    }

    //获取地区
    public function getRegion() {
        return $this->region ?: '';
    }

    //获取语言
    public function getLanguages() {
        return $this->languages ?: '';
    }

    //获取官方网站
    public function getWebUrl() {
        return $this->web_url ?: '';
    }

    //获取上映日期
    public function getReleaseDate() {
        return $this->release_date ?: '';
    }

    //获取季数
    public function getSeason() {
        return $this->season ?: 0;
    }

    //获取集数
    public function getEpisode() {
        return $this->episode ?: 0;
    }

    //获取片长
    public function getDuration() {
        return $this->duration ?: '';
    }

    //获取又名
    public function getAlsoCalled() {
        return $this->also_called ?: '';
    }

    //获取imdb_url
    public function getImdbUrl() {
        return $this->imdb_url ?: '';
    }

    //是否上映
    public function getIsOn() {
        $is_on = $this->xpath->evaluate("//*[@id='interest_sectl']/div/div[2]/div/div[2]");
        if (!empty($is_on->length)) {
            $is_on = $is_on->item(0)->nodeValue;
            if (trim($is_on) == "尚未上映") {
                return 2;
            }
        }
        return 1;
    }

    //设置属性
    public function setAttrs() {
        self::setName();
        self::setImgUrl();
        self::setScore();
        self::setYear();

        $spans = $this->xpath->query("//*[@id='info']/span");
        $length = $spans->length;
        for ($i = 1; $i <= $length; $i++) {
            $span = $this->xpath->query("//*[@id='info']/span[" . $i . "]");
            if (!empty($span->length)) {
                $value = $span->item(0)->nodeValue;
                if (strstr($value, '导演')) {
                    $this->director = trim(strstr($value, ":"), ': ');
                } elseif (strstr($value, '编剧')) {
                    $this->screenwriter = trim(strstr($value, ":"), ': ');
                } elseif (strstr($value, '主演')) {
                    $this->actor = trim(strstr($value, ":"), ': ');
                } elseif (strstr($value, '类型')) {
                    $j = 1;
                    while ($j) {
                        $tag = $this->xpath->query("//*[@id='info']/span[" . ($i + $j) . "]");
                        if (!empty($tag->length) && $tag->item(0)->nodeValue != '官方网站:' && $tag->item(0)->nodeValue != '制片国家/地区:') {
                            $this->tags[] = $tag->item(0)->nodeValue;
                            $j++;
                        } else {
                            $j = false;
                        }
                    }
                    $this->tags = implode(' / ', $this->tags);
                } elseif (strstr($value, '官方网站')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::a[1]/@href");
                    if (!empty($item->length)) {
                        $this->web_url = $item->item(0)->nodeValue;
                    }
                } elseif (strstr($value, '制片国家/地区')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::text()[1]");
                    if (!empty($item->length)) {
                        $this->region = trim($item->item(0)->nodeValue, ' ');
                    }
                } elseif (strstr($value, '语言')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::text()[1]");
                    if (!empty($item->length)) {
                        $this->languages = trim($item->item(0)->nodeValue, ' ');
                    }
                } elseif (strstr($value, '上映日期') || strstr($value, '首播')) {
                    $item = $this->xpath->query("//*[@id='info']/span[".($i + 1)."]");
                    if(!empty($item->length)){
                        if(strstr($item->item(0)->nodeValue,'(')){
                            $this->release_date = substr($item->item(0)->nodeValue,0,strpos($item->item(0)->nodeValue, '('));
                        }else{
                            $this->release_date = $item->item(0)->nodeValue;
                        }
                    }
                } elseif (strstr($value, '季数')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::text()[1]");
                    if (!empty($item->length)) {
                        $this->season = trim($item->item(0)->nodeValue, ' ');
                    }
                    if (empty($this->season)) {
                        $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::select/option");
                        $this->season = $item->length;
                    }
                } elseif (strstr($value, '集数')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::text()[1]");
                    if (!empty($item->length)) {
                        $this->episode = trim($item->item(0)->nodeValue, ' ');
                    }
                } elseif (strstr($value, '片长')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::text()[1]");
                    if (!empty($item->length)) {
                        $this->duration = trim($item->item(0)->nodeValue, ' ');
                    }
                    if (empty($this->duration)) {
                        $item = $this->xpath->query("//*[@id='info']/span[" . ($i + 1) . "]");
                        if (!empty($item->length)) {
                            $this->duration = $item->item(0)->nodeValue;
                        }
                    }
                } elseif (strstr($value, '又名')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::text()[1]");
                    if (!empty($item->length)) {
                        $this->also_called = trim($item->item(0)->nodeValue, ' ');
                    }
                } elseif (strstr($value, 'IMDb链接')) {
                    $item = $this->xpath->query("//*[@id='info']/span[" . $i . "]/following-sibling::a[1]/@href");
                    if (!empty($item->length)) {
                        //$item->item(0)->nodeValue;
                        $this->imdb_url = $item->item(0)->nodeValue;
                    }
                }
            }
        }
    }

}

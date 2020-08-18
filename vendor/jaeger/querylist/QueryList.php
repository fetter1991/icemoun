<?php

namespace QL;

use phpQuery, Exception, ReflectionClass;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * QueryList
 *
 * 一个基于phpQuery的通用列表采集类
 *
 * @author            Jaeger
 * @email            734708094@qq.com
 * @link            http://git.oschina.net/jae/QueryList
 * @version         3.2.1
 *
 * @example
 *
 * //获取CSDN移动开发栏目下的文章列表标题
 * $hj = QueryList::Query('http://mobile.csdn.net/',array("title"=>array('.unit h1','text')));
 * print_r($hj->data);
 *
 * //回调函数1
 * function callfun1($content,$key)
 * {
 * return '回调函数1：'.$key.'-'.$content;
 * }
 * class HJ{
 * //回调函数2
 * static public function callfun2($content,$key)
 * {
 * return '回调函数2：'.$key.'-'.$content;
 * }
 * }
 * //获取CSDN文章页下面的文章标题和内容
 * $url = 'http://www.csdn.net/article/2014-06-05/2820091-build-or-buy-a-mobile-game-backend';
 * $rules = array(
 * 'title'=>array('h1','text','','callfun1'),    //获取纯文本格式的标题,并调用回调函数1
 * 'summary'=>array('.summary','text','-input strong'), //获取纯文本的文章摘要，但保strong标签并去除input标签
 * 'content'=>array('.news_content','html','div a -.copyright'),    //获取html格式的文章内容，但过滤掉div和a标签,去除类名为copyright的元素
 * 'callback'=>array('HJ','callfun2')      //调用回调函数2作为全局回调函数
 * );
 * $rang = '.left';
 * $hj = QueryList::Query($url,$rules,$rang);
 * print_r($hj->data);
 *
 * //继续获取右边相关热门文章列表的标题以及链接地址
 * $hj->setQuery(array('title'=>array('','text'),'url'=>array('a','href')),'#con_two_2 li');
 * //输出数据
 * echo $hj->getData();
 */
class QueryList
{
    public $data;
    public $html;
    private $page;
    private $pqHtml;
    private $outputEncoding = false;
    private $inputEncoding = false;
    private $htmlEncoding;
    public static $logger = null;
    public static $instances;

    public function __construct()
    {
    }

    /**
     * 静态方法，访问入口
     * @param string $page 要抓取的网页URL地址(支持https);或者是html源代码
     * @param array $rules 【选择器数组】说明：格式array("名称"=>array("选择器","类型"[,"标签过滤列表"][,"回调函数"]),.......[,"callback"=>"全局回调函数"]);
     *                               【选择器】说明:可以为任意的jQuery选择器语法
     *                               【类型】说明：值 "text" ,"html" ,"HTML标签属性" ,
     *                               【标签过滤列表】:可选，要过滤的选择器名，多个用空格隔开,当标签名前面添加减号(-)时（此时标签可以为任意的元素选择器），表示移除该标签以及标签内容，否则当【类型】值为text时表示需要保留的HTML标签，为html时表示要过滤掉的HTML标签
     *                               【回调函数】/【全局回调函数】：可选，字符串（函数名） 或 数组（array("类名","类的静态方法")），回调函数应有俩个参数，第一个参数是选择到的内容，第二个参数是选择器数组下标，回调函数会覆盖全局回调函数
     *
     * @param string $range 【块选择器】：指 先按照规则 选出 几个大块 ，然后再分别再在块里面 进行相关的选择
     * @param string $outputEncoding【输出编码格式】指要以什么编码输出 (UTF-8,GB2312,.....)，防止出现乱码,如果设置为 假值 则不改变原字符串编码
     * @param string $inputEncoding 【输入编码格式】明确指定输入的页面编码格式(UTF-8,GB2312,.....)，防止出现乱码,如果设置为 假值 则自动识别
     * @param bool|false $removeHead 【是否移除页面头部区域】 乱码终极解决方案
     * @return mixed
     */
    public static function Query($page, array $rules, $range = '', $outputEncoding = null, $inputEncoding = null, $removeHead = false)
    {
        return self::getInstance()->_query($page, $rules, $range, $outputEncoding, $inputEncoding, $removeHead);
    }

    /**
     * 运行QueryList扩展
     * @param $class
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public static function run($class, $args = array())
    {
        $extension = self::getInstance("QL\\Ext\\{$class}");
        return $extension->run($args);
    }

    /**
     * 日志设置
     * @param $handler
     */
    public static function setLog($handler)
    {
        if (class_exists('Monolog\Logger')) {
            if (is_string($handler)) {
                $handler = new StreamHandler($handler, Logger::INFO);
            }
            self::$logger = new Logger('QueryList');
            self::$logger->pushHandler($handler);
        } else {
            throw new Exception("You need to install the package [monolog/monolog]");

        }

    }

    /**
     * 获取任意实例
     * @return mixed
     * @throws Exception
     */
    public static function getInstance()
    {
        $args = func_get_args();
        count($args) || $args = array('QL\QueryList');
        $key = md5(serialize($args));
        $className = array_shift($args);
        if (!class_exists($className)) {
            throw new Exception("no class {$className}");
        }
        if (!isset(self::$instances[$key])) {
            $rc = new ReflectionClass($className);
            self::$instances[$key] = $rc->newInstanceArgs($args);
        }
        return self::$instances[$key];
    }

    /**
     * 获取目标页面源码(主要用于调试)
     * @param bool|true $rel
     * @return string
     */
    public function getHtml($rel = true)
    {
        return $rel ? $this->qpHtml : $this->html;
    }

    /**
     * 获取采集结果数据
     * @param callback $callback
     * @return array
     */
    public function getData($callback = null)
    {
        if (is_callable($callback)) {
            return array_map($callback, $this->data);
        }
        return $this->data;
    }

    /**
     * 重新设置选择器
     * @param $rules
     * @param string $range
     * @param string $outputEncoding
     * @param string $inputEncoding
     * @param bool|false $removeHead
     * @return QueryList
     */
    public function setQuery(array $rules, $range = '', $outputEncoding = null, $inputEncoding = null, $removeHead = false)
    {

        return $this->_query($this->html, $rules, $range, $outputEncoding, $inputEncoding, $removeHead);
    }

    private function _query($page, array $rules, $range, $outputEncoding, $inputEncoding, $removeHead)
    {
        $this->data = array();
        $this->page = $page;
        $this->html = $this->_isURL($this->page) ? $this->_request($this->page) : $this->page;
        $outputEncoding && $this->outputEncoding = $outputEncoding;
        $inputEncoding && $this->inputEncoding = $inputEncoding;
        $removeHead && $this->html = $this->_removeHead($this->html);
        $this->pqHtml = '';

        if (empty($this->html)) {
            $this->_log('The received content is empty!', 'error');
            trigger_error('The received content is empty!', E_USER_NOTICE);
        }
        //获取编码格式
        $this->htmlEncoding = $this->inputEncoding ? $this->inputEncoding : $this->_getEncode($this->html);
        // $this->html = $this->_removeTags($this->html,array('script','style'));
        $this->regArr = $rules;
        $this->regRange = $range;
        $this->_getList();
        return $this;
    }

    private function _getList()
    {
        $this->inputEncoding && phpQuery::$defaultCharset = $this->inputEncoding;
        $document = phpQuery::newDocumentHTML($this->html);
        $this->qpHtml = $document->htmlOuter();
        if (!empty($this->regRange)) {
            $robj = pq($document)->find($this->regRange);
            $i = 0;
            foreach ($robj as $item) {
                while (list($key, $reg_value) = each($this->regArr)) {
                    if ($key == 'callback') continue;
                    $tags = isset($reg_value[2]) ? $reg_value[2] : '';
                    $iobj = pq($item)->find($reg_value[0]);

                    switch ($reg_value[1]) {
                        case 'text':
                            $this->data[$i][$key] = $this->_allowTags(pq($iobj)->html(), $tags);
                            break;
                        case 'html':
                            $this->data[$i][$key] = $this->_stripTags(pq($iobj)->html(), $tags);
                            break;
                        default:
                            $this->data[$i][$key] = pq($iobj)->attr($reg_value[1]);
                            break;
                    }

                    if (isset($reg_value[3])) {
                        $this->data[$i][$key] = call_user_func($reg_value[3], $this->data[$i][$key], $key);
                    } else if (isset($this->regArr['callback'])) {
                        $this->data[$i][$key] = call_user_func($this->regArr['callback'], $this->data[$i][$key], $key);
                    }
                }
                //重置数组指针
                reset($this->regArr);
                $i++;
            }
        } else {
            while (list($key, $reg_value) = each($this->regArr)) {
                if ($key == 'callback') continue;
                $document = phpQuery::newDocumentHTML($this->html);
                $tags = isset($reg_value[2]) ? $reg_value[2] : '';
                $lobj = pq($document)->find($reg_value[0]);
                $i = 0;
                foreach ($lobj as $item) {
                    switch ($reg_value[1]) {
                        case 'text':
                            $this->data[$i][$key] = $this->_allowTags(pq($item)->html(), $tags);
                            break;
                        case 'html':
                            $this->data[$i][$key] = $this->_stripTags(pq($item)->html(), $tags);
                            break;
                        default:
                            $this->data[$i][$key] = pq($item)->attr($reg_value[1]);
                            break;
                    }

                    if (isset($reg_value[3])) {
                        $this->data[$i][$key] = call_user_func($reg_value[3], $this->data[$i][$key], $key);
                    } else if (isset($this->regArr['callback'])) {
                        $this->data[$i][$key] = call_user_func($this->regArr['callback'], $this->data[$i][$key], $key);
                    }

                    $i++;
                }
            }
        }
        if ($this->outputEncoding) {
            //编码转换
            $this->data = $this->_arrayConvertEncoding($this->data, $this->outputEncoding, $this->htmlEncoding);
        }
        phpQuery::$documents = array();
    }

    /**
     * URL请求
     * @param $url
     * @return string
     */
    private function _request($url)
    {
        if (function_exists('curl_init')) {
	        $ch = curl_init();
	        curl_setopt( $ch, CURLOPT_URL, $url );
	        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	        curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
	        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
	        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
	        curl_setopt( $ch, CURLOPT_REFERER, $url );
	        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	        curl_setopt( $ch, CURLOPT_USERAGENT,
		        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36' );
	        $result = curl_exec( $ch );
	        curl_close( $ch );
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $url);
//            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$this->RrndIP(), 'CLIENT-IP:'.$this->RrndIP()));
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
//            curl_setopt($ch, CURLOPT_REFERER, $url);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_USERAGENT, $this->randAgent());
//            $result = curl_exec($ch);
//            curl_close($ch);

        } elseif (version_compare(PHP_VERSION, '5.0.0') >= 0) {
            $opts = array(
                'http' => array(
                    'header' => "Referer:{$url}"
                )
            );
            $result = file_get_contents($url, false, stream_context_create($opts));
        } else {
            $result = file_get_contents($url);
        }
        return $result;
    }

    public function RrndIP()
    {

        $ip2id = round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
        $ip3id = round(rand(600000, 2550000) / 10000);
        $ip4id = round(rand(600000, 2550000) / 10000);
        //下面是第二种方法，在以下数据中随机抽取
        $arr_1 = array("218", "218", "66", "66", "218", "218", "60", "60", "202", "204", "66", "66", "66", "59", "61", "60", "222", "221", "66", "59", "60", "60", "66", "218", "218", "62", "63", "64", "66", "66", "122", "211");
        $randarr = mt_rand(0, count($arr_1) - 1);
        $ip1id = $arr_1[$randarr];
        return $ip1id . "." . $ip2id . "." . $ip3id . "." . $ip4id;
    }

    public function randAgent()
    {
        $agentarry = [
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
            'Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; en) Presto/2.8.131 Version/11.11',
            'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.8.131 Version/11.11',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)',
            'Opera/9.80 (Windows NT 5.1; U; zh-cn) Presto/2.9.168 Version/11.50',
            'Mozilla/5.0 (Windows NT 5.1; rv:5.0) Gecko/20100101 Firefox/5.0',
            'Mozilla/5.0 (Windows NT 5.2) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.11 TaoBrowser/2.0 Safari/536.11',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.71 Safari/537.1 LBBROWSER',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; LBBROWSER)',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; 360SE)',
            'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 SE 2.X MetaSr 1.0',
            'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; SE 2.X MetaSr 1.0)',
            'Opera/9.27 (Windows NT 5.2; U; zh-cn)',
            'Opera/8.0 (Macintosh; PPC Mac OS X; U; en)',
            'Mozilla/5.0 (Macintosh; PPC Mac OS X; U; en) Opera 8.0',
            'Mozilla/5.0 (Windows; U; Windows NT 5.2) Gecko/2008070208 Firefox/3.0.1',
            'Mozilla/5.0 (Windows; U; Windows NT 5.1) Gecko/20070309 Firefox/2.0.0.3',
            'Mozilla/5.0 (Windows; U; Windows NT 5.1) Gecko/20070803 Firefox/1.5.0.12',
            'Mozilla/4.0 (compatible; MSIE 12.0',
            'Mozilla/5.0 (Windows NT 5.1; rv:44.0) Gecko/20100101 Firefox/44.0',
            "Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_3 like Mac OS X) AppleWebKit/603.3.8 (KHTML, like Gecko) Mobile/14G60 MicroMessenger/6.5.18 NetType/WIFI Language/en",
            "Mozilla/5.0 (Linux; U; Android 7.1.2; zh-cn; MI 5X Build/N2G47H) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.146 Mobile Safari/537.36 XiaoMi/MiuiBrowser/9.2.2",
            "Mozilla/5.0 (Linux; U; Android 6.0.1; zh-cn; NX531J Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/37.0.0.0 MQQBrowser/6.8 Mobile Safari/537.36",
            "Mozilla/5.0 (Linux; U; Android 6.0.1; zh-CN; SM-C7000 Build/MMB29M) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/40.0.2214.89 UCBrowser/11.6.2.948 Mobile Safari/537.36",
            "Mozilla/5.0 (Linux; U; Android 7.0; zh-CN; SM-G9500 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/40.0.2214.89 UCBrowser/11.7.0.953 Mobile Safari/537.36",
            "Mozilla/5.0 (Linux; U; Android 7.0; zh-CN; PRA-AL00 Build/HONORPRA-AL00) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/40.0.2214.89 UCBrowser/11.7.0.953 Mobile Safari/537.36",
            "Mozilla/4.0 (compatible; MSIE 6.0; ) Opera/UCWEB7.0.2.37/28/999",
            "Mozilla/5.0 (Linux; U; Android 5.1.1; zh-cn; MI 4S Build/LMY47V) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/53.0.2785.146 Mobile Safari/537.36 XiaoMi/MiuiBrowser/9.1.3",
            "Mozilla/5.0 (Linux; U; Android 7.1.1; zh-CN; OPPO R11 Build/NMF26X) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/40.0.2214.89 UCBrowser/11.7.0.953 Mobile Safari/537.36",
            "Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_3 like Mac OS X) AppleWebKit/603.3.8 (KHTML, like Gecko) Mobile/14G60 MicroMessenger/6.5.7 NetType/WIFI Language/zh_CN",
            "Mozilla/5.0 (Linux; Android 5.1; m3 note Build/LMY47I; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/48.0.2564.116 Mobile Safari/537.36 T7/9.3 baiduboxapp/9.3.0.10 (Baidu; P1 5.1)",
            "Mozilla/5.0 (Linux; U; Android 7.0; zh-CN; SM-G9550 Build/NRD90M) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/40.0.2214.89 UCBrowser/11.7.0.953 Mobile Safari/537.36",
        ];
        return $agentarry[array_rand($agentarry, 1)];
    }

    /**
     * 移除页面head区域代码
     * @param $html
     * @return mixed
     */
    private function _removeHead($html)
    {
        return preg_replace('/<head.+?>.+<\/head>/is', '<head></head>', $html);
    }

    /**
     * 获取文件编码
     * @param $string
     * @return string
     */
    private function _getEncode($string)
    {
        return mb_detect_encoding($string, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
    }

    /**
     * 转换数组值的编码格式
     * @param array $arr
     * @param string $toEncoding
     * @param string $fromEncoding
     * @return array
     */
    private function _arrayConvertEncoding($arr, $toEncoding, $fromEncoding)
    {
        eval('$arr = ' . iconv($fromEncoding, $toEncoding . '//IGNORE', var_export($arr, TRUE)) . ';');
        return $arr;
    }

    /**
     * 简单的判断一下参数是否为一个URL链接
     * @param string $str
     * @return boolean
     */
    private function _isURL($str)
    {
        if (preg_match('/^http(s)?:\\/\\/.+/', $str)) {
            return true;
        }
        return false;
    }

    /**
     * 去除特定的html标签
     * @param string $html
     * @param string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    private function _stripTags($html, $tags_str)
    {
        $tagsArr = $this->_tag($tags_str);
        $html = $this->_removeTags($html, $tagsArr[1]);
        $p = array();
        foreach ($tagsArr[0] as $tag) {
            $p[] = "/(<(?:\/" . $tag . "|" . $tag . ")[^>]*>)/i";
        }
        $html = preg_replace($p, "", trim($html));
        return $html;
    }

    /**
     * 保留特定的html标签
     * @param string $html
     * @param string $tags_str 多个标签名之间用空格隔开
     * @return string
     */
    private function _allowTags($html, $tags_str)
    {
        $tagsArr = $this->_tag($tags_str);
        $html = $this->_removeTags($html, $tagsArr[1]);
        $allow = '';
        foreach ($tagsArr[0] as $tag) {
            $allow .= "<$tag> ";
        }
        return strip_tags(trim($html), $allow);
    }

    private function _tag($tags_str)
    {
        $tagArr = preg_split("/\s+/", $tags_str, -1, PREG_SPLIT_NO_EMPTY);
        $tags = array(array(), array());
        foreach ($tagArr as $tag) {
            if (preg_match('/-(.+)/', $tag, $arr)) {
                array_push($tags[1], $arr[1]);
            } else {
                array_push($tags[0], $tag);
            }
        }
        return $tags;
    }

    /**
     * 移除特定的html标签
     * @param string $html
     * @param array $tags 标签数组
     * @return string
     */
    private function _removeTags($html, $tags)
    {
        $tag_str = '';
        if (count($tags)) {
            foreach ($tags as $tag) {
                $tag_str .= $tag_str ? ',' . $tag : $tag;
            }
            phpQuery::$defaultCharset = $this->inputEncoding ? $this->inputEncoding : $this->htmlEncoding;
            $doc = phpQuery::newDocumentHTML($html);
            pq($doc)->find($tag_str)->remove();
            $html = pq($doc)->htmlOuter();
            $doc->unloadDocument();
        }
        return $html;
    }

    /**
     * 打印日志
     * @param string $message
     * @param string $level
     */
    private function _log($message = '', $level = 'info')
    {
        if (!is_null(self::$logger)) {
            $url = $this->_isURL($this->page) ? $this->page : '[html]';
            $count = count($this->data);
            $level = empty($level) ? ($count ? 'info' : 'warning') : $level;
            $message = empty($message) ? ($count ? 'Get data successfully' : 'Get data failed') : $message;
            self::$logger->$level($message, array(
                'page' => $url,
                'count' => $count
            ));
        }
    }
}

/*
class Autoload
{
    public static function load($className)
    {
        $files = array(
            sprintf('%s/extensions/%s.php',__DIR__,$className),
            sprintf('%s/extensions/vendors/%s.php',__DIR__,$className)
        );
        foreach ($files as $file) {
            if(is_file($file)){
                require $file;
                return true;
            }
        }
        return false;
    }
}

spl_autoload_register(array('Autoload','load'));

*/

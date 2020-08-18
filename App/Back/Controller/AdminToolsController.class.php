<?php


namespace Back\Controller;

use Common\Lib\Redis;
use QL\QueryList;

/**
 * 管理员工具
 *
 * Class AdminTools
 * @package Back\Controller
 */
class AdminToolsController extends CommonController
{
    private $search = array(
        'C71',
        'C72',
        'C73',
        'C74',
        'C75',
        'C76',
        'C77',
        'C78',
        'C79',
        'C80',
        'C81',
        'C82',
        'C83',
        'C84',
        'C85',
        'C86',
        'C87',
        'C88',
        'C89',
        'C90',
        'C91',
        'C92',
        'C93',
        'C94',
        'C95',
        'C96',
        '83838356',
        'COMIC1☆6',
        'COMIC1☆7',
        'COMIC1☆8',
        'COMIC1☆9',
        'COMIC1☆10',
        'COMIC1☆11',
        'COMIC1☆12',
        'COMIC1☆13',
        'COMIC1☆14',
        'COMIC1☆15',
        '禁漫漢化組',
        '臉腫漢化組',
        '3D全彩',
        '無修',
        '/',
        '40010試作型',
        '中國',
        '同人誌',
        '同人CG集',
        '沒有漢化',
        '無毒漢化組',
        '空気系☆漢化',
        '紳士倉庫漢化',
        '1月號',
        '2月號',
        '3月號',
        '4月號',
        '5月號',
        '6月號',
        '7月號',
        '8月號',
        '9月號',
        '10月號',
        '11月號',
        '12月號',
        '2013年',
        '2014年',
        '2015年',
        '2016年',
        '2017年',
        '2018年',
        '2019年',
        '成年コミック',
        '(アズールレーン)',
        '[中]',
        '彩画堂',
        '木谷椎',
        '白姬漢化組',
        'DL版',
        '臉腫漢化组',
        '中国翻訳',
        '中文',
        '(中文) ',
        '(同人誌)',
        '40010壱號',
        '(同人CG集) ',
        '無邪気漢化組',
        'M&amp;U',
        '&amp;',
        'nbsp;',
        'final個人漢化',
        '無修正',
        '空気系★漢化',
        '嗶哢嗶哢漢化組',
        '2DJ漢化組',
        '4K掃圖組',
        '風的工房',
        'CE家族社',
        'CE漢化組',
        '彩畫堂',
        '中國語',
        '翻訳',
        'DL',
        '瑞樹漢化',
        '脸肿汉化组',
        '千易夏河崎個人漢化',
        'final個人漢化',
        '3D漢化',
        'nhz個人漢化',
        '臉腫漢化組',
        '紳士倉庫漢化',
        '漢化',
        'genesis漢化',
        '祐希堂漢化組',
        '夢之行蹤漢化組',
        '白姬漢化',
        '琉璃神社漢化x清純突破漢化',
        '嗶哢嗶哢漢化',
        '萌即正義漢化',
        '櫻丘漢化組',
        '渣橙子個人漢化',
        '白姬漢化組',
        '白姬漢化組',
        '水土不服漢化組',
        '兔司姬漢化組',
        '無邪気漢化',
        '臉腫漢化',
        '夢之行蹤漢化',
        '禁漫漢化',
        '禁漫漢化',
        '禁漫漢化組',
    );
    private $search2 = array('[]', '()', '【】', '（）');

    public function __construct()
    {
        parent::__construct();
    }


    public function index()
    {
        $this->display();
    }

    //检查数据入库
    public function insertSql()
    {
        $str = file_get_contents('http://127.0.0.1:86/local_book.json');
        $jsonArr = json_decode($str, true);
        $error = '';
        unset($jsonArr[count($jsonArr) - 1]);
        foreach ($jsonArr as $item) {
            $isExist = M('comic_check_local_page')->where('local_id = ' . $item['local_id'])->find();
            if (!$isExist) {
                $add = array(
                    'local_id' => $item['local_id'],
                    'local_page' => $item['local_page'],
                );
                $res = M('comic_check_local_page')->add($add);
                if (!$res) {
                    echo $item['local_id'] . '插入失败<br/>';
                }
            }else{
                echo  $item['local_id'] . '已经存在<br/>';;
            }
        }
        //$this->ajaxReturn(array('code' => 200, 'msg' => $error));
    }


    //更新为数据库中的页码
    public function checkSqlPage()
    {
        $list = M('comic_check_local_page')->select();
        foreach ($list as $item) {
            $count = M('comic_chapter')->where('source = ' . $item['local_id'])->find();
            if ($count) {
                if ($count['source_size'] == $item['local_page']) {
                    $save = array(
                        'sql_page' => $count['source_size'],
                        'status' => 1
                    );
                } else {
                    $save = array(
                        'sql_page' => $count['source_size'],
                        'status' => 2
                    );
                }
                M('comic_check_local_page')->where('local_id = ' . $item['local_id'])->save($save);
            }
        }
    }

    /**
     *
     */
    public function getLostPage()
    {
        $Comic = new  GetComicController();

        $item = M('comic_check_local_page')->where('status = 2')->find();
        $local_id = $item['local_id'];
        $fileList = scandir(ROOT_PATH . 'Comic/' . $local_id);
        unset($fileList[0]);
        unset($fileList[1]);
        $c = count($fileList);

        if ($c != $item['sql_page']) {
            $data = $Comic->getImgLink($local_id);

            if ($data['code'] == 200 && $data['data']) {
                $str = '';
                foreach ($data['data'] as $val) {
                    $img = str_replace('album_photo_', '', $val['total_page']);
                    if ($img && !in_array($img, $fileList)) {
                        $str .= 'https://cdn-msp.msp-comic1.xyz/media/photos/' . $local_id . '/' . $img . "\n";
                    }
                }
                if ($str) {
                    $h = fopen(ROOT_PATH . 'Download/' . $local_id . '.txt', 'w+');
                    $res = fwrite($h, $str);
                    fclose($h);
                    if ($res) {
                        M('comic_check_local_page')->where('local_id = ' . $local_id)->save(array('status' => 3));
                        $this->ajaxReturn(array('code' => 200, 'msg' => '成功'));
                    } else {
                        $this->ajaxReturn(array('code' => 0, 'msg' => '失败'));
                    }
                } else {
                    M('comic_check_local_page')->where('local_id = ' . $local_id)->save(array('status' => 4));
                    $this->ajaxReturn(array('code' => 0, 'msg' => '没有需要下载的图片'));
                }
            } else {
                M('comic_check_local_page')->where('local_id = ' . $local_id)->save(array('status' => 5));
                $this->ajaxReturn(array('code' => 0, 'msg' => '获取数据失败'));
            }

        } else {
            M('comic_check_local_page')->where('local_id = ' . $local_id)->save(array('status' => 1));
            $this->ajaxReturn(array('code' => 200, 'msg' => '已下载'));
        }
    }

    /**
     *
     */
    public function checkLocal()
    {
        $list = M('comic_local_check')->where('status = 0')->select();
        foreach ($list as $value) {
            $isExist = M('comic_chapter')->where('source = ' . $value['local_id'])->find();
            if ($isExist) {
                M('comic_local_check')->where('local_id = ' . $value['local_id'])->save(array('status' => 1));
            } else {
                M('comic_local_check')->where('local_id = ' . $value['local_id'])->save(array('status' => 2));
            }
        }
    }

    public function inSql()
    {
        $item = M('comic_local_check')->where('status = 2')->find();
        $isExist = M('comic_chapter')->where('source = ' . $item['local_id'])->find();
        if (!$isExist) {
            $Comic = new GetComicController();
            $data = $Comic->getComicData($item['local_id']);
            $search = $this->search;
            $search2 = $this->search2;
            if ($data['data']) {
                if ($data['data']['list']) {
                    $titleTemp = str_replace($search, '', $data['data']['name']);
                    $title = str_replace($search2, '', $titleTemp);
                    $bookData = array(
                        'source' => $item['local_id'],
                        'name' => $data['data']['name'],
                        'title' => $title,
                        'tags' => $data['data']['tags'],
                        'editor_note' => $data['data']['editor_note'],
                        'author' => $data['data']['author'],
                        'desc' => $data['data']['desc'],
                        'add_time' => time(),
                        'status' => 5,
                    );
                    $addBooks = M('comic_lists')->add($bookData);
                    if ($addBooks) {
                        $addData = array();
                        foreach ($data['data']['list'] as $val) {
                            $temp = array(
                                'pid' => $addBooks,
                                'source' => $val['source'],
                                'title' => $val['title'],
                                'status' => 5,
                                'add_time' => time()
                            );
                            $addData[] = $temp;
                        }
                        $addChapter = M('comic_chapter')->addAll($addData);
                        $save = M('comic_local_check')->where('local_id = ' . $item['local_id'])->save(array('status' => 1));
                        $this->ajaxReturn(array('code' => 200, 'msg' => '入库成功'));
                    } else {
                        $this->ajaxReturn(array('code' => 500, 'msg' => '入库失败'));
                    }
                } else {
                    $titleTemp = str_replace($search, '', $data['data']['name']);
                    $title = str_replace($search2, '', $titleTemp);
                    $bookData = array(
                        'source' => $item['local_id'],
                        'name' => $data['data']['name'],
                        'title' => $title,
                        'tags' => $data['data']['tags'],
                        'editor_note' => $data['data']['editor_note'],
                        'author' => $data['data']['author'],
                        'desc' => $data['data']['desc'],
                        'add_time' => time(),
                        'status' => 5,
                    );
                    $addBooks = M('comic_lists')->add($bookData);
                    if ($addBooks) {
                        $addData = array();
                        foreach ($data['data']['list'] as $val) {
                            $addData = array(
                                'pid' => $addBooks,
                                'source' => $val['source'],
                                'title' => $val['title'],
                                'status' => 5,
                                'add_time' => time()
                            );
                        }
                        $addChapter = M('comic_chapter')->add($addData);
                        $save = M('comic_local_check')->where('local_id = ' . $item['local_id'])->save(array('status' => 1));
                        $this->ajaxReturn(array('code' => 200, 'msg' => '入库成功'));
                    }
                }
            } else {
                M('comic_local_check')->where('local_id = ' . $item['local_id'])->save(array('status' => 3));
                $this->ajaxReturn(array('code' => 200, 'msg' => '数据获取失败'));
            }

        } else {
            M('comic_local_check')->where('local_id = ' . $item['local_id'])->save(array('status' => 1));
            $this->ajaxReturn(array('code' => 200, 'msg' => '数据已存在'));
        }
    }

    /**
     * achive入库
     */
    public function addNew()
    {
        //查找数据
        $query_id = M('comic_achieve')->where('status = 0')->getField('query_id');
        //采集数据
        $Comic = new GetComicController();
        $res = $Comic->getComicData($query_id);
        //数据正常获取
        if ($res['data']) {
            $comicData = $res['data'];
            //未入库
            $isExist = M('comic_lists')->where('source = ' . $query_id)->find();
            //设置PID
            if (!$isExist) {
                $addBooksRes = M('comic_lists')->add($comicData);
                if ($addBooksRes) {
                    $pid = $addBooksRes;
                }
            } else {
                $pid = $isExist['id'];
            }

            //列表与单本区分
            if ($comicData['list']) {
                foreach ($comicData['list'] as $item) {
                    $isExistCha = M('comic_chapter')->where('source = ' . $item['source'])->find();
                    if (!$isExistCha) {
                        $addCha = array(
                            'pid' => $pid,
                            'source' => $item['source'],
                            'title' => $item['title'],
                            'status' => 0,
                            'add_time' => time()
                        );
                        $addRes = M('comic_chapter')->add($addCha);
                    }
                }
                M('comic_achieve')->where('query_id = ' . $query_id)->save(array('status' => 1));
                if ($addRes) {
                    $this->ajaxReturn(array('code' => 200, 'msg' => '列表单项新增成功'));
                } else {
                    $this->ajaxReturn(array('code' => 200, 'msg' => '列表单项新增失败'));
                }
            } else {
                $isExistCha = M('comic_chapter')->where('source = ' . $comicData['source'])->find();
                if (!$isExistCha) {
                    $addCha = array(
                        'pid' => $pid,
                        'source' => $comicData['source'],
                        'title' => $comicData['name'],
                        'status' => 0,
                        'add_time' => time()
                    );
                    $addRes = M('comic_chapter')->add($addCha);
                    M('comic_achieve')->where('query_id = ' . $query_id)->save(array('status' => 1));
                    if ($addRes) {
                        $this->ajaxReturn(array('code' => 200, 'msg' => '单本新增成功'));
                    } else {
                        $this->ajaxReturn(array('code' => 200, 'msg' => '单本新增失败'));
                    }
                } else {
                    M('comic_achieve')->where('query_id = ' . $query_id)->save(array('status' => 1));
                    $this->ajaxReturn(array('code' => 200, 'msg' => '数据已存在'));
                }
            }
        } else {
            M('comic_achieve')->where('query_id = ' . $query_id)->save(array('status' => 5));
            $this->ajaxReturn(array('code' => 0, 'msg' => '获取数据失败'));
        }
    }

    /**
     * 更新页码
     */
    public function updatePage()
    {
        $chapter = M('comic_chapter')->where('status = 0 and source_size < 500')->order(array('source_size' => 'asc'))->find();
        $COMIC = new GetComicController();
        $res = $COMIC->getPage($chapter['source']);
        if ($res) {
            $comicData = $res;
            $save = array(
                'source_size' => $comicData['total_page'],
                'status' => 1,
                'update_time' => time()
            );
            $chapter = M('comic_chapter')->where('source = ' . $chapter['source'])->save($save);
            $this->ajaxReturn(array('code' => 200, 'msg' => '页码更新成功'));
        } else {
            $save = array(
                'status' => 3
            );
            $chapter = M('comic_chapter')->where('source = ' . $chapter['source'])->save($save);
            $this->ajaxReturn(array('code' => 200, 'msg' => '页码更新失败'));
        }
    }

    /**
     * 遍历文件夹
     *
     * @param $files
     */
    private function list_file($files)
    {
        //1、首先先读取文件夹
        $temp = scandir($files);
        //遍历文件夹
        foreach ($temp as $v) {
            $a = $files . '/' . $v;
            //如果是文件夹则执行
            if (is_dir($a)) {
                //判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
                if ($v == '.' || $v == '..') {
                    continue;
                }
                //把文件夹红名输出
                //echo "<font color='red'>$a</font>", "<br/>";

                //因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
                $this->list_file($a);
            } else {
                echo $a, "<br/>";
            }
        }
    }


    /**
     * redis 测试工具
     */
    public function redisTest()
    {
        $formData = I('get.');

        $val = $formData['val'];                         //方法的值
        $key = $formData['key'];                         //方法的Key
        $action = $formData['action'];                      //redis方法名
        $db = $formData['db'] ? $formData['db'] : 0;    //数据库编号

        $res = '';
        $redis = new Redis($db);
        switch ($action) {
            case 'zScore';
                $res = $redis->zScore($key, $val);
                break;
            case 'scard';
                $res = $redis->scard($key);
                break;
            case 'zget';
                $res = $redis->zget($key);
                break;
            case 'zRevRange';
                $res = $redis->zRevRange($key);
                break;
            case 'stringGet';
                $res = $redis->stringGet($val);
                break;
            case 'hGet';
                $res = $redis->hGet($key, $val);
                break;
            case 'smembers';
                $res = $redis->smembers($key);
                break;
        }

        $return['val'] = $val;
        $return['key'] = 'Redis Key值为：' . $key;
        $return['action'] = 'Redis 方法：' . $action;
        $return['res'] = $res;

        $this->ajaxReturn($return);
    }

    /**
     * 下载电影数据
     *
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function downToExcel()
    {
        $userInfo = session('user_info');
        if ($userInfo['id'] && $userInfo['username'] != 'admin') {
            $this->redirect('/Back/Comic');
        }

        $getData = I('get.');
        $moviesId = $getData['id'];
        //章节列表
        $chapter = M('chapter')->where('movies_id = ' . $moviesId)
            ->field('id,movies_id,name,sortrank,add_time')
            ->select();

        //解说列表
        $images = array();
        foreach ($chapter as $item) {
            $imagesData = M('chapter_image')->where('chapter_id = ' . $item['id'])
                ->field('id,chapter_id,reading,url,sortrank,add_time')
                ->select();
            $images = array_merge($images, $imagesData);
        }

        Vendor("phpexcel.Classes.PHPExcel");
        Vendor("phpexcel.Classes.PHPExcel.Writer.Excel2007");

        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);

        for ($i = 0; $i < 2; $i++) {
            if ($i > 0) {
                $objExcel->createSheet();
            }
            $objExcel->setactivesheetindex($i);
        }

        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle('章节列表');
        $objExcel->getActiveSheet()->setCellValue('A1', "id");
        $objExcel->getActiveSheet()->setCellValue('B1', "movies_id");
        $objExcel->getActiveSheet()->setCellValue('C1', "name");
        $objExcel->getActiveSheet()->setCellValue('D1', "sortrank");
        $objExcel->getActiveSheet()->setCellValue('E1', "add_time");
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $i = 2;
        foreach ($chapter as $key => $val) {
            $objExcel->getActiveSheet()->setCellValue('A' . $i, $val['id']);
            $objExcel->getActiveSheet()->setCellValue('B' . $i, $val['movies_id']);
            $objExcel->getActiveSheet()->setCellValue('C' . $i, $val['name']);
            $objExcel->getActiveSheet()->setCellValue('D' . $i, $val['sortrank']);
            $objExcel->getActiveSheet()->setCellValue('E' . $i, $val['add_time']);
            $i++;
        }

        $objExcel->setActiveSheetIndex(1);
        $objExcel->getActiveSheet()->setTitle('解说列表');
        $objExcel->getActiveSheet()->setCellValue('A1', "id");
        $objExcel->getActiveSheet()->setCellValue('B1', "chapter_id");
        $objExcel->getActiveSheet()->setCellValue('C1', "sortrank");
        $objExcel->getActiveSheet()->setCellValue('D1', "url");
        $objExcel->getActiveSheet()->setCellValue('E1', "reading");
        $objExcel->getActiveSheet()->setCellValue('F1', "status");
        $objExcel->getActiveSheet()->setCellValue('G1', "add_time");
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $j = 2;
        foreach ($images as $vo) {
            $objExcel->getActiveSheet()->setCellValue('A' . $j, $vo['id']);
            $objExcel->getActiveSheet()->setCellValue('B' . $j, $vo['chapter_id']);
            $objExcel->getActiveSheet()->setCellValue('C' . $j, $vo['sortrank']);
            $objExcel->getActiveSheet()->setCellValue('D' . $j, $vo['url']);
            $objExcel->getActiveSheet()->setCellValue('E' . $j, $vo['reading']);
            $objExcel->getActiveSheet()->setCellValue('F' . $j, $vo['status']);
            $objExcel->getActiveSheet()->setCellValue('G' . $j, $vo['add_time']);
            $j++;
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="ID：' . $moviesId . '电影数据.xls"');
        header("Content-Transfer-Encoding:binary");

        $objWriter->save('php://output');
    }


    /**
     * 加入检测表
     */
    public function addToCheck()
    {
        $tableList = array(
            'ice_comic_check_copy',
            'ice_comic_copy',
            'ice_comic_copy1',
            'ice_comic_copy2',
            'query_search_1',
            'query_search_2',
            'query_search_3'
        );
        $this->_addCheck($tableList);
    }

    /**
     * 入库
     *
     * @param $tableList
     *
     * @return array|string
     */
    private function _addCheck($tableList)
    {
        if (!$tableList) {
            return '';
        }

        $comicList = array();
        foreach ($tableList as $table) {
            $list = M($table)->where('db_id > 0')->select();
            $comicList = array_merge($comicList, $list);
        }


        $msg = '';
        foreach ($comicList as $item) {
            $res1 = M('ice_comic' . __COPY__)->where('db_id = ' . $item['db_id'])->find();
            $res2 = M('ice_comic_check_error' . __COPY__)->where('db_id = ' . $item['db_id'])->find();
            if (!$res1 && !$res2) {
                unset($item['id']);
                $save = $item;
                $save['desc'] = "";
                $save['status'] = 0;
                $save['add_time'] = 0;

                $addRes = M('ice_comic_check_new' . __COPY__)->add($save);
                if ($addRes) {
                    echo $item['db_id'] . "添加成功<br/>";
                } else {
                    echo $item['db_id'] . "添加失败<br/>";
                }
            } else {
                echo $item['db_id'] . "已存在<br/>";
            }
        }
    }

    /**
     * 检测图片数量
     *
     * @param $status
     */
    public function checkImgStatus()
    {
        $msg = '';
        //0：待检测 1:已检测，数据未下载 2:已检测,数据有异常 3:数据正常，可入库
        $list = M('ice_comic_check_new' . __COPY__)->where('db_id > 0')->select();
        //检测文件夹及文件数量
        foreach ($list as $key => $item) {
            echo $key . ":正在检测ID：" . $item['db_id'] . "<br/>";
            $path = __BKSER__ . $item['db_id'];
            $isExist = file_exists($path);
            if ($isExist) {
                echo $key . ":ID：" . $item['db_id'] . "已下载，检测图片数量<br/>";
                //计算文件数量，去掉"./"和"../"
                $count = intval(count(scandir($path))) - 2;
                if ($count != $item['total_page']) {
                    $saveData['desc'] = '图片数量异常';
                    $saveData['img_status'] = $count < $item['total_page'] ? '2' : '3';
                    $saveData['status'] = 2;
                    echo $key . ":ID：" . $item['db_id'] . "图片数量异常，状态码" . $saveData['img_status'] . "<hr/>";
                } else {
                    $saveData['desc'] = '处理完成';
                    $saveData['img_status'] = 1;
                    $saveData['status'] = 3;
                    echo $key . ":ID：" . $item['db_id'] . "图片正常<hr/>";
                }
            } else {
                $saveData['desc'] = '未下载';
                $saveData['img_status'] = 0;
                $saveData['status'] = 1;
                echo $key . ":ID：" . $item['db_id'] . "未下载<hr/>";
            }
            $saveData['add_time'] = time();

            $res = M('ice_comic_check_new' . __COPY__)->where('db_id = ' . $item['db_id'])->save($saveData);
            if (!$res) {
                $msg = $item['db_id'] . "更新失败\n";
            }
        }
    }


    /**
     * 入库操作
     */
    public function addComic()
    {
        $msg = '';
        $list = M('ice_comic_check_new' . __COPY__)->where('status = 3')->order(array('db_id' => 'asc'))->select();
        foreach ($list as $item) {
            $isExist = M('ice_comic' . __COPY__)->where('db_id = ' . $item['db_id'])->find();
            if (!$isExist) {
                unset($item['id']);
                $saveData = $item;
                $saveData['img_status'] = 1;
                $saveData['status'] = 3;
                $saveData['desc'] = '';
                $saveData['add_time'] = time();
                $res = M('ice_comic' . __COPY__)->add($saveData);
                if (!$res) {
                    echo $item['db_id'] . "入库失败<br/>";
                }
            }
        }
    }

    /**
     * 抓取数据并更新
     */
    public function updateCheckDate()
    {
        //采集方法
        $Comic = new GetComicController();
        //逐个更新，由前端控制
        $info = M('ice_comic_check_new' . __COPY__)->where('status = 4')->find();
        if (!$info) {
            $this->ajaxReturn(array('code' => 500, 'msg' => '无数据'));
        }

        $result = $Comic->getComic($info['db_id']);
        if ($result['code'] == 200) {
            $saveData = $result['data'];
            $saveData['status'] = 1;
            $saveData['img_status'] = 0;
            $saveData['add_time'] = time();

            $res = M('ice_comic_check_new' . __COPY__)->where('db_id = ' . $info['db_id'])->save($saveData);
            if ($res) {
                $this->ajaxReturn(array('code' => 200, 'id' => $info['db_id'], 'msg' => '更新成功'));
            } else {
                $this->ajaxReturn(array('code' => 0, 'id' => $info['db_id'], 'msg' => '更新失败'));
            }
        } else {
            $errData['status'] = 4;
            $errData['desc'] = '数据抓取失败';
            $saveData['add_time'] = time();

            $res = M('ice_comic_check_new' . __COPY__)->where('db_id = ' . $info['db_id'])->save($errData);
            $this->ajaxReturn(array('code' => 200, 'id' => $info['db_id'], 'msg' => '抓取数据失败'));
        }
    }

    public function checkNo()
    {
        $list = scandir(__BOOKS__);
        unset($list[0]);
        unset($list[1]);
        foreach ($list as $item) {
            $res = M('ice_comic')->where('db_id = ' . $item)->find();
            if (!$res) {
                print_r($item);
                echo "<br/>";
            }
        }
    }

    //重命名
    private function _rename($path)
    {
//		$path = './';
        $list = scandir($path);

        foreach ($list as $k => $dir) {
            if ($dir == '.' || $dir == '..' || $dir == 'index.php') {
                continue;
            }
            if (is_dir($dir)) {
                $sec_dir = scandir($dir);
                foreach ($sec_dir as $key => $item) {
                    if ($item == '.' || $item == '..' || $item == 'index.php') {
                        continue;
                    }
                    $page = str_pad(($key - 1), 5, 0, STR_PAD_LEFT);

                    $old = "./" . $dir . "/" . $item;
                    $new = "./" . $dir . "/" . $page . '.jpg';
                    print_r(rename($old, $new));
                    echo '<br/>';
                }
            } else {
                $page = str_pad(($k - 1), 5, 0, STR_PAD_LEFT);
                $old = "./" . $dir;
                $new = "./" . $page . '.jpg';
                print_r(rename($old, $new));
                echo '<br/>';
            }
        }
    }
}
<?php

/**
 * 。。。
 * @time         2019-4-30
 * @author       tsj
 * @version     1.0
 */

namespace Back\Model;

use Think\Model;

class MoviesModel extends Model {

    protected $_validate = array(
        array('name', 'require', '标题不能为空'),
        array('subtitle', 'require', '副标题不能为空'),
        array('author', 'IsAuthor', '作者不能为空', 1, 'callback'),
        array('director', 'require', '导演不能为空'),
        array('actor', 'require', '演员不能为空'),
        array('desc', 'require', '简介不能为空'),
        array('editor_note', 'require', '作者按不能为空'),
        array('tags', 'require', '标签不能为空'),
        array('tags', '/^[\x{4e00}-\x{9fa5}-|Za-z0-9]+$/u', '标签只能是中英文加上数字和符号|组成！'),
        array('score', 'require', '豆瓣评分不能为空'),
        array('total_size', 'require', '影片大小不能为空'),
        array('total_page', 'require', '图片总数不能为空'),
        array('begin_pay', 'require', '付费章节不能为空'),
        array('price', 'require', '价格不能为空'),
        array('overdate', 'IsOverdate', '完结时间不能为空', 1, 'callback'),
        array('form', 'IsChecked', '分类不能为空', 1, 'callback'),
    );

    /**
     * 自动完成规则
     * @var type 
     */
    protected $_auto = array(
        array('add_time', 'time', 1, 'function'),
        array('charging_time', 'chargingTime', 2, 'callback'),
        array('author', 'getAuthor', 3, 'callback'),
        array('author_id', 'IsSelectAuthor', 3, 'callback'),
        array('overdate', 'IsTime', 3, 'callback'),
        array('form', 'AssembleForm', 3, 'callback'), //组装form
    );

    /**
     * 验证作者
     */
    public function IsAuthor() {
        $author_type = I('author_type');
        $author_name = I('author');
        $authorid = I('author_id');
        if ($author_type == 1 && $author_name == '') {
            return false;
        } else if ($author_type == 2 && $authorid == '') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 验证完结时间
     * @return boolean
     */
    public function IsOverdate() {
        $is_over = I('is_over');
        $overdate = I('overdate');
        if ($is_over == 1 && $overdate == '') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 验证复选框
     */
    public function IsChecked() {
        $is_over = I('form');
        if (isset($is_over) && !empty($is_over)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取作者名称
     */
    public function getAuthor() {
        $author = I('author');
        $authorid = I('author_id');
        $author_type = I('author_type');
        if ($author_type == 1) {
            return $author;
        } else {
            $author_name = M('author')->where(array('id' => $authorid))->getField('nick_name');
            return $author_name;
        }
    }

    /**
     * 获取作者id
     * @return int
     */
    public function IsSelectAuthor() {
        $author_type = I('author_type');
        $authorid = I('author_id');
        if ($author_type == 1) {
            return 0;
        } else {
            return $authorid;
        }
    }

    /**
     * 完结时间获取
     * @return boolean
     */
    public function IsTime() {
        $is_over = I('is_over');
        $overdate = I('overdate');
        if ($is_over == 1) {
            return strtotime($overdate);
        } else {
            return 0;
        }
    }

    /**
     * 组装分类
     * @return string
     */
    public function AssembleForm() {
        $from = I("form");
        $newFrom = array_filter($from);
        if($newFrom){
            $jsonForm = '["' . join($newFrom, '","') . '"]';
            return $jsonForm;
        }else{
            return '';
        }
    }

    /**
     * 获取开启计费时间
     * @return int
     */
    public function chargingTime() {
        $time = I('charging_time');
        if ($time) {
            return strtotime($time);
        } else {
            return 0;
        }
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/26
 * Time: 14:45
 */
namespace Back\Model;
use Think\Model\ViewModel;
class MoviesViewModel extends ViewModel{
    protected $viewFields = array(
        'movies'=>array('id','subtitle','editor_note','director','actor','score','author_id','showtime_id','zone_id','tags','rank','total_size','total_page','name','org_name','form','author','cover','banner','`desc`','hunt','sex','hot','level','order_num','mold','begin_pay','price','status','add_time','overdate','img_status')
    );
}
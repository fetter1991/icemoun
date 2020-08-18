<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/9
 * Time: 14:37
 */

namespace Common\Model;
use Think\Model\ViewModel;

class TradeViewModel extends ViewModel{
    protected $viewFields = array(
        'trade'=>array('user_id','trade_no','pay','type','add_time','pay_status'),
        'user_info'=>array('nick_name','_type'=>'left','_on'=>'trade.user_id = user_info.user_id'),
        'user'=>array('channel_id','_type'=>'left','_on'=>'trade.user_id = user.id')
    );
}
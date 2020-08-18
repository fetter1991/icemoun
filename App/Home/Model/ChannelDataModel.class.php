<?php
namespace Home\Model;
use Think\Model;
class ChannelDataModel extends BaseModel {
    public $TYPE = array(
        //1进入次数，2 激活次数，3 充值次数，4 充值金额
        'Act' => 1, // 活跃量
        'Reg' => 2, // 新用户注册量
        'Pay_count' => 3, // 用户充值次数
        'Pay_money' => 4, // 用户充值金额
    );
    
    protected $_auto = array (
        array('add_time','getTime',Model::MODEL_INSERT,'callback'), // 对update_time字段在更新的时候写入当前时间戳
    );
	
}
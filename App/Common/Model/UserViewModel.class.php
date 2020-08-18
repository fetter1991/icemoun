<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/19
 * Time: 15:41
 */
namespace Common\Model;
use Think\Model\ViewModel;
class UserViewModel extends ViewModel{
    protected $viewFields =array(
        'user'=>array('id','is_follow','expand_id','add_time'),
        'user_info'=>array('nick_name','sex','avatar','total','gold','is_vip','_type'=>'LEFT','_on'=>'user.id = user_info.user_id'),
        'vip'=>array('vip_overdue','_type'=>'LEFT','_on'=>'user.id = vip.user_id'),
        'channel'=>array('id'=>'channel_id','nick_name'=>'channel','_type'=>'LEFT','_on'=>'channel.id = user.channel_id'),
        'expand'=>array('account'=>'expand','_type'=>'LEFT','_on'=>'expand.id = user.expand_id')
    );
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/26
 * Time: 14:45
 */
namespace Back\Model;
use Think\Model\ViewModel;
class UserArticleViewModel extends ViewModel{
    protected $viewFields = array(
        'user'=>array('id'=>'user_id'),
        'user_article'=>array('id','article_id','status','_type'=>'left','_on'=>'user.id = user_article.user_id'),
        'user_info'=>array('nick_name','_type'=>'left','_on'=>'user.id = user_info.user_id'),
        'article'=>array('name'=>'article','_type'=>'left','_on'=>'article.id = user_article.article_id'),
        'user_address'=>array('name','region','address','phone','_type'=>'left','_on'=>'user.id = user_address.user_id')
    );
}
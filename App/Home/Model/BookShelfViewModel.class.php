<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 14:12
 */
namespace Home\Model;
use Think\Model\ViewModel;
use Common\Model\BookModel;
class BookShelfViewModel extends ViewModel{
    protected $viewFields = array(
        'Bookshelf'=>array('id','user_id','type_id','add_time'=>'collect_time'),
        'novel'=>array('status','add_time','_type'=>'left','_on'=>'Bookshelf.type_id = novel.type_id'),
        'user_novel'=>array('time','_on'=>'Bookshelf.user_id = user_novel.user_id and Bookshelf.type_id = user_novel.type_id','_type'=>'left')
    );

    //获取书架
    public function getBookShelf($userid){
        $res = $this->where('Bookshelf.user_id = "'.$userid.'"')->order('time desc')->select();
        $book = new BookModel;

        foreach($res as $k=>$v){
            $novel = $book->getOneBook($v['type_id']);
            $map = array(
                'user_id'=>$userid,
                'type_id'=>$v['type_id']
            );
            $chaptar = $book->getArchives($v['type_id']);
            $aid = M('user_novel')->where($map)->getField('aid');
            if(empty($aid)){//如果还是为空说明是没有看过书就添加书架的
                $aid = $chaptar[0]['id'];
            }
            $chaptarId = array_column($chaptar,'id');
            $curve = array_search($aid,$chaptarId,true);
            $res[$k]['plan'] = sprintf("%.2f",$curve/$novel['count']*100);
            $res[$k]['name'] = $novel['name'];
            $res[$k]['desc'] = $novel['desc'];
            $res[$k]['author'] = $novel['author'];
            $res[$k]['icon'] = $novel['icon'];
            $res[$k]['overdate'] = $novel['overdate'];
        }

        return $res;
    }
}
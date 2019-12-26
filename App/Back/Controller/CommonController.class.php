<?php
namespace Back\Controller;
use Think\Controller;

class CommonController extends Controller {
    public function _initialize(){
        $user_id = session('user_id');
        if (empty($user_id)) {
            $login_url = U('Public/login');
            session('_pre_url_',U(CONTROLLER_NAME.'/'.ACTION_NAME,$_GET));
            redirect($login_url);
        }
        $status = M('admin')->where(['id'=>$user_id])->find();
        if($status['status'] != 1){
            $login_url = U('Public/loginOut');
            redirect($login_url);
        }
        $loginInfo = cookie('admin_last_login_time');
        if(empty($loginInfo) || empty($status['session_time']) || $loginInfo < $status['session_time']){
            session('user_id', null);
            session('user_info', null);
            cookie('admin_last_login_time',null);
            cookie('loginUser',null);
            $this->redirect('Public/login');
            return ;
        }
        
    }
    
    /**
     * @param string $model_name
     * @param array $map
     * @param string $order
     * @param string $page_size
     */
    protected function _list($model_name, $map=array(), $order = '', $page_size = 0){
        $Model = D($model_name);
        //取得满足条件的记录数
        $count = $Model->where($map)->count('1');
        if ($count > 0) {
            import('Common.Lib.Page');
            $page = new \Common\Page($count,20);
            $voList = $Model->where($map)->order($order)->limit($page->firstRow,$page->listRows)->select();
            $this->assign('list', $voList);
            //var_dump($voList);die;
            $this->assign('page',$page->show());
        }
    }
    
    protected function _add($model_name,$data=''){
        $Model = D($model_name);
        $data = $Model->create($data);
        if(false === $data || false === $Model->add($data)) {
           return $Model->getError();
        }
        return true;
    }


    protected function getMenu(array $items, $id = 'id', $pid = 'pid', $son = 'children')
    {
        $tree = array();
        $tmpMap = array();

        foreach ($items as $item) {
            $tmpMap[$item[$id]] = $item;
        }
        foreach ($items as $item) {
            if (isset($tmpMap[$item[$pid]])) {
                $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
            } else {
                $tree[] = &$tmpMap[$item[$id]];
            }
        }
        return $tree;
    }
    
}
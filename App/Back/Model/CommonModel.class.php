<?php
namespace Back\Model;
use Think\Model;
class CommonModel extends Model {
    
    /**
     * 判断密码是否合法
     * @param $v
     * @return bool|int
     */
	public function checkPwd($v) {
        if(empty($v))
            return false;
        return preg_match(C('ZZ_MATCH.PWD'),$v);
    }
    
    /**
     * 获取加密串。
     * @param $pwd
     * @return string
     */
    public function getPwd($pwd) {
	    return md5(md5($pwd).C('APPC_KEY.PWD'));
    }
    
	
	public function getTime(){
		return time();
	}
	
}
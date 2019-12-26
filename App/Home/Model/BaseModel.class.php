<?php
namespace Home\Model;
use Think\Model;
class BaseModel extends Model {
    
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
    
    /**
     * 对数据库字段自增，如果不存在该条记录，则插入记录
     * @param $field
     * @param $data
     * @param int $step
     */
	public function setIncPlus($field,$data,$step=1){
	    $one = $this->where($data)->find();
	    if (empty($one)) {
            $data[$field] = $step;
            $data = $this->create($data);
            if ($data !== false) {
                $this->add($data);
            }
        } else {
	        $this->where(array('id'=>$one['id']))->setInc($field,$step);
        }
	    
    }
	
}
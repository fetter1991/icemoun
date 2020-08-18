<?php
namespace Back\Model;
use Think\Model;
class AdminNavModel extends Model {
	protected $_validate = array(
        array('name','require','昵称必须', Model::MODEL_INSERT),
	);
}
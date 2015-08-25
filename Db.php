<?php
namespace Baiy;

/**
 * 简单数据库操作封装
 * @example
 * <?php
 * $Db = new Db(array('host'=>'','user'=>'','password'=>'','dbname'=>'','port'=>'','charset'=>''))
 * ?>
 */
class Db extends \mysqli {
	public function __construct($config) {
		$config['host']     = !isset($config['host']) ? '127.0.0.1' : $config['host'];
		$config['user']     = !isset($config['user']) ? 'root' : $config['user'];
		$config['password'] = !isset($config['password']) ? 'root' : $config['password'];
		$config['port']     = !isset($config['port']) ? '3306' : $config['port'];
		$config['charset']  = !isset($config['charset']) ? 'utf-8' : $config['charset'];

		if (!isset($config['dbname'])) {
			throw new Exception('数据库名称不能为空');
		}

		parent::__construct($config['host'], $config['user'], $config['password'], $config['dbname'], $config['port']);
		if ($this->connect_error) {
			throw new Exception($config['dbname'] . ' 连接错误 ' . $this->connect_errno . ' ' . $this->connect_error);
		}
		$this->set_charset($config['charset']);
	}

	/**
	 * 多条查询
	 * @param string $sql SQL语句
	 * @param string $key 返回数据索引
	 * @return array 数据结果集
	 */
	public function DbSelect($sql, $key = '') {
		$result = $this->query($sql);
		if ($result === false) {
			throw new Exception('数据查询错误:[sql:' . $sql . ']' . $this->error);
		}
		$lists = [];
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
			if (!empty($key)) {
				$lists[$row[$key]] = $row;
			} else {
				$lists[] = $row;
			}
		}
		$result->free();
		return $lists;
	}

	/**
	 * 单条查询
	 * @param string $sql SQL查询语句
	 * @return array 结果集
	 */
	public function DbGetOne($sql) {
		$lists = $this->DbSelect($sql);
		return $lists[0];
	}

	/**
	 * 数据库插入(支持批量)
	 * @param array $lists 出入数据数组
	 * @param string $table 表名
	 * @return insert_id
	 */
	public function DbInsert($lists, $table) {
		$info = [];
		if (!isset($lists[0]) || !is_array($lists[0])) {
			$info[] = $lists;
		} else {
			$info = $lists;
		}

		$key    = array_keys($info[0]);
		$key    = '(`' . implode('`,`', $key) . '`)';
		$values = [];
		foreach ($info as $var) {
			$values[] = "('" . implode("','", array_map('addslashes', $var)) . "')";
		}
		$value = implode(',', $values);

		$sql = "INSERT INTO `" . $table . "` " . $key . " VALUES " . $value;
		$this->query($sql);
		return $this->insert_id;
	}
}
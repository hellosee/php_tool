<?php
// +----------------------------------------------------------------------
// | 数据库操作类
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org>
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------
namespace Baiy;

use Baiy\Mysql\Drive;

/**
 * $db = new \Baiy\Mysql($config);
 * ============配置参数============
 * array(
 *    "host"=>'127.0.0.1',
 *    "user"=>'root',
 *    "dbname"=>'',
 *    "password"=>'root',
 *    "port"=>'3306',
 *    "charset"=>'utf-8',
 *    "debug"=>true
 * )
 * ===========使用方法=============
 * 查询:$db->table->field()->where()->group()->having()->order()->limit()->key()->[select|find|count]();
 * 添加:$db->table->data()->[insert|replace]();
 * 更新:$db->table->where()->data()->[update]();
 * 删除:$db->table->where()->delete();
 * 执行SQL:$db->query();
 * 事务:$db->startTrans();$db->commit();$db->rollback();
 * 其他:$db->getLastSql();$db->getError();
 * 注意:where方法禁止叠加使用
 */
class Mysql
{

    /**
     * 当前数据库操作对象
     *
     * @var mysql
     */
    public $db = null;

    /**
     * 数据库配置
     *
     * @var array
     */
    public $config = [];
    /**
     * sql语句，主要用于输出构造成的sql语句
     *
     * @var string
     */
    public $sql = '';

    /**
     * 数据信息
     *
     * @var array
     */
    private $data = [];

    /**
     * 查询表达式参数
     *
     * @var array
     */
    private $options = [];

    /**
     * 错误信息
     *
     * @var string
     */
    private $error = '';

    /**
     * 模型初始化
     *
     * @param array $config 数据库配置
     * @param array $table  表名
     */
    public function __construct($config = [])
    {
        $config['host']     = !isset($config['host']) ? '127.0.0.1' : $config['host'];
        $config['user']     = !isset($config['user']) ? 'root' : $config['user'];
        $config['dbname']   = !isset($config['dbname']) ? '' : $config['dbname'];
        $config['password'] = !isset($config['password']) ? 'root' : $config['password'];
        $config['port']     = !isset($config['port']) ? '3306' : $config['port'];
        $config['charset']  = !isset($config['charset']) ? 'utf-8' : $config['charset'];
        $config['debug']    = !isset($config['debug']) ? true : $config['debug'];

        if (!isset($config['dbname'])) {
            throw new \Exception('数据库名称不能为空');
        }
        $this->config = $config;
    }

    /**
     * 重载实现相关连贯操作
     * [field,data,where,group,having,order,limit]
     *
     * @param  string $method 方法名
     * @param  array  $args   参数
     */
    public function __call($method, $args)
    {
        $method = strtolower($method);
        if (in_array($method, ['table', 'field', 'data', 'where', 'group', 'having', 'order', 'limit', 'key'])) {
            $this->options[$method] = $args[0];
            return $this;
        } else {
            throw new \Exception($method . '方法 未定义');
        }
    }

    /**
     * 统计
     */
    public function count()
    {
        $data = $this->field('count(*)')->find();
        return intval($data['count(*)']);
    }

    /**
     * 单条数据
     * 返回关联数组
     */
    public function find()
    {
        $lists = $this->limit(1)->select();
        return empty($lists) ? [] : $lists[0];
    }

    /**
     * SELECT
     */
    public function select()
    {
        $select_sql = 'SELECT {FIELD} FROM {TABLE}{WHERE}{GROUP}{HAVING}{ORDER}{LIMIT}';
        $this->sql  = str_replace(
            ['{TABLE}', '{FIELD}', '{WHERE}', '{GROUP}', '{HAVING}', '{ORDER}', '{LIMIT}'],
            [
                $this->_parseTable(),
                $this->_parseField(),
                $this->_parseWhere(),
                $this->_parseGroup(),
                $this->_parseHaving(),
                $this->_parseOrder(),
                $this->_parseLimit(),
            ], $select_sql);

        $data  = [];
        $query = $this->query($this->sql);

        $key = $this->_parseKey();

        while ($row = $this->db->fetch_array($query)) {
            if ($key) {
                $data[$row[$key]] = $row;
            } else {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * table分析
     */
    private function _parseTable()
    {
        $table = isset($this->options['table']) ? $this->options['table'] : '';
        return $this->_filterFieldName($table);
    }

    /**
     * 字段和表名处理添加`和过滤
     *
     * @param string $key
     */
    private function _filterFieldName($key)
    {
        $key = trim($key);
        if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }

    /**
     * field分析
     */
    private function _parseField()
    {
        $field = isset($this->options['field']) ? $this->options['field'] : '';
        return empty($field) ? '*' : $field;
    }

    /**
     * where分析
     */
    private function _parseWhere()
    {
        $where     = isset($this->options['where']) ? $this->options['where'] : '';
        $condition = "";
        if (!empty($where)) {
            $condition = " WHERE ";
            if (is_string($where)) {
                $condition .= $where;
            } else {
                if (is_array($where)) {
                    foreach ($where as $key => $value) {
                        $condition .= " " . $this->_filterFieldName($key) . "=" . $this->_filterFieldValue($value) . " AND ";
                    }
                    $condition = substr($condition, 0, -4);
                } else {
                    $condition = "";
                }
            }
        }
        return $condition;
    }

    private function _filterFieldValue($value)
    {
        return '\'' . addslashes($value) . '\'';
    }

    /**
     * group分析
     */
    private function _parseGroup()
    {
        $group = isset($this->options['group']) ? $this->options['group'] : '';
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * having分析
     */
    private function _parseHaving()
    {
        $having = isset($this->options['having']) ? $this->options['having'] : '';
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * order分析
     *
     * @param string $order
     */
    private function _parseOrder()
    {
        $order = isset($this->options['order']) ? $this->options['order'] : '';
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * limit分析
     *
     * @param mixed $limit
     */
    private function _parseLimit()
    {
        $limit = isset($this->options['limit']) ? $this->options['limit'] : '';
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * 执行SQL语句
     *
     * @param  string $sql 需要执行的sql语句
     */
    public function query($sql)
    {
        if (empty($sql)) {
            return false;
        }

        // 清空SQL语句选项
        $this->options = [];

        $this->sql = $sql;
        try {
            $this->connect();
            return $this->db->query($this->sql);
        } catch (\Exception $e) {
            if ($this->config['debug'] !== false) {
                throw new \Exception($e->getMessage());
            } else {
                $this->error = $e->getMessage();
            }
        }
        return;
    }

    /**
     * 连接数据库
     */
    private function connect()
    {
        if (empty($this->db)) {
            $config   = $this->config;
            $this->db = new Drive($config['host'], $config['user'], $config['password'], $config['dbname'],
                $config['port'], $config['charset']);
        }
    }

    /**
     * key分析
     * select 返回数组所以字段名
     *
     * @param string $order
     */
    private function _parseKey()
    {
        $key = isset($this->options['key']) ? $this->options['key'] : '';
        return trim($key);
    }

    /**
     * 替换插入
     * 重载$this->insert() 部分功能
     */
    public function replace()
    {
        return $this->insert(true);
    }

    /**
     * 插入数据 支持批量插入
     *
     * @param  boolean $replace 是否替换插入
     *
     * @return 返回插入主键值 如没有则为影响行数 出错返回false
     */
    public function insert($replace = false)
    {
        $this->sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO '
            . $this->_parseTable()
            . $this->_parseData('insert');

        $query = $this->query($this->sql);

        $id = $this->db->insert_id();
        return empty($id) ? $this->db->affected_rows() : $id;
    }

    /**
     * data分析
     *
     * @param array $data
     */
    private function _parseData($type = 'insert')
    {
        $data = isset($this->options['data']) ? $this->options['data'] : '';
        //插入
        if ($type == 'insert') {
            $fields = $values = [];
            //批量
            if (isset($data[0]) && is_array($data[0])) {
                $fields = array_map([$this, '_filterFieldName'], array_keys($data[0]));
                foreach ($data as $key => $var) {
                    $values[] = '(' . implode(',', array_map([$this, '_filterFieldValue'], array_values($var))) . ')';
                }
                return ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values);
            } //单条
            else {
                $fields = array_map([$this, '_filterFieldName'], array_keys($data));
                $values = array_map([$this, '_filterFieldValue'], array_values($data));
                return ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
            }
        } //更新
        else {
            $set = [];
            foreach ($data as $key => $val) {
                $set[] = $this->_filterFieldName($key) . '=' . $this->_filterFieldValue($val);
            }
            return ' SET ' . implode(',', $set);
        }
    }

    /**
     * 更新
     *
     * @return 返回受影响函数 发生错误 返回false
     */
    public function update()
    {
        $this->sql = 'UPDATE '
            . $this->_parseTable()
            . $this->_parseData('update')
            . $this->_parseWhere();

        $this->query($this->sql);
        return $this->db->affected_rows();
    }

    /**
     * 删除
     *
     * @return 返回受影响函数 发生错误 返回false
     */
    public function delete()
    {
        $this->sql = 'DELETE FROM '
            . $this->_parseTable()
            . $this->_parseWhere()
            . $this->_parseOrder()
            . $this->_parseLimit();

        $this->query($this->sql);
        return $this->db->affected_rows();
    }

    /**
     * 启动事务
     */
    public function startTrans()
    {
        $this->commit();
        $this->db->start_trans();
        return;
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * 返回最后一条执行的SQL语句
     */
    public function getLastSql()
    {
        return $this->sql;
    }

    /**
     * 获取数据库错误信息
     */
    public function getError()
    {
        return $this->error;
    }
}
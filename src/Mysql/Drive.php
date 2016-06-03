<?php
// +----------------------------------------------------------------------
// | mysql驱动
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org> 
// +----------------------------------------------------------------------
// |  Time: 2016/6/3 10:45
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------

namespace Baiy\Mysql;

class Drive
{
    //数据库连接实例
    public $link;
    //数据库主机
    public $dbhost;
    //数据库用户名
    public $dbuser;
    //数据库密码
    public $dbpw;
    //数据库编码
    public $dbcharset;

    //事务开启标记
    private $trans_tag = 0; //0 关闭 >0 开启

    /**
     * 连接数据库
     *
     * @param  string $dbhost    数据库地址
     * @param  string $dbuser    数据库用户
     * @param  string $dbpw      数据库密码
     * @param  string $dbname    数据库名
     * @param  int    $dbport    数据库端口
     * @param  string $dbcharset 数据库编码
     */
    public function __construct($dbhost, $dbuser, $dbpw, $dbname, $dbport, $dbcharset)
    {
        $this->dbhost    = $dbhost;
        $this->dbuser    = $dbuser;
        $this->dbpw      = $dbpw;
        $this->dbname    = $dbname;
        $this->dbport    = $dbport;
        $this->dbcharset = $dbcharset;

        $this->link = new \mysqli($this->dbhost, $this->dbuser, $this->dbpw, $this->dbname, $this->dbport);
        if ($this->link->connect_error) {
            $this->halt('数据库连接错误 (' . $this->link->connect_errno . ')' . $this->link->connect_error);
        }

        //设置数据库编码 防止中文乱码
        $this->link->query("SET NAMES '" . $this->dbcharset . "'");
        //设置运行模型 防止mysql报错
        $this->link->query("SET sql_mode=''");
    }

    /**
     * 输出错误信息
     *
     * @param  string $message 错误信息
     * @param  string $sql     sql语句
     */
    public function halt($message = '', $sql = '')
    {
        throw new \Exception($message . $sql);
    }

    /**
     * 选择数据库
     *
     * @param  string $dbname 数据库名
     */
    public function select_db($dbname)
    {
        return $this->link->select_db($dbname);
    }

    /**
     * 查询sql语句
     *
     * @param  string $sql SQL语句
     */
    public function query($sql)
    {
        $result = $this->link->query($sql);
        if ($result === false) {
            $this->halt($sql . '执行错误 [' . $this->errno . ']' . $this->error);
        }
        return $result;
    }

    /**
     * 从结果集中取得一行作为关联数组，或数字数组，或二者兼有
     *
     * @param  object $result      结果集对象
     * @param  int    $result_type 返回类型
     */
    public function fetch_array($result, $result_type = MYSQLI_ASSOC)
    {
        return empty($result) ? '' : $result->fetch_array($result_type);
    }

    /**
     * 获取上一次插入的id
     */
    public function insert_id()
    {
        return $this->link->insert_id;
    }

    /**
     * 取得前一次 MySQL 操作所影响的记录行数
     */
    public function affected_rows()
    {
        return $this->link->affected_rows;
    }

    /**
     * 取得结果集中行的数目
     *
     * @param  object $result 结果集对象
     */
    public function num_rows($result)
    {
        return empty($result) ? '' : $result->num_rows;
    }

    /**
     * 取得结果集中字段的数目
     *
     * @param  object $result 结果集对象
     */
    public function num_fields($result)
    {
        return empty($result) ? '' : $result->field_count;
    }

    /**
     * 从结果集中取得列信息并作为对象返回
     *
     * @param  object $result 结果集对象
     */
    public function fetch_fields($result)
    {
        return empty($result) ? '' : $result->fetch_field();
    }

    /**
     * 释放结果内存
     *
     * @param  object $result 结果集对象
     */
    public function free_result($result)
    {
        return empty($result) ? '' : $result->free();
    }

    /**
     * 启动事务
     */
    public function start_trans()
    {
        if ($this->trans_tag == 0) {
            //关闭自动提交
            $this->link->autocommit(false);
        }
        $this->trans_tag++;
        return true;
    }

    /**
     * 事务提交
     * 用于非自动提交状态下面的查询提交
     */
    public function commit()
    {
        if ($this->trans_tag > 0) {
            //事务提交
            $result = $this->link->commit();
            $this->link->autocommit(true);
            $this->trans_tag = 0;
            if (!$result) {
                $this->halt($this->errno(), $this->error());
                return false;
            }
        }
        return true;
    }

    /**
     * 获取错误代码
     */
    public function errno()
    {
        return empty($this->link) ? '' : $this->link->errno;
    }

    /**
     * 获取错误信息详情
     */
    public function error()
    {
        return empty($this->link) ? '' : $this->link->error;
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        if ($this->trans_tag > 0) {
            $result = $this->link->rollback();
            $this->link->autocommit(true);
            $this->trans_tag = 0;
            if (!$result) {
                $this->halt($this->errno(), $this->error());
                return false;
            }
        }
        return true;
    }

    /**
     * 获取版本号
     */
    public function version()
    {
        return $this->link->server_info;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 关闭数据库连接
     */
    public function close()
    {
        return !empty($this->link) ? $this->link->close() : true;

    }
}
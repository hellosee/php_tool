<?php
// +----------------------------------------------------------------------
// | Mencache 缓存类
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org> 
// +----------------------------------------------------------------------
// |  Time: 2016/6/3 10:42
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------

namespace Baiy\Cache;

/**
 * 配置参数
 * array(
 * "host"=>'127.0.0.1',
 * "port"=>'11211',
 * "timeout"=>0,
 * "persistent"=>false,
 * "prefix"=>'',
 * )
 */
class Mencache implements Base
{
    private $option = [
        "host"       => '127.0.0.1',
        "port"       => '11211',
        "timeout"    => 0,
        "persistent" => false,
        "prefix"     => '',
    ];
    private $handler;

    public function init($option = [])
    {
        if (!extension_loaded('memcache')) {
            throw new \Exception('[memcache] 扩展为安装');
        }
        $this->option = array_merge($this->option, $option);
        $this->connect();
    }

    private function connect()
    {
        $this->handler = new \Memcache;
        $func          = $this->option['persistent'] ? 'pconnect' : 'connect';
        if ($this->option['timeout']) {
            $is = $this->handler->$func($this->option['host'], $this->option['port'], $this->option['timeout']);
        } else {
            $is = $this->handler->$func($this->option['host'], $this->option['port']);
        }

        if ($is != true) {
            throw new \Exception('[memcache] ' . $this->option['host'] . ' 连接失败');
        }
    }

    public function set($name, $value, $expire = 0)
    {
        $name = $this->options['prefix'] . $name;
        if ($this->handler->set($name, $value, 0, $expire)) {
            return true;
        }
        return false;
    }

    public function get($name)
    {
        return $this->handler->get($this->options['prefix'] . $name);
    }

    public function delete($name)
    {
        return $this->handler->delete($this->options['prefix'] . $name);
    }

    public function flush()
    {
        return $this->handler->flush();
    }

    public function close()
    {
        return $this->handler->close();
    }
}
<?php
// +----------------------------------------------------------------------
// | 缓存类
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org>
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------
namespace Baiy;

/**
 * $cache = new \Baiy\Cache($type, $config);
 * ===========使用方法=============
 * 设置:$db->set($name, $value, $expire = 0);
 * 获取:$db->get($name);
 * 删除:$db->delete($name);
 * 清空:$db->flush();
 * 关闭:$db->close();
 */
class Cache
{

    private $handler;

    public function __construct($type, $option = [])
    {
        switch (strtolower($type)) {
            case 'file':
                $this->handler = new \Baiy\Cache\File();
                break;
            case 'mencache':
                $this->handler = new \Baiy\Cache\Mencache();
                break;
            default:
                throw new \Exception('缓存类型设置不正确');
                break;
        }
        //初始化
        $this->handler->init($option);
    }

    public function set($name, $value, $expire = 0)
    {
        $this->checkName($name);
        return $this->handler->set($name, $value, $expire);
    }

    private function checkName($name)
    {
        if (empty($name)) {
            throw new \Exception('缓存名称不能为空');
        }
    }

    public function get($name)
    {
        $this->checkName($name);
        return $this->handler->get($name);
    }

    public function delete($name)
    {
        $this->checkName($name);
        return $this->handler->delete($name);
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
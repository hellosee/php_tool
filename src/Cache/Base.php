<?php
// +----------------------------------------------------------------------
// | 缓存接口
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org> 
// +----------------------------------------------------------------------
// |  Time: 2016/6/3 10:36
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------

namespace Baiy\Cache;

interface Base
{
    public function init($option);

    public function set($name, $value, $expire = 0);

    public function get($name);

    public function delete($name);

    public function flush();

    public function close();
}
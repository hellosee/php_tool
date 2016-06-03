<?php
// +----------------------------------------------------------------------
// | 文经缓存
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org> 
// +----------------------------------------------------------------------
// |  Time: 2016/6/3 10:39
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------

namespace Baiy\Cache;

/**
 * 配置参数
 * array(
 * 'path'=>'缓存路径',
 * 'type'=>'serialize|array'
 * );
 */
class File implements Base
{
    private $path;
    private $type;

    public function init($option = [])
    {
        if (!isset($option['path'])) {
            throw new \Exception('必须设置缓存路径');
        }
        if (!is_dir($option['path'])) {
            mkdir($option['path'], 0777, true);
        }

        if (!$this->dir_writeable($option['path'])) {
            throw new \Exception('[文件缓存]缓存目录不可写');
        }

        $this->path = rtrim($option['path'], '\\/') . DIRECTORY_SEPARATOR;
        $this->type = $option['type'] == 'serialize' ? 'serialize' : 'array';
    }

    /**
     * 检查目录是否可写
     */
    private function dir_writeable($path)
    {
        $testfile = $path . "/test.test";

        $fp = @fopen($testfile, "wb");
        if ($fp) {
            @fclose($fp);
            @unlink($testfile);
            return true;
        }
        return false;
    }

    public function set($name, $value, $expire = 0)
    {
        $file = $this->path . self::cacheName($name);
        if ($expire > 0) {
            $value = ['content' => $value, md5('expire') => time() + $expire];
        }
        if ($this->type == 'array') {
            $value = "<?php\nreturn " . var_export($value, true) . ";\n?>";
        } else {
            $value = serialize($value);
        }
        file_put_contents($file, $value);
        return;
    }

    /**
     * 缓存文件名
     */
    private static function cacheName($name)
    {
        return md5($name) . '.cache.db';
    }

    public function get($name)
    {
        $file  = $this->path . self::cacheName($name);
        $value = '';
        if (is_file($file)) {
            if ($this->type == 'array') {
                $cache = include $file;
            } else {
                $cache = unserialize(file_get_contents($file));
            }

            if (!isset($cache[md5('expire')])) {
                $value = $cache;
            } else {
                if (time() <= $cache[md5('expire')]) {
                    $value = $cache['content'];
                }
            }
        }
        return $value;
    }

    public function delete($name)
    {
        $file = $this->path . self::cacheName($name);
        if (is_file($file)) {
            @unlink($file);
        }
        return;
    }

    public function flush()
    {
        $path  = $this->path;
        $files = scandir($path);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_dir($path . $file)) {
                    array_map('unlink', glob($path . $file . '/*.*'));
                } elseif (is_file($path . $file)) {
                    unlink($path . $file);
                }
            }
            return true;
        }
        return false;
    }

    public function close()
    {
    }
}
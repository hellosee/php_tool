<?php
namespace Baiy;

/**
 * 文件系统类
 */
class FileSystem
{

    /**
     * 拷贝目录及下面所有文件
     *
     * @param    string $fromdir 原路径
     * @param    string $todir 目标路径
     * @return   string  如果目标路径不存在则返回false，否则为true
     */
    function dirCopy($fromdir, $todir)
    {
        $fromdir = $this->dirPath($fromdir);
        $todir = $this->dirPath($todir);
        if (!is_dir($fromdir)) return FALSE;
        if (!is_dir($todir)) $this->dirCreate($todir);
        $list = glob($fromdir . '*');
        if (!empty($list)) {
            foreach ($list as $v) {
                $path = $todir . basename($v);
                if (is_dir($v)) {
                    $this->dirCopy($v, $path);
                } else {
                    copy($v, $path);
                    @chmod($path, 0777);
                }
            }
        }
        return TRUE;
    }

    /**
     * 转化 \ 为 /
     *
     * @param    string $path 路径
     * @return   string  路径
     */
    function dirPath($path)
    {
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) != '/') $path = $path . '/';
        return $path;
    }

    /**
     * 创建目录
     *
     * @param    string $path 路径
     * @param    string $mode 属性
     * @return   string  如果已经存在则返回true，否则为flase
     */
    function dirCreate($path)
    {
        if (is_dir($path)) return TRUE;
        $path = $this->dirPath($path);
        $temp = explode('/', $path);
        $cur_dir = '';
        $max = count($temp) - 1;
        for ($i = 0; $i < $max; $i++) {
            $cur_dir .= $temp[$i] . '/';
            if (@is_dir($cur_dir)) continue;
            @mkdir($cur_dir, 0777, true);
            @chmod($cur_dir, 0777);
        }
        return is_dir($path);
    }

    /**
     * 列出目录下所有文件
     *
     * @param    string $path 路径
     * @param    string $exts 扩展名
     * @param    array $list 增加的文件列表
     * @return   array   所有满足条件的文件
     */
    function dirList($path, $exts = '', $list = array())
    {
        $path = $this->dirPath($path);
        $files = glob($path . '*');
        foreach ($files as $v) {
            if (!$exts || pathinfo($v, PATHINFO_EXTENSION) == $exts) {
                $list[] = $v;
                if (is_dir($v)) {
                    $list = $this->dirList($v, $exts, $list);
                }
            }
        }
        return $list;
    }

    /**
     * 设置目录下面的所有文件的访问和修改时间
     *
     * @param    string $path 路径
     * @param    int $mtime 修改时间
     * @param    int $atime 访问时间
     * @return   array   不是目录时返回false，否则返回 true
     */
    function dirTouch($path, $mtime = 0, $atime = 0)
    {
        if (!is_dir($path)) return false;
        $path = $this->dirPath($path);
        if (!is_dir($path)) touch($path, $mtime, $atime);
        $files = glob($path . '*');
        foreach ($files as $v) {
            is_dir($v) ? $this->dirTouch($v, $mtime, $atime) : touch($v, $mtime, $atime);
        }
        return true;
    }

    /**
     * 目录列表
     *
     * @param    string $dir 路径
     * @param    int $parentid 父id
     * @param    array $dirs 传入的目录
     * @return   array   返回目录列表
     */
    function dirTree($dir, $parentid = 0, $dirs = array())
    {
        global $id;
        if ($parentid == 0) $id = 0;
        $list = glob($dir . '*');
        foreach ($list as $v) {
            if (is_dir($v)) {
                $id++;
                $dirs[$id] = array('id' => $id, 'parentid' => $parentid, 'name' => basename($v), 'dir' => $v . '/');
                $dirs = $this->dirTree($v . '/', $id, $dirs);
            }
        }
        return $dirs;
    }

    /**
     * 删除目录及目录下面的所有文件
     *
     * @param    string $dir 路径
     * @return   bool    如果成功则返回 TRUE，失败则返回 FALSE
     */
    function dirDelete($dir)
    {
        $dir = $this->dirPath($dir);
        if (!is_dir($dir)) return FALSE;
        $list = glob($dir . '*');
        foreach ($list as $v) {
            is_dir($v) ? $this->dirDelete($v) : @unlink($v);
        }
        return @rmdir($dir);
    }
}


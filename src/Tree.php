<?php
// +----------------------------------------------------------------------
// | 树形结构生成
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org>
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------
namespace Baiy;

class Tree
{

    /**
     * 配置参数
     */
    private $options = [
        'primary_key'    => 'id', //主节点
        'parent_key'     => 'pid', //父节点
        'order_key'      => '', //排序节点
        'order_by'       => 'DESC', //排序方式
        'son_lists_name' => 'son', //下级列表节点名称
    ];

    /**
     * 初始数据
     */
    private $init_data;

    public function __construct(array $data, $options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->initData($data);
    }

    /**
     * 初始化数据
     */
    private function initData($data)
    {
        //排序
        if (!empty($this->options['order_key'])) {
            $order_by  = $this->options['order_by'];
            $order_key = $this->options['order_key'];
            usort($data, function ($a, $b) use ($order_by, $order_key) {
                return $order_by == 'DESC' ? $a[$order_key] < $b[$order_key] : $a[$order_key] > $b[$order_key];
            });
        }

        $lists = [];
        foreach ($data as $value) {
            $lists[$value[$this->options['primary_key']]] = $value;
        }
        $this->init_data = $lists;
    }

    /**
     * 获取对应的所有子级
     *
     * @param  integer $id
     */
    public function getSons($id)
    {
        $init_data = $this->init_data;
        $options   = $this->options;
        $sons      = [];
        $tempfunc  = function ($pid) use (&$sons, &$tempfunc, $init_data, $options) {
            foreach ($init_data as $value) {
                if ($pid == $value[$options['parent_key']]) {
                    $sons[$value[$options['primary_key']]] = $value;
                    $tempfunc($value[$options['primary_key']]);
                }
            }
        };
        $tempfunc($id);
        return $sons;
    }

    /**
     * 获取对应的下级子级
     *
     * @param  integer $id
     */
    public function getSon($id)
    {
        $sons = [];
        foreach ($this->init_data as $value) {
            if ($id == $value[$this->options['parent_key']]) {
                $sons[$value[$this->options['primary_key']]] = $value;
            }
        }
        return $sons;
    }

    /**
     * 获取所有上级
     */
    public function getParents($id)
    {
        $pids = [];
        $pid  = $this->init_data[$id][$this->options['parent_key']];
        while ($pid) {
            $pids[] = $pid;
            $pid    = $this->init_data[$pid][$this->options['parent_key']];
        }
        $pids = array_reverse($pids);

        $lists = [];
        foreach ($pids as $var) {
            $lists[$var] = $this->init_data[$var];
        }

        return $lists;
    }

    /**
     * 获取上级
     */
    public function getParent($id)
    {
        $pid = $this->init_data[$id][$this->options['parent_key']];
        return $this->init_data[$pid];
    }

    /**
     * 获取树
     */
    public function getTree()
    {
        $init_data = $this->init_data;
        $options   = $this->options;
        $tempfunc  = function ($pid) use (&$tempfunc, $init_data, $options) {
            $lists = [];
            foreach ($init_data as $value) {
                if ($pid == $value[$options['parent_key']]) {
                    $sons = $tempfunc($value[$options['primary_key']]);
                    if (!empty($sons)) {
                        $value[$options['son_lists_name']] = $sons;
                    }
                    $lists[] = $value;
                }
            }
            return $lists;
        };
        return $tempfunc(0);
    }
}

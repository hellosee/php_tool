<?php
// +----------------------------------------------------------------------
// | 分页类 (Bootstrap v3 样式)
// +----------------------------------------------------------------------
// | Author: taotao.chen <wo@baiy.org>
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 https://www.baiy.org All rights reserved.
// +----------------------------------------------------------------------
namespace Baiy;

class Page
{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage = 11; // 分页栏每页显示的页数
    public $lastSuffix = true; // 最后一页是否显示总页数

    private $p = 'page'; //分页参数名
    private $url = []; //当前链接URL
    private $urltpl = ''; //分页模版
    private $nowPage = 1;

    // 分页显示定制
    private $config = [
        'header' => '<li><span class="rows">共 %TOTAL_ROW% 条记录</span></li>',
        'prev'   => '&laquo;',
        'next'   => '&raquo;',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    ];

    /**
     * 架构函数
     *
     * @param array $totalRows 总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter 分页跳转的参数
     */
    public function __construct($totalRows, $listRows, $parameter = [], $urltpl = '')
    {
        /* 基础设置 */
        $this->totalRows = $totalRows; //设置总记录数
        $this->listRows  = $listRows; //设置每页显示行数
        $this->parameter = empty($parameter) ? $_GET : $parameter;
        $this->nowPage   = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->urltpl    = $urltpl;
        $this->firstRow  = $this->listRows * ($this->nowPage - 1);
    }

    public function setVarPage($p)
    {
        $this->p = $p;
    }

    /**
     * 定制分页链接设置
     *
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 组装分页链接
     *
     * @return string
     */
    public function show()
    {
        if (0 == $this->totalRows) {
            return '';
        }

        /* 生成URL */
        $this->parameter[$this->p] = '_PAGE_';
        if (!empty($this->urltpl)) {
            $this->urltpl = new_explode($this->urltpl, true, '|');
            $this->url[0] = $this->urltpl[0];
            $this->url[1] = isset($this->urltpl[1]) ? $this->urltpl[1] : $this->urltpl[0];
        } else {
            $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
            $this->url[0] = $this->url[1] = $sys_protocal . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . http_build_query($this->parameter);
        }
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页零时变量 */
        $now_cool_page      = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<li><a class="prev" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a></li>' : '';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<li><a class="next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a></li>' : '';

        //第一页
        $the_first = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1) {
            $the_first = '<li><a class="first" href="' . $this->url(1) . '">' . $this->config['first'] . '</a></li>';
        }

        //最后一页
        $the_end = '';
        if ($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages) {
            $the_end = '<li><a class="end" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a></li>';
        }

        //数字连接
        $link_page = "";
        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } else {
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if ($page > 0 && $page != $this->nowPage) {

                if ($page <= $this->totalPages) {
                    $link_page .= '<li><a class="num" href="' . $this->url($page) . '">' . $page . '</a></li>';
                } else {
                    break;
                }
            } else {
                if ($page > 0 && $this->totalPages != 1) {
                    $link_page .= '<li class="active"><span class="current">' . $page . '</span></li>';
                }
            }
        }

        //替换分页内容
        $page_str = str_replace(
            [
                '%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%',
                '%TOTAL_PAGE%'
            ],
            [
                $this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end,
                $this->totalRows, $this->totalPages
            ],
            $this->config['theme']);
        return "{$page_str}";
    }

    /**
     * 生成链接URL
     *
     * @param  integer $page 页码
     *
     * @return string
     */
    private function url($page)
    {
        if ($page < 2) {
            return str_replace(urlencode('_PAGE_'), $page, $this->url[0]);
        } else {
            return str_replace(urlencode('_PAGE_'), $page, $this->url[1]);
        }
    }
}

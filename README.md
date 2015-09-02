# 文件说明

## 缓存类 Cache.php

````php
<?php
$cache = new \Baiy\Cache($config);
//设置
$cache->set($name, $value, $expire = 0);
//获取
$cache->get($name);
//删除
$cache->delete($name);
//清空
$cache->flush();
//关闭
$cache->close();
?>
````

## HTTP请求类 Http.php
````php
<?php
$http = new \Baiy\Http();
//POST
$http->post();
//GET
$http->get();
//上传
$http->upload();
//获取结果
$http->get_data();
//获取头部
$http->get_header();
?>
````

## mysql操作类 Mysql.php

## RSS解析类 Rss.php

## 模板解析类 Template.php

## 树生成操作类 Tree.php
<?php
include 'Init.php';

$option = array(
		'cache_path' => __DIR__.'/tmp', // 模板缓存路径
		'tpl_path' => __DIR__.'/tmp/template', // 模板路径
		'debug' => false,
	);
$d = 'index/d';
$template = new \Baiy\Template($option,function($tpl) use($d){
	return empty($tpl) ? $d : $tpl;
});
$template->assign(array("title"=>'自定义标题',"b"=>time()));
$template->display('index/index');

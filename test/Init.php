<?php
header('Content-type: text/html; charset=utf-8');
spl_autoload_register('autoload');
function autoload($class) {
	$class = explode('\\', $class);
	$class = end($class);
	include dirname(__DIR__).'/'.$class.'.php';
}
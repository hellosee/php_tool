<?php
include 'Init.php';

$path = __DIR__ . '/tmp';
//file
$cache = new \Baiy\Cache('Mencache');

$cache->set("aaa", range(100, 1000));
print_r($cache->get('aaa'));
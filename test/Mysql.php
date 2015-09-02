<?php
include 'Init.php';

$db = new \Baiy\Mysql(['dbname'=>'baiy','debug'=>false]);
$data = array(
        ["key1"=>'data1',
        "key2"=>'data2',
        "key3"=>'data3',
        "key4"=>'data4'],
        ["key1"=>'data1',
        "key2"=>'data2',
        "key3"=>'data3',
        "key4"=>'data4'],
        ["key1"=>'data1',
        "key2"=>'data2',
        "key3"=>'data3',
        "key4"=>'data5']
    );
print_r($db->table('images')->data($data)->insert());
<?php

//shows results in PHP and JSON

include 'DaseClient.php';
$client = new DaseClient('keanepj');
$res = $client->search('favorite');
$app_root = $res->app_root;
$total = $res->total;

print_r($res);

$client = new DaseClient('keanepj',false);
$res = $client->search('favorite');

print $res;


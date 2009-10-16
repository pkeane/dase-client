<?php

include 'DaseClient.php';
$client = new DaseClient('keanepj',false);
$q = trim(strip_tags($_GET['q']));
$res = $client->search($q);

header('Content-type: application/json');

echo $res;


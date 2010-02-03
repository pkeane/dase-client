<?php

include 'DaseClient.php';

$user = 'pkeane';
$c = new DaseClient('');


print $c->searchCollections('red',array('gov310','efossils'));


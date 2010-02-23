<?php

include 'DaseClient.php';

$user = 'pkeane';
$c = new DaseClient('test','json','http://dase.laits.utexas.edu');


print $c->searchCollections('red',array('gov310','efossils'));


<?php

include 'DaseClient.php';


$c = new DaseClient('keanepj');
$c->setAuth('pkeane','098xxx123');

foreach ($c->getFilePaths('/mnt/home/pkeane/test_uploads') as $fp) {
	$meta = array('one'=>'two','three'=>'four');
	$res = $c->postFileToCollection($fp,$meta);
	print $res[1];
}

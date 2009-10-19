<?php

include 'DaseClient.php';


$c = new DaseClient('keanepj');
$c->setAuth('pkeane','password here');

foreach ($c->getFilePaths('/mnt/home/pkeane/test_uploads') as $fp) {
	$meta = array('one'=>'two','three'=>'four');
	$res = $c->postFileToCollection($fp,$meta);
	print $res[1];
}

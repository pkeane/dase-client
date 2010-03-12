<?php

include 'DaseClient.php';

$user = 'pkeane';
$pass = DaseClient::promptForPassword($user);
$REPO = '/mnt/home/pkeane/test_mp4s';

$c = new DaseClient('keanepj');
$c->setAuth($user,$pass);

foreach ($c->getFilePaths($REPO) as $fp) {
	$base = basename($fp);
	$title = str_replace('_',' ',preg_replace('/\.(m|M)(p|P)4/','',$base));
	$meta = array('new_attribute'=>$title,'original_filename'=>$base);
	$res = $c->postFileToCollection($fp,$meta,true,'video');
	print_r($res);
	print $base."\n";
}

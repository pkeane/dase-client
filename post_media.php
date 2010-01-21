<?php

include 'DaseClient.php';

$user = 'pkeane';
$pass = DaseClient::promptForPassword($user);
$REPO = '/mnt/dar2/diia/wallercreek';


$c = new DaseClient('waller');
$c->setAuth($user,$pass);

foreach ($c->getFilePaths($REPO) as $fp) {
	$parts = explode('/',$fp);
	$last = array_pop($parts);
	$name = str_replace('.JPG','',$last);
	$path = str_replace($REPO,'',$fp);
	$parts2 = explode('/',trim($path,'/'));
	$year = array_shift($parts2);
	$date = array_shift($parts2);

	print $name."\n";
	$meta = array('description'=>$name,'title'=>$path,'year'=>$year,'date' => $date);
	$res = $c->postFileToCollection($fp,$meta);
	print $res[1];
}

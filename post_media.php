<?php

include 'DaseClient.php';


$c = new DaseClient('ee_jewish_history');
$c->setAuth('pkeane','098xxx123');

foreach ($c->getFilePaths('/mnt/home/pkeane/bulgarian_journal') as $fp) {
	$parts = explode('/',$fp);
	$last = array_pop($parts);
	$name = str_replace('.jpg','',$last);
	print $name."\n";
	$meta = array('format'=>'Journal','country'=>'Bulgaria','title'=>$name,'number_in_series'=>$name);
	$res = $c->postFileToCollection($fp,$meta);
	print $res[1];
}

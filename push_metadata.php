<?php

setlocale(LC_ALL, 'en_US.UTF-8');

include '../dase-client/DaseClient.php';
$client = new DaseClient('japanese_grammar');
$user = 'pkeane';
$pass = DaseClient::promptForPassword($user);

$filename = 'joshu_md2.csv';

$handle = fopen($filename, "r");

$meta = array();
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	$filename = $data[0];
	$meta[$filename]['filename'] = $data[0];
	$meta[$filename]['grammar_tags'] = explode('|',$data[1]);
	$meta[$filename]['english_title'] = $data[2];
	$meta[$filename]['japanese_title'] = $data[3];
}
fclose($handle);

foreach ($meta as $filename => $m) {
	$title = $filename.'.mov';
	$res = $client->search('@title:'.$title);
	$url = $res->app_root.$res->items[0]->links->metadata;
	if ($url) {
		$pairs = array();
		foreach ($m as $k=> $v) {
			if (is_array($v)) {
				$vset = array();
				foreach($v as $str) {
					$vset[] = '"'.$str.'"';
				}
				$pairs[] = '"'.$k.'":['.join(',',$vset).']'; 
			} else {
				$pairs[] = "\"$k\":\"$v\"\n";
			}
		}
		$json = "{".join(',',$pairs)."}\n";
		$rp = DaseClient::post($url,$json,$user,$pass,'application/json');
		print_r($rp);
	}
}

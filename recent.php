<?php

//PHP ERROR REPORTING -- turn off for production
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);



include 'DaseClient.php';
$client = new DaseClient('vrc');

$user = 'pkeane';
$pass = 'dupload';

$REPO = '/mnt/dar2/favrc/for-dase';


function getFilePaths($directory)
{
	$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
	$files = array();
	foreach ($dir as $file) {
		$file_path = $file->getPathname();
		if ($file->isFile() && '.' != substr($file->getFilename(),0,1)) {
			$files[$file->getMTime().$file->getFilename()]['path'] = $file_path;
			$files[$file->getMTime().$file->getFilename()]['name'] = $file->getFilename();
		}
	}
	krsort($files);
	return $files;
}

$max = 40;


foreach (getFilePaths($REPO) as $file) {
	$max--;
	if ($max > 0) {
		$mtime = filemtime($file['path']);
		if (time()-$mtime < 48*3600) {
			print $file['path'].":".filemtime($file['path'])."\n";
			$acc = str_replace('.tif','',$file['name']);
			$res = $client->search('acc_num_PK:'.$acc);
			print_r($res);
			if (isset($res->items) && count($res->items)) {
				$url = 'https://daseupload.laits.utexas.edu'.$res->items[0]->links->media;
				//$bits = file_get_contents($file['path']);
				//$resp = DaseClient::post($url,$bits,$user,$pass,'image/tiff');
				//print_r($resp);
			} else {
				print "found no record in DASe\n";
			}
		}

	}
}

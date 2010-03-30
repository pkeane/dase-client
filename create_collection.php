<?php

include 'DaseClient.php';

$user = 'pkeane';
$pass = DaseClient::promptForPassword($user);
$dase_host = 'https://daseupload.laits.utexas.edu';

$colls = array('Scratch One','Scratch Two');

foreach ($colls as $coll) {
	$res = DaseClient::createCollection($dase_host,$coll,$user,$pass);
	print_r($res);
}

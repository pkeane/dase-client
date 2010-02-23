<?php

include 'DaseClient.php';

ini_set('memory_limit','700M');

$user = 'pkeane';
$pass = DaseClient::promptForPassword($user);

$client = new DaseClient('waller');
$uris = $client->getCollectionItemUris();

foreach (explode("\n",$uris) as $uri) {
	$res = DaseClient::get($uri.'.atom',$user,$pass);
	if ('200' == $res[0]['http_code']) {
		$atom_entry = $res[1];
		if ('image/jpeg' == $client->getAdminMetadata($atom_entry,'admin_mime_type')) {
			$item_type_link = $client->getLinkByRel($atom_entry,'http://daseproject.org/relation/edit-item_type')."\n";
			$post_res = DaseClient::post($item_type_link,'photo',$user,$pass,'text/plain');

			//print HTTP code and response message
			print $post_res[0]['http_code'].' '.$post_res[1]."\n";
		}
	} else {
		print $res[0]['http_code'].' '.$res[1]."\n";
	}	
}

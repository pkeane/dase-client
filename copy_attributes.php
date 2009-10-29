<?php

include 'DaseClient.php';

$user = 'pkeane';
$pass = DaseClient::getPassword($user);

$old_waller = new DaseClient('waller_creek');
$new_waller = new DaseClient('waller');
$new_waller->setAuth($user,$pass);

$atts = $old_waller->getCollectionAttributesAtom();

foreach (DaseClient::getLinksByRel($atts,'edit') as $url) {
	$res = DaseClient::get($url);
	if ('200' == $res[0]) {
		$att_atom = $res[1];
		print $new_waller->postAttributeToCollection($att_atom);
		print "\n";
	}
}

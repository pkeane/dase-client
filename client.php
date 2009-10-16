<?php

include 'DaseClient.php';

$client = new DaseClient('keanepj');
$res = $client->search('hotel');

$app_root = $res->app_root;
$total = $res->total;

$html ="<html><head><title>DaseClient Sample</title></head><body>";
$html .="<h1>DaseClient Sample</h1>";
$html .="<h3>$total items found</h3>";
$html .="<ul>";

foreach ($res->items as $item) {
	if (isset($item->metadata->keyword)) {
		$html .='<li><img src="'.$app_root.'/'.$item->media->small.'">'.$item->metadata->keyword[0]."</li>\n";
	}
}

$html .="</ul>";
$html .="</body></html>";

echo $html;

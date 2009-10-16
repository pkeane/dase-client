<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>My Favorites</title>

		<link rel="stylesheet" type="text/css" href="www/css/style.css">
<script type="text/javascript" src="www/scripts/jquery.js"></script>
</head>

<?php

include 'DaseClient.php';
$client = new DaseClient('keanepj');
$res = $client->search('keyword:favorite item_type:room');
$app_root = $res->app_root;
$total = $res->total;

?>

<body>

<h1>My Favorites (<?php echo $total; ?> items found)</h1>

<ul>

<?php foreach ($res->items as $item) { ?>

<li><img src="<?php echo $app_root; ?>/<?php echo $item->media->thumbnail; ?>"><?php echo $item->metadata->description[0]; ?></li>

<?php } ?>

</ul>


</body>

</html>

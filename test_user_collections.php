<?php

include 'DaseClient.php';

$user = 'pkeane';
$c = new DaseClient('test');
$pass = DaseClient::promptForPassword($user);
$c->setAuth($user,$pass);


print $c->getUserCollections();


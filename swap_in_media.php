<?php

include 'DaseClient.php';
ini_set('memory_limit','700M');

$user = 'pkeane';
$pass = DaseClient::promptForPassword($user);
$REPO = '/home/pkeane/sai_videos';


$c = new DaseClient('south_asia','php');
$c->setAuth($user,$pass);

foreach ($c->getFilePaths($REPO) as $fp) {
    $link = matcher($fp,$c);
    if (!$link) {
        print $fp."\n";;
    } else {
        $body = file_get_contents($fp);
        $res = DaseClient::put($link,$body,$user,$pass,DaseClient::getMime($fp));
        print_r($res);
    }
}

//write this anew for each job
//accepts a filepath and returns a media-link
function matcher($fp,$client)
{
    $parts = explode('/',$fp);
    $name = array_pop($parts);
    $sernum = DaseClient::makeSerialNumber($name);
    $item = $client->getBySerialNumber($sernum);
    $a = 'edit-media';
    if ($item) {
        return $client->getDaseUrl().$item->links->$a."\n";
    } else {
        $name = preg_replace('/-SAI seminar series/','',$name,1);
        $sernum = DaseClient::makeSerialNumber($name);
        $item = $client->getBySerialNumber($sernum);
        if ($item) {
            return $client->getDaseUrl().$item->links->$a."\n";
        } else {
            return false;
        }
    }
}

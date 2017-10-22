<?php

$path = realpath('E:\songs');
$iterator = new DirectoryIterator($path);
//    $iterator->setFlags(DirectoryIterator::SKIP_DOTS);
$objects = new IteratorIterator($iterator);

//$part = "";

$songs_array = array();
$songs = "";
$i=0;
foreach ($objects as $name) {
    if ($name->isFile()) {

        $title_no = filter_var($name, FILTER_SANITIZE_NUMBER_INT);
        $songs .= $title_no . ", ";
    }
}

print_r($songs);
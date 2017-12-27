<?php

require __DIR__ . "/vendor/autoload.php";

define("WHATANIME_DIR", __DIR__."/whatanime_cache");

$st = file_get_contents("1.jpg");
$st = new WhatAnime\WhatAnime($st);
$st = $st->getFirst();
var_dump($st);

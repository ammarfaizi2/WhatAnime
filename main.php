<?php

require __DIR__ . "/vendor/autoload.php";

define("WHATANIME_DIR", __DIR__."/whatanime_cache");
define("WHATANIME_VIDEO_URL", "https://webhook.teainside.ga/storage/telegram/whatanime_cache/video");

$st = file_get_contents("/tmp/qqq.jpg");
$st = new WhatAnime\WhatAnime($st);
$out = $st->getFirst();

var_dump($out);
var_dump($st->getVideo());
// var_dump($out);

<?php

require __DIR__ . "/vendor/autoload.php";

$st = new WhatAnime\WhatAnime();
$st = $st->exec();
var_dump($st);

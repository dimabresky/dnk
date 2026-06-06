<?php

if (is_file(__DIR__ . '/include/include.php')) {
    require_once __DIR__ . '/include/include.php';
}

use Dnk\PhpInterface\Utils;

Utils::processUserReauthorizeIfNeeded();

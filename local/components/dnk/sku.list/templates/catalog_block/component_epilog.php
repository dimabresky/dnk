<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (class_exists(\TSolution\Extensions::class)) {
    \TSolution\Extensions::init(['swiper', 'swiper_events']);
}

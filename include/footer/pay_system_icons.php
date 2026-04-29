<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @global CMain $APPLICATION */
global $APPLICATION;

$APPLICATION->IncludeComponent(
    'dnk:payment.logos',
    '',
    [
        'SHOW_BADGES' => 'Y',
        'USE_STRIP_IMAGE' => 'N',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);

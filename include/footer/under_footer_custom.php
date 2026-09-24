<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
    'dnk:review.notice',
    '',
    ['INTERVAL_HOURS' => 12],
    false,
    ['HIDE_ICONS' => 'Y']
);

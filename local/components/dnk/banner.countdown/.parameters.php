<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'DATETIME' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_DATETIME'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'TITLE' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_TITLE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'SUBTITLE' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_SUBTITLE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'PICTURE' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_PICTURE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'COLOR' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_COLOR'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'TIMER_COLOR' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_TIMER_COLOR'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'TIMER_BG' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_TIMER_BG'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'TIMER_SHADOW' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PARAM_TIMER_SHADOW'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
    ],
];

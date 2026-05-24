<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [
        'CACHE_SETTINGS' => [
            'NAME' => GetMessage('DNK_CERT_BUY_GROUP_CACHE'),
        ],
    ],
    'PARAMETERS' => [
        'CACHE_TIME' => [
            'PARENT' => 'CACHE_SETTINGS',
            'NAME' => GetMessage('DNK_CERT_BUY_PARAM_CACHE_TIME'),
            'TYPE' => 'STRING',
            'DEFAULT' => '3600',
        ],
    ],
];

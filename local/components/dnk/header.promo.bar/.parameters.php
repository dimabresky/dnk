<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [
        'CACHE_SETTINGS' => [
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_GROUP_CACHE'),
        ],
        'VIEW' => [
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_GROUP_VIEW'),
        ],
    ],
    'PARAMETERS' => [
        'IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_PARAM_IBLOCK_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => defined('DNK_HEADER_PROMO_IBLOCK_ID') ? (string) DNK_HEADER_PROMO_IBLOCK_ID : '',
        ],
        'CACHE_TIME' => [
            'PARENT' => 'CACHE_SETTINGS',
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_PARAM_CACHE_TIME'),
            'TYPE' => 'STRING',
            'DEFAULT' => '120',
        ],
        'MOBILE_BREAKPOINT' => [
            'PARENT' => 'VIEW',
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_PARAM_MOBILE_BREAKPOINT'),
            'TYPE' => 'STRING',
            'DEFAULT' => '768',
        ],
        'HIDE_ON_EXPIRE' => [
            'PARENT' => 'VIEW',
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_PARAM_HIDE_ON_EXPIRE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
    ],
];

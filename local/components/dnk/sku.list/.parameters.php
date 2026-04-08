<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [
        'CACHE_SETTINGS' => [
            'NAME' => GetMessage('DNK_SKU_LIST_GROUP_CACHE'),
        ],
    ],
    'PARAMETERS' => [
        'IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_SKU_LIST_PARAM_IBLOCK_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => defined('DNK_CATALOG_IBLOCK_ID') ? DNK_CATALOG_IBLOCK_ID : '',
        ],
        'ELEMENT_ID' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_SKU_LIST_PARAM_ELEMENT_ID'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'CACHE_TIME' => [
            'PARENT' => 'CACHE_SETTINGS',
            'NAME' => GetMessage('DNK_SKU_LIST_PARAM_CACHE_TIME'),
            'TYPE' => 'STRING',
            'DEFAULT' => '3600',
        ],
    ],
];

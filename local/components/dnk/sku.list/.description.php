<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('DNK_SKU_LIST_COMPONENT_NAME'),
    'DESCRIPTION' => GetMessage('DNK_SKU_LIST_COMPONENT_DESCRIPTION'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'dnk',
        'NAME' => 'DNK',
        'CHILD' => [
            'ID' => 'catalog',
            'NAME' => GetMessage('DNK_SKU_LIST_COMPONENT_PATH_CATALOG'),
        ],
    ],
];

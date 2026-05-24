<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('DNK_CERT_BUY_COMPONENT_NAME'),
    'DESCRIPTION' => GetMessage('DNK_CERT_BUY_COMPONENT_DESC'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'dnk',
        'NAME' => 'DNK',
        'CHILD' => [
            'ID' => 'shop',
            'NAME' => GetMessage('DNK_CERT_BUY_COMPONENT_PATH_SHOP'),
        ],
    ],
];

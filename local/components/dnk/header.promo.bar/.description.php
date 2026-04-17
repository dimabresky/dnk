<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_COMPONENT_NAME'),
    'DESCRIPTION' => GetMessage('DNK_HEADER_PROMO_BAR_COMPONENT_DESCRIPTION'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'dnk',
        'NAME' => 'DNK',
        'CHILD' => [
            'ID' => 'content',
            'NAME' => GetMessage('DNK_HEADER_PROMO_BAR_COMPONENT_PATH_CONTENT'),
        ],
    ],
];

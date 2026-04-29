<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('DNK_PAYMENT_LOGOS_NAME'),
    'DESCRIPTION' => GetMessage('DNK_PAYMENT_LOGOS_DESC'),
    'ICON' => '/images/icon.gif',
    'PATH' => [
        'ID' => 'dnk',
        'NAME' => 'DNK',
        'CHILD' => [
            'ID' => 'content',
            'NAME' => GetMessage('DNK_PAYMENT_LOGOS_PATH_CONTENT'),
        ],
    ],
];

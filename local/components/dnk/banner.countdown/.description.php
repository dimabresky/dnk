<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_NAME'),
    'DESCRIPTION' => GetMessage('DNK_BANNER_COUNTDOWN_DESC'),
    'PATH' => [
        'ID' => 'dnk',
        'NAME' => 'DNK',
        'CHILD' => [
            'ID' => 'content',
            'NAME' => GetMessage('DNK_BANNER_COUNTDOWN_PATH_CONTENT'),
        ],
    ],
];

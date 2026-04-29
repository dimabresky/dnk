<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'SHOW_BADGES' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_PAYMENT_LOGOS_PARAM_SHOW_BADGES'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'USE_STRIP_IMAGE' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_PAYMENT_LOGOS_PARAM_USE_STRIP'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
    ],
];

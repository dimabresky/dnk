<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'INTERVAL_HOURS' => [
            'PARENT' => 'BASE',
            'NAME' => GetMessage('DNK_REVIEW_NOTICE_PARAM_INTERVAL_HOURS'),
            'TYPE' => 'STRING',
            'DEFAULT' => '12',
        ],
    ],
];

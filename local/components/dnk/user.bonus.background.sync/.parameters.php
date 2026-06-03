<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => [
            'NAME' => GetMessage('DNK_BONUS_BG_SYNC_GROUP_SETTINGS'),
        ],
    ],
    'PARAMETERS' => [
        'BALANCE_SELECTOR' => [
            'PARENT' => 'SETTINGS',
            'NAME' => GetMessage('DNK_BONUS_BG_SYNC_PARAM_BALANCE_SELECTOR'),
            'TYPE' => 'STRING',
            'DEFAULT' => '.js-dnk-bonus-balance',
        ],
        'AUTO_REFRESH' => [
            'PARENT' => 'SETTINGS',
            'NAME' => GetMessage('DNK_BONUS_BG_SYNC_PARAM_AUTO_REFRESH'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
    ],
];

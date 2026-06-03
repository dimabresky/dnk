<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'REQUESTS_PER_PAGE' => [
            'NAME' => 'Заявок на странице',
            'TYPE' => 'STRING',
            'DEFAULT' => '10',
        ],
    ],
];

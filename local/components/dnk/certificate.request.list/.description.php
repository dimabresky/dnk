<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => GetMessage('DNK_CERT_REQ_LIST_NAME'),
    'DESCRIPTION' => GetMessage('DNK_CERT_REQ_LIST_DESC'),
    'PATH' => [
        'ID' => 'dnk',
        'NAME' => GetMessage('DNK_CERT_REQ_LIST_PATH'),
    ],
];

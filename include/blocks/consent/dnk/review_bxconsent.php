<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$arOptions = $arConfig['PARAMS'];

if (!$arOptions['OPTION_CODE']) {
    return;
}

$request = Bitrix\Main\Context::getCurrent()->getRequest();

$inputName = $arOptions['INPUT_NAME'] ?? 'reviewLicence';
$isChecked = TSolution::GetFrontParametrValue('REVIEW_CHECKED') === 'Y' || $request->get($inputName) === 'Y';

$APPLICATION->IncludeComponent(
    'bitrix:main.userconsent.request',
    'main',
    [
        'AUTO_SAVE' => $arOptions['AUTO_SAVE'] ?? 'N',
        'COMPOSITE_FRAME_MODE' => 'A',
        'COMPOSITE_FRAME_TYPE' => 'AUTO',
        'BLOCK_NAME' => 'review_licence_block',
        'ID' => TSolution::getFrontParametrValue($arOptions['OPTION_CODE']),
        'IS_CHECKED' => $isChecked ? 'Y' : '',
        'IS_LOADED' => 'N',
        'INPUT_NAME' => $inputName,
        'SUBMIT_EVENT_NAME' => $arOptions['SUBMIT_EVENT_NAME'] ?? '',
        'CODE' => $arOptions['CODE'] ?? '',
        'ORIGINATOR_ID' => $arOptions['ORIGINATOR_ID'] ?? '',
        'ORIGIN_ID' => $arOptions['ORIGIN_ID'] ?? '',
        'REPLACE' => [
            'button_caption' => $arOptions['SUBMIT_TEXT'] ?? 'Send',
            'fields' => $arOptions['REPLACE_FIELDS'] ?? [],
        ],
        'HIDDEN_ERROR' => $arOptions['HIDDEN_ERROR'] ?? 'N',
    ],
    $arOptions['PARENT_COMPONENT'] ?? false,
    ['HIDE_ICONS' => 'Y']
);

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Aspro\Premier\Functions\ExtComponentParameter;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('iblock')) {
    return;
}

ExtComponentParameter::init(__DIR__, []);

$arFromTheme = $arTmpConfig = [];
/* check for custom option */
if (isset($_REQUEST['src_path'])) {
    $_SESSION['src_path_component'] = $_REQUEST['src_path'];
}
if (
    !isset($_SESSION['src_path_component'])
    || strpos((string)$_SESSION['src_path_component'], 'custom') === false
) {
    $arFromTheme = ['FROM_THEME' => Loc::getMessage('ASPRO__SELECT_PARAM__FROM_THEME')];
}

ExtComponentParameter::addSelectParameter('SHOW_TITLE_IN_BLOCK', [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
    'VALUES' => $arFromTheme + [
        'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
        'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
    ],
    'DEFAULT' => 'Y',
    'SORT' => 999,
]);

ExtComponentParameter::addSelectParameter('TITLE_POSITION', [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
    'VALUES' => $arFromTheme + [
        'NORMAL' => Loc::getMessage('ASPRO__SELECT_PARAM__NORMAL'),
        'CENTERED' => Loc::getMessage('ASPRO__SELECT_PARAM__CENTERED'),
    ],
    'DEFAULT' => 'Y',
    'SORT' => 999,
]);

$arSectionFields = CIBlockParameters::GetSectionFieldCode(
    GetMessage('SORT'),
    'DATA_SOURCE',
    []
);
$arSectionFields['MULTIPLE'] = 'N';
$arSectionFields['SIZE'] = '1';
$arSectionFields['DEFAULT'] = 'SORT';

$arSectionFields2 = $arSectionFields;
$arSectionFields2['NAME'] = GetMessage('SORT_2');
$arSectionFields2['DEFAULT'] = 'ID';

$arSort = CIBlockParameters::GetElementSortFields(
    ['SHOWS', 'SORT', 'TIMESTAMP_X', 'NAME', 'ID', 'ACTIVE_FROM', 'ACTIVE_TO'],
    ['KEY_LOWERCASE' => 'Y']
);
if (Loader::includeModule('catalog')) {
    $arSort = array_merge(
        $arSort,
        CCatalogIBlockParameters::GetCatalogSortFields(),
        [
            'PROPERTY_MINIMUM_PRICE' => GetMessage('SORT_PRICES_MINIMUM_PRICE'),
            'PROPERTY_MAXIMUM_PRICE' => GetMessage('SORT_PRICES_MAXIMUM_PRICE'),
            'REGION_PRICE' => GetMessage('SORT_PRICES_REGION_PRICE')
        ]
    );
    if (isset($arSort['CATALOG_AVAILABLE'])) {
        unset($arSort['CATALOG_AVAILABLE']);
    }
}

$arTemplateParameters = [
    'TITLE' => [
        'NAME' => Loc::getMessage('T_TITLE'),
        'TYPE' => 'STRING',
        'DEFAULT' => Loc::getMessage('V_TITLE'),
    ],
    'RIGHT_TITLE' => [
        'NAME' => Loc::getMessage('T_RIGHT_TITLE'),
        'TYPE' => 'STRING',
        'DEFAULT' => Loc::getMessage('V_RIGHT_TITLE'),
    ],
    'RIGHT_LINK' => [
        'NAME' => Loc::getMessage('T_RIGHT_LINK'),
        'TYPE' => 'STRING',
        'DEFAULT' => '',
    ],
    'SORT' => $arSectionFields,
    'SORT_ORDER' => [
        'NAME' => GetMessage('SORT_ORDER'),
        'PARENT' => 'DATA_SOURCE',
        'TYPE' => 'LIST',
        'VALUES' => [
            'ASC' => GetMessage('SORT_ASC'),
            'DESC' => GetMessage('SORT_DESC'),
        ],
        'DEFAULT' => 'ASC',
    ],
    'SORT_2' => $arSectionFields2,
    'SORT_ORDER_2' => [
        'NAME' => GetMessage('SORT_ORDER_2'),
        'PARENT' => 'DATA_SOURCE',
        'TYPE' => 'LIST',
        'VALUES' => [
            'ASC' => GetMessage('SORT_ASC'),
            'DESC' => GetMessage('SORT_DESC'),
        ],
        'DEFAULT' => 'ASC',
    ],
    'STORY_PRODUCTS_ITEMS_COUNT' => [
        'NAME' => Loc::getMessage('STORY_PRODUCTS_ITEMS_COUNT'),
        'TYPE' => 'STRING',
        'DEFAULT' => '20',
    ],
    'STORY_PRODUCTS_SORT_FIELD' => [
        'NAME' => GetMessage('STORY_PRODUCTS_SORT_FIELD'),
        'TYPE' => 'LIST',
        'VALUES' => $arSort,
        'ADDITIONAL_VALUES' => 'Y',
        'DEFAULT' => 'sort',
    ],
    'STORY_PRODUCTS_SORT_ORDER' => [
        'NAME' => GetMessage('STORY_PRODUCTS_SORT_ORDER'),
        'TYPE' => 'LIST',
        'VALUES' => [
            'ASC' => GetMessage('SORT_ASC'),
            'DESC' => GetMessage('SORT_DESC'),
        ],
        'DEFAULT' => 'asc',
        'ADDITIONAL_VALUES' => 'Y',
    ],
];

ExtComponentParameter::appendTo($arTemplateParameters);

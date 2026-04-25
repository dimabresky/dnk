<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Aspro\Premier\Functions\ExtComponentParameter;
use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('iblock')) {
    return;
}

$arSKU = $boolSKU = false;
$arPropertySort = $arPropertySortDefault = $arPropertyDefaultSort = [];
$arPrice = $arProperty = $arProperty_N = $arProperty_X = $arProperty_F = [];
$arSort = CIBlockParameters::GetElementSortFields(
    ['SHOWS', 'SORT', 'TIMESTAMP_X', 'NAME', 'ID', 'ACTIVE_FROM', 'ACTIVE_TO'],
    ['KEY_LOWERCASE' => 'Y']
);
$arPropertySortDefault = ['SORT', 'SHOWS', 'NAME'];
$arPropertySort = [
    'SORT' => GetMessage('SORT_BUTTONS_SORT'),
    'SHOWS' => GetMessage('SORT_BUTTONS_POPULARITY'),
    'NAME' => GetMessage('SORT_BUTTONS_NAME'),
    // "CUSTOM"=>GetMessage("SORT_BUTTONS_CUSTOM")
];

if (Loader::includeModule('catalog')) {
    $arSort = array_merge($arSort, CCatalogIBlockParameters::GetCatalogSortFields(), ['PROPERTY_MINIMUM_PRICE' => GetMessage('SORT_PRICES_MINIMUM_PRICE'), 'PROPERTY_MAXIMUM_PRICE' => GetMessage('SORT_PRICES_MAXIMUM_PRICE'), 'REGION_PRICE' => GetMessage('SORT_PRICES_REGION_PRICE')]);
    if (isset($arSort['CATALOG_AVAILABLE'])) {
        unset($arSort['CATALOG_AVAILABLE']);
    }

    $rsPrice = CCatalogGroup::GetList($v1 = 'sort', $v2 = 'asc');
    while ($arr = $rsPrice->Fetch()) {
        $arPrice[$arr['NAME']] = '['.$arr['NAME'].'] '.$arr['NAME_LANG'];
    }
    if ((isset($arCurrentValues['IBLOCK_ID']) && (int) $arCurrentValues['IBLOCK_ID']) > 0) {
        $arSKU = CCatalogSKU::GetInfoByProductIBlock($arCurrentValues['IBLOCK_ID']);
        $boolSKU = !empty($arSKU) && is_array($arSKU);
    }
    $arPropertySortDefault = array_merge($arPropertySortDefault, ['PRICES', 'QUANTITY']);
    $arPropertySort = array_merge($arPropertySort, [
        'PRICES' => GetMessage('SORT_BUTTONS_PRICE'),
        'QUANTITY' => GetMessage('SORT_BUTTONS_QUANTITY'),
    ]);
} else {
    $arPrice = $arProperty_N;
}

$propertyIterator = Iblock\PropertyTable::getList([
    'select' => ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'LINK_IBLOCK_ID', 'USER_TYPE', 'SORT'],
    'filter' => [
        '=IBLOCK_ID' => $arCurrentValues['LINK_GOODS_IBLOCK_ID'],
        '=ACTIVE' => 'Y',
    ],
    'order' => [
        'SORT' => 'ASC',
        'NAME' => 'ASC',
    ],
]);
while ($property = $propertyIterator->fetch()) {
    $propertyCode = (string) $property['CODE'];

    if ($propertyCode == '') {
        $propertyCode = $property['ID'];
    }

    $propertyName = '['.$propertyCode.'] '.$property['NAME'];
    $arPropertySort[$propertyCode] = $propertyName;

    if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE) {
        $arProperty[$propertyCode] = $propertyName;

        if ($property['MULTIPLE'] == 'Y') {
            $arProperty_X[$propertyCode] = $propertyName;
        } elseif ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_LIST) {
            $arProperty_X[$propertyCode] = $propertyName;
        } elseif ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_ELEMENT && (int) $property['LINK_IBLOCK_ID'] > 0) {
            $arProperty_X[$propertyCode] = $propertyName;
        }
    } else {
        $arProperty_F[$propertyCode] = $propertyName;
    }

    if ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_NUMBER) {
        $arProperty_N[$propertyCode] = $propertyName;
    }

    if ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_STRING) {
        $arProperty_S[$propertyCode] = $propertyName;
    }
}

unset($propertyCode, $propertyName, $property, $propertyIterator);

if ($arCurrentValues['SORT_PROP']) {
    foreach ($arCurrentValues['SORT_PROP'] as $code) {
        $arPropertyDefaultSort[$code] = $arPropertySort[$code];
    }
} else {
    foreach ($arPropertySortDefault as $code) {
        $arPropertyDefaultSort[$code] = $arPropertySort[$code];
    }
}

$arIBlocks = [];
$rsIBlock = CIBlock::GetList(
    [
        'ID' => 'ASC',
    ],
    [
        // 'TYPE' => $arCurrentValues['IBLOCK_TYPE'],
        'ACTIVE' => 'Y',
    ]
);
while ($arIBlock = $rsIBlock->Fetch()) {
    $arIBlocks[$arIBlock['ID']] = "[{$arIBlock['ID']}] {$arIBlock['NAME']}";
}

if ($arCurrentValues['SORT_PROP']) {
    foreach ($arCurrentValues['SORT_PROP'] as $code) {
        $arPropertyDefaultSort[$code] = $arPropertySort[$code];
    }
} else {
    foreach ($arPropertySortDefault as $code) {
        $arPropertyDefaultSort[$code] = $arPropertySort[$code];
    }
}

$arAscDesc = [
    'asc' => GetMessage('IBLOCK_SORT_ASC'),
    'desc' => GetMessage('IBLOCK_SORT_DESC'),
];

$arRegionPrice = $arPrice;
if (Loader::includeModule('catalog')) {
    $arPriceSort = array_merge(['MINIMUM_PRICE' => GetMessage('SORT_PRICES_MINIMUM_PRICE'), 'MAXIMUM_PRICE' => GetMessage('SORT_PRICES_MAXIMUM_PRICE'), 'REGION_PRICE' => GetMessage('SORT_PRICES_REGION_PRICE')], $arPrice);
}

ExtComponentParameter::init(__DIR__, $arCurrentValues);

ExtComponentParameter::addBaseParameters([
    [
        ['SECTION' => 'SECTION', 'OPTION' => 'BRANDS_PAGE'],
        'SECTION_ELEMENTS_TYPE_VIEW',
    ],
    // array(
    // 	array('SECTION' => 'SECTION', 'OPTION' => 'BRANDS_DETAIL_PAGE'),
    // 	'ELEMENT_TYPE_VIEW'
    // ),
]);

ExtComponentParameter::addRelationBlockParameters([
    ExtComponentParameter::RELATION_BLOCK_ARTICLES,
    ExtComponentParameter::RELATION_BLOCK_BRANDS,
    ExtComponentParameter::RELATION_BLOCK_LANDINGS,
    ExtComponentParameter::RELATION_BLOCK_NEWS,
    ExtComponentParameter::RELATION_BLOCK_PROJECTS,
    ExtComponentParameter::RELATION_BLOCK_REVIEWS,
    ExtComponentParameter::RELATION_BLOCK_SERVICES,
    ExtComponentParameter::RELATION_BLOCK_STAFF,
    ExtComponentParameter::RELATION_BLOCK_TIZERS,
    ExtComponentParameter::RELATION_BLOCK_VACANCY,

    ExtComponentParameter::RELATION_BLOCK_DOCS,
    ExtComponentParameter::RELATION_BLOCK_LINK_GOODS,
    ExtComponentParameter::RELATION_BLOCK_LINK_SECTIONS,
    ExtComponentParameter::RELATION_BLOCK_COMMENTS,
    ExtComponentParameter::RELATION_BLOCK_COLLECTIONS,
]);

ExtComponentParameter::addTextParameter('DEPTH_LEVEL_BRAND', [
    'NAME' => GetMessage('T_DEPTH_LEVEL_BRAND'),
    'DEFAULT' => 2,
]);

if (strpos($arCurrentValues['SECTION_ELEMENTS_TYPE_VIEW'], 'list_elements_1') !== false) {
    ExtComponentParameter::addSelectParameter('FON', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'VALUES' => [
            'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'N',
        'SORT' => 999,
    ]);

    ExtComponentParameter::addSelectParameter('BORDERED', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'VALUES' => [
            'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'N',
        'SORT' => 999,
    ]);
}

if (strpos($arCurrentValues['SECTION_ELEMENTS_TYPE_VIEW'], 'list_elements_2') !== false) {
    ExtComponentParameter::addSelectParameter('FON', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'VALUES' => [
            'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'N',
        'SORT' => 999,
    ]);

    ExtComponentParameter::addSelectParameter('BORDERED', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'VALUES' => [
            'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'N',
        'SORT' => 999,
    ]);

    ExtComponentParameter::addSelectParameter('ELEMENTS_IN_ROW', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW'),
        'VALUES' => [
            '4' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 4]),
            '5' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 5]),
            '6' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 6]),
        ],
        'DEFAULT' => '4',
        'SORT' => 999,
    ]);
}

ExtComponentParameter::appendTo($arTemplateParameters);

$arTemplateParameters['SHOW_DETAIL_LINK'] = [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
    'NAME' => Loc::getMessage('SHOW_DETAIL_LINK'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
];

$arTemplateParameters['USE_SHARE'] = [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
    'NAME' => Loc::getMessage('USE_SHARE'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
];

$arTemplateParameters['SORT_PROP'] = [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_DETAIL,
    'NAME' => GetMessage('T_SORT_PROP'),
    'TYPE' => 'LIST',
    'VALUES' => array_merge([/* "CUSTOM"=>GetMessage("SORT_BUTTONS_CUSTOM") */], $arPropertySort),
    'DEFAULT' => $arPropertySortDefault,
    'SIZE' => 5,
    'MULTIPLE' => 'Y',
    'REFRESH' => 'Y',
];

$arTemplateParameters['SORT_PROP_DEFAULT'] = [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_DETAIL,
    'NAME' => GetMessage('T_SORT_PROP_DEFAULT'),
    'TYPE' => 'LIST',
    'VALUES' => $arPropertyDefaultSort,
];

$arTemplateParameters['SORT_DIRECTION'] = [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_DETAIL,
    'NAME' => GetMessage('T_SORT_DIRECTION'),
    'TYPE' => 'LIST',
    'VALUES' => $arAscDesc,
];

if (is_array($arCurrentValues['SORT_PROP'])) {
    if (in_array('PRICES', $arCurrentValues['SORT_PROP'])) {
        $arTemplateParameters['SORT_PRICES'] = [
            'SORT' => 200,
            'NAME' => GetMessage('SORT_PRICES'),
            'TYPE' => 'LIST',
            'VALUES' => $arPriceSort,
            'DEFAULT' => ['MINIMUM_PRICE'],
            'PARENT' => 'DETAIL_SETTINGS',
            'MULTIPLE' => 'N',
        ];
        $arTemplateParameters['SORT_REGION_PRICE'] = [
            'SORT' => 200,
            'NAME' => GetMessage('SORT_REGION_PRICE'),
            'TYPE' => 'LIST',
            'VALUES' => $arRegionPrice,
            'DEFAULT' => ['BASE'],
            'PARENT' => 'DETAIL_SETTINGS',
            'MULTIPLE' => 'N',
        ];
    }
}

$arTemplateParameters['VIEW_TYPE'] = [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_DETAIL,
    'NAME' => GetMessage('DEFAULT_LIST_TEMPLATE'),
    'TYPE' => 'LIST',
    'VALUES' => [
        'table' => GetMessage('DEFAULT_LIST_TEMPLATE_BLOCK'),
        'list' => GetMessage('DEFAULT_LIST_TEMPLATE_LIST'),
        'price' => GetMessage('DEFAULT_LIST_TEMPLATE_TABLE')],
    'DEFAULT' => 'table',
];

/* check for custom option */
$siteID = SITE_ID;
if (isset($_REQUEST['src_site']) || isset($_REQUEST['site'])) {
    $siteID = $_REQUEST['src_site'] ?: $_REQUEST['site'];
}
$viewTemplate = $arCurrentValues['SECTION_ELEMENTS_TYPE_VIEW'];

if ($viewTemplate === 'FROM_MODULE') {
    if (isset($_SESSION)
        && isset($_SESSION['THEME'])
        && isset($_SESSION['THEME'][$siteID])
        && isset($_SESSION['THEME'][$siteID]['BRANDS_PAGE'])
    ) {
        $viewTemplate = $_SESSION['THEME'][$siteID]['BRANDS_PAGE'];
    } else {
        $viewTemplate = Bitrix\Main\Config\Option::get(CPremier::moduleID, 'BRANDS_PAGE', '', $siteID);
    }
}
if (strpos($viewTemplate, 'with_group') !== false) {
    $arTemplateParameters['USE_AGENT'] = [
        'NAME' => GetMessage('T_USE_AGENT'),
        'TYPE' => 'CHECKBOX',
        'DEFAULT' => 'N',
        'PARENT' => 'LIST_SETTINGS',
    ];
}

$detailPage = Bitrix\Main\Config\Option::get(CPremier::moduleID, 'BRANDS_DETAIL_PAGE', '', $siteID);
if (!empty($_SESSION['THEME'][$siteID]['BRANDS_DETAIL_PAGE'])) {
    $detailPage = $_SESSION['THEME'][$siteID]['BRANDS_DETAIL_PAGE'];
}
if ($detailPage === 'catalog') {
    $arTemplateParameters['USE_FILTER_PRICE'] = [
        'PARENT' => 'FILTER_SETTINGS',
        'NAME' => GetMessage('USE_FILTER_PRICE_TITLE'),
        'TYPE' => 'CHECKBOX',
        'DEFAULT' => 'N',
        'REFRESH' => 'Y',
        'SORT' => 600,
    ];
    if (isset($arCurrentValues['USE_FILTER_PRICE']) && $arCurrentValues['USE_FILTER_PRICE'] == 'Y') {
        $arTemplateParameters['FILTER_PRICE_CODE'] = [
            'PARENT' => 'FILTER_SETTINGS',
            'NAME' => GetMessage('FILTER_PRICE_CODE_TITLE'),
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => $arPrice,
            'SORT' => 601,
        ];
    }
}

if (stripos($detailPage, 'sections') !== false) {
    $arTemplateParameters['SECTION_IMAGES'] = [
        'TYPE' => 'LIST',
        'PARENT' => ExtComponentParameter::PARENT_GROUP_DETAIL,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__IMAGES'),
        'VALUES' => [
            'FROM_MODULE' => GetMessage('ASPRO__SELECT_PARAM__FROM_THEME'),
            'ICONS' => GetMessage('ASPRO__SELECT_PARAM__ICONS'),
            'PICTURES' => GetMessage('ASPRO__SELECT_PARAM__PICTURES'),
            'TRANSPARENT_PICTURES' => GetMessage('TRANSPARENT_PICTURES'),
        ],
        'DEFAULT' => 'FROM_MODULE',
        'SORT' => 999,
    ];
}

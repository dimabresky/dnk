<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

global $USER_FIELD_MANAGER;
use Aspro\Premier\Functions\ExtComponentParameter;
use Aspro\Premier\Product\Blocks;
use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

if (
    !Loader::includeModule('iblock')
    || !Loader::includeModule('aspro.premier')
) {
    return;
}

CBitrixComponent::includeComponentClass('bitrix:catalog.section');

$arSKU = $boolSKU = false;
$arPropertySort = $arPropertySortDefault = $arPropertyDefaultSort = [];
$arPrice = $arProperty = $arProperty_N = $arProperty_X = $arProperty_F = $arProperty_S = [];

$arAscDesc = [
    'asc' => GetMessage('IBLOCK_SORT_ASC'),
    'desc' => GetMessage('IBLOCK_SORT_DESC'),
];
$arSort = CIBlockParameters::GetElementSortFields(
    ['SHOWS', 'SORT', 'TIMESTAMP_X', 'NAME', 'ID', 'ACTIVE_FROM', 'ACTIVE_TO'],
    ['KEY_LOWERCASE' => 'Y']
);
$arPropertySortDefault = ['SORT', 'SHOWS', 'NAME'];
$arPropertySort = [
    'SORT' => GetMessage('SORT_BUTTONS_SORT'),
    'SHOWS' => GetMessage('SORT_BUTTONS_POPULARITY'),
    'NAME' => GetMessage('SORT_BUTTONS_NAME'),
    'CUSTOM' => GetMessage('SORT_BUTTONS_CUSTOM'),
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
        '=IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'],
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

    $arProperty[$propertyCode] = $propertyName;
    if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE) {
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
        if (!empty($arPropertySort[$code])) {
            $arPropertyDefaultSort[$code] = $arPropertySort[$code];
        }
    }
} else {
    foreach ($arPropertySortDefault as $code) {
        if (!empty($arPropertySort[$code])) {
            $arPropertyDefaultSort[$code] = $arPropertySort[$code];
        }
    }
}

$arRegionPrice = $arPrice;
if (Loader::includeModule('catalog')) {
    $arPrice = array_merge(['MINIMUM_PRICE' => GetMessage('SORT_PRICES_MINIMUM_PRICE'), 'MAXIMUM_PRICE' => GetMessage('SORT_PRICES_MAXIMUM_PRICE'), 'REGION_PRICE' => GetMessage('SORT_PRICES_REGION_PRICE')], $arPrice);
}

$arUserFields_S = $arUserFields_E = [];
$arUserFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_'.$arCurrentValues['IBLOCK_ID'].'_SECTION');
foreach ($arUserFields as $FIELD_NAME => $arUserField) {
    if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum') {
        $arUserFields_E[$FIELD_NAME] = $arUserField['LIST_COLUMN_LABEL'] ? $arUserField['LIST_COLUMN_LABEL'] : $FIELD_NAME;
    }

    if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'string') {
        $arUserFields_S[$FIELD_NAME] = $arUserField['LIST_COLUMN_LABEL'] ? $arUserField['LIST_COLUMN_LABEL'] : $FIELD_NAME;
    }
}
$arIBlocks = [];
$rsIBlock = CIBlock::GetList(
    [
        'ID' => 'ASC',
    ],
    [
        'TYPE' => $arCurrentValues['IBLOCK_TYPE'],
        'ACTIVE' => 'Y',
    ]
);
while ($arIBlock = $rsIBlock->Fetch()) {
    $arIBlocks[$arIBlock['ID']] = "[{$arIBlock['ID']}] {$arIBlock['NAME']}";
}

$arTemplateParametersParts = [];

ExtComponentParameter::init(__DIR__, $arCurrentValues);
ExtComponentParameter::addBaseParameters([
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'SECTIONS_TYPE_VIEW_CATALOG'],
        'SECTIONS_TYPE_VIEW',
    ],
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'SECTION_TYPE_VIEW_CATALOG'],
        'SECTION_TYPE_VIEW',
    ],
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'ELEMENTS_CATALOG'],
    ],
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'CATALOG'],
    ],
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'ELEMENTS_TABLE_TYPE_VIEW'],
    ],
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'ELEMENTS_LIST_TYPE_VIEW'],
    ],
    [
        ['SECTION' => 'CATALOG_PAGE', 'OPTION' => 'ELEMENTS_PRICE_TYPE_VIEW'],
    ],
]);

ExtComponentParameter::addRelationBlockParameters([
    ExtComponentParameter::RELATION_BLOCK_DESC,
    ExtComponentParameter::RELATION_BLOCK_CHAR,
    [
        ExtComponentParameter::RELATION_BLOCK_GALLERY,
        'additionalParams' => [
            'toggle' => true,
            // 'type' => array(
            // 	ExtComponentParameter::GALLERY_TYPE_BIG,
            // 	ExtComponentParameter::GALLERY_TYPE_SMALL,
            // )
        ],
    ],
    ExtComponentParameter::RELATION_BLOCK_VIDEO,
    ExtComponentParameter::RELATION_BLOCK_DOCS,
    ExtComponentParameter::RELATION_BLOCK_FAQ,
    ExtComponentParameter::RELATION_BLOCK_REVIEWS,
    ExtComponentParameter::RELATION_BLOCK_SALE,
    ExtComponentParameter::RELATION_BLOCK_ARTICLES,
    ExtComponentParameter::RELATION_BLOCK_SERVICES,
    ExtComponentParameter::RELATION_BLOCK_BUY_SERVICES,
    ExtComponentParameter::RELATION_BLOCK_SKU,
    ExtComponentParameter::RELATION_BLOCK_ASSOCIATED,
    ExtComponentParameter::RELATION_BLOCK_EXPANDABLES,
    ExtComponentParameter::RELATION_BLOCK_RECOMS,
    [
        ExtComponentParameter::RELATION_BLOCK_BUY,
        'additionalParams' => [
            'toggle' => false,
        ],
    ],
    [
        ExtComponentParameter::RELATION_BLOCK_PAYMENT,
        'additionalParams' => [
            'toggle' => false,
        ],
    ],
    [
        ExtComponentParameter::RELATION_BLOCK_DELIVERY,
        'additionalParams' => [
            'toggle' => false,
        ],
    ],
    [
        ExtComponentParameter::RELATION_BLOCK_DOPS,
        'additionalParams' => [
            'toggle' => false,
        ],
    ],
    ExtComponentParameter::RELATION_BLOCK_CATALOG_COMMENTS,
]);

// ADD CUSTOM BLOCKS
$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$siteId = $request->get('src_site') ?? $request->get('site');

$arCurrentValues['CUSTOM_DETAIL_BLOCKS'] = $arCurrentValues['CUSTOM_DETAIL_BLOCKS'] ?? '';
$blocksObj = new Blocks($siteId, $arCurrentValues['CUSTOM_DETAIL_BLOCKS']);
$curBlockList = implode(',', array_keys($blocksObj->customBlocks));
$customTabsParam = Blocks::formatForOrder($blocksObj->getTabs());
$customBlocksParam = Blocks::formatForOrder($blocksObj->getBlocks());

ExtComponentParameter::addSelectParameter('CUSTOM_PROPERTY_DATA', [
    'SORT' => 700,
    'VALUES' => $arProperty,
    'DEFAULT' => [],
    'MULTIPLE' => 'Y',
    'SIZE' => 8,
]);
ExtComponentParameter::addCustomParameter('CUSTOM_DETAIL_BLOCKS', [
    'SORT' => 701,
    'JS_FILE' => Bitrix\Main\Page\Asset::getInstance()->getFullAssetPath('/bitrix/js/aspro.premier/settings/custom_sorted_blocks/script.min.js'),
    'JS_EVENT' => 'initCustomSortedBlocks',
    'JS_DATA' => str_replace('\'', '"', CUtil::PhpToJSObject(
        [
            'checkable' => true,
            'sortable' => false,
            'showPropsAlways' => true,
            'disableAdd' => true,
            'header' => [
                'code' => 'name',
                'title' => Loc::getMessage('ASPRO__CUSTOM_PARAM__CUSTOM_DETAIL_BLOCKS_TITLE'),
                'allowEmpty' => true,
            ],
            'props' => [
                [
                    'code' => 'is_tab',
                    'title' => Loc::getMessage('ASPRO__CUSTOM_PARAM__CUSTOM_DETAIL_BLOCKS_IS_TAB'),
                    'type' => 'checkbox',
                ],
                [
                    'code' => 'id',
                    'title' => Loc::getMessage('ASPRO__CUSTOM_PARAM__CUSTOM_DETAIL_BLOCKS_CODE'),
                    'type' => 'note',
                    // 'rand' => true,
                ],
            ],
            'currentValue' => $curBlockList,
        ]
    )),
    'DEFAULT' => '',
    'REFRESH' => 'Y',
]);

ExtComponentParameter::addOrderBlockParameters(array_merge([
    // ExtComponentParameter::ORDER_BLOCK_SALE,
    ExtComponentParameter::ORDER_BLOCK_TABS,
    ExtComponentParameter::ORDER_BLOCK_GALLERY,
    ExtComponentParameter::ORDER_BLOCK_SKU,
    ExtComponentParameter::ORDER_BLOCK_BUY_SERVICES,
    ExtComponentParameter::ORDER_BLOCK_SERVICES,
    ExtComponentParameter::ORDER_BLOCK_ARTICLES,
    ExtComponentParameter::ORDER_BLOCK_ASSOCIATED,
    ExtComponentParameter::ORDER_BLOCK_EXPANDABLES,
    ExtComponentParameter::ORDER_BLOCK_RECOMS,
    ExtComponentParameter::ORDER_BLOCK_COMPLECT,
    ExtComponentParameter::ORDER_BLOCK_KIT,
    ExtComponentParameter::ORDER_BLOCK_GIFT,
], $customBlocksParam));

ExtComponentParameter::addOrderTabParameters(array_merge([
    ExtComponentParameter::ORDER_BLOCK_DESC,
    ExtComponentParameter::ORDER_BLOCK_CHAR,
    ExtComponentParameter::ORDER_BLOCK_VIDEO,
    ExtComponentParameter::ORDER_BLOCK_DOCS,
    ExtComponentParameter::ORDER_BLOCK_FAQ,
    ExtComponentParameter::ORDER_BLOCK_CATALOG_COMMENTS,
    ExtComponentParameter::ORDER_BLOCK_BUY,
    ExtComponentParameter::ORDER_BLOCK_PAYMENT,
    ExtComponentParameter::ORDER_BLOCK_DELIVERY,
    ExtComponentParameter::ORDER_BLOCK_DOPS,
], $customTabsParam));

ExtComponentParameter::addOrderAllParameters(array_merge([
    // ExtComponentParameter::ORDER_BLOCK_SALE,
    ExtComponentParameter::ORDER_BLOCK_DESC,
    ExtComponentParameter::ORDER_BLOCK_CHAR,
    ExtComponentParameter::ORDER_BLOCK_REVIEWS,
    ExtComponentParameter::ORDER_BLOCK_GALLERY,
    ExtComponentParameter::ORDER_BLOCK_VIDEO,
    ExtComponentParameter::ORDER_BLOCK_SKU,
    ExtComponentParameter::ORDER_BLOCK_BUY_SERVICES,
    ExtComponentParameter::ORDER_BLOCK_SERVICES,
    ExtComponentParameter::ORDER_BLOCK_ARTICLES,
    ExtComponentParameter::ORDER_BLOCK_DOCS,
    ExtComponentParameter::ORDER_BLOCK_FAQ,
    ExtComponentParameter::ORDER_BLOCK_ASSOCIATED,
    ExtComponentParameter::ORDER_BLOCK_EXPANDABLES,
    ExtComponentParameter::ORDER_BLOCK_RECOMS,
    ExtComponentParameter::ORDER_BLOCK_BUY,
    ExtComponentParameter::ORDER_BLOCK_PAYMENT,
    ExtComponentParameter::ORDER_BLOCK_DELIVERY,
    ExtComponentParameter::ORDER_BLOCK_DOPS,
    ExtComponentParameter::ORDER_BLOCK_CATALOG_COMMENTS,
    ExtComponentParameter::ORDER_BLOCK_COMPLECT,
    ExtComponentParameter::ORDER_BLOCK_KIT,
    ExtComponentParameter::ORDER_BLOCK_GIFT,
], $customBlocksParam, $customTabsParam));

ExtComponentParameter::addUseTabParameter('USE_DETAIL_TABS');

ExtComponentParameter::addCheckBoxParameter('USE_SHARE', [
    'DEFAULT' => 'N',
]);
ExtComponentParameter::addCheckBoxParameter('HEADING_COUNT_ELEMENTS', [
    'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
    'NAME' => GetMessage('T_HEADING_COUNT_ELEMENTS'),
    'DEFAULT' => 'Y',
]);

if (strpos($arCurrentValues['SECTIONS_TYPE_VIEW'], 'sections_1') !== false) {
    ExtComponentParameter::addSelectParameter('SECTIONS_HIGH_ELEMENT', [
        'PARENT' => 'SECTIONS_SETTINGS',
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__HIGH_ELEMENT'),
        'VALUES' => [
            'Y' => GetMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => GetMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'Y',
        'SORT' => 999,
    ]);
    ExtComponentParameter::addSelectParameter('SECTIONS_ELEMENTS_COUNT', [
        'PARENT' => 'SECTIONS_SETTINGS',
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW'),
        'VALUES' => [
            '3' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 3]),
            '4' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 4]),
            '5' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 5]),
        ],
        'DEFAULT' => '4',
        'SORT' => 999,
    ]);
}
if (strpos($arCurrentValues['SECTIONS_TYPE_VIEW'], 'sections_2') !== false) {
    ExtComponentParameter::addSelectParameter('SECTIONS_IMAGES_POSITION', [
        'PARENT' => 'SECTIONS_SETTINGS',
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__IMAGES_POSITION'),
        'VALUES' => [
            'LEFT' => GetMessage('ASPRO__SELECT_PARAM__LEFT'),
            'TOP' => GetMessage('ASPRO__SELECT_PARAM__TOP'),
        ],
        'DEFAULT' => 'TOP',
        'SORT' => 999,
    ]);
    ExtComponentParameter::addSelectParameter('SECTIONS_IMAGES', [
        'PARENT' => 'SECTIONS_SETTINGS',
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__IMAGES'),
        'VALUES' => [
            'ICONS' => GetMessage('ASPRO__SELECT_PARAM__ICONS'),
            'PICTURES' => GetMessage('ASPRO__SELECT_PARAM__PICTURES'),
            'TRANSPARENT_PICTURES' => GetMessage('TRANSPARENT_PICTURES'),
        ],
        'DEFAULT' => 'PICTURES',
        'SORT' => 999,
    ]);
}
if (strpos($arCurrentValues['SECTION_TYPE_VIEW'], 'section_1') !== false) {
    ExtComponentParameter::addSelectParameter('SECTION_BORDERED', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__BORDERED'),
        'VALUES' => [
            'Y' => GetMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => GetMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'Y',
        'SORT' => 999,
    ]);
    ExtComponentParameter::addSelectParameter('SECTION_IMAGE_ON_FON', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__IMAGE_ON_FON'),
        'VALUES' => [
            'Y' => GetMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => GetMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'Y',
        'SORT' => 999,
    ]);
    ExtComponentParameter::addSelectParameter('SECTION_IMAGES', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__IMAGES'),
        'VALUES' => [
            'ICONS' => GetMessage('ASPRO__SELECT_PARAM__ICONS'),
            'PICTURES' => GetMessage('ASPRO__SELECT_PARAM__PICTURES'),
            'TRANSPARENT_PICTURES' => GetMessage('TRANSPARENT_PICTURES'),
        ],
        'DEFAULT' => 'PICTURES',
        'SORT' => 999,
    ]);
}
if (strpos($arCurrentValues['SECTION_TYPE_VIEW'], 'section_2') !== false) {
    ExtComponentParameter::addSelectParameter('SECTION_HIGH_ELEMENT', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__HIGH_ELEMENT'),
        'VALUES' => [
            'Y' => GetMessage('ASPRO__SELECT_PARAM__YES'),
            'N' => GetMessage('ASPRO__SELECT_PARAM__NO'),
        ],
        'DEFAULT' => 'Y',
        'SORT' => 999,
    ]);
    ExtComponentParameter::addSelectParameter('SECTION_ELEMENTS_COUNT', [
        'PARENT' => ExtComponentParameter::PARENT_GROUP_LIST,
        'NAME' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW'),
        'VALUES' => [
            '3' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 3]),
            '4' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 4]),
            '5' => GetMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 5]),
        ],
        'DEFAULT' => '4',
        'SORT' => 999,
    ]);
}

ExtComponentParameter::appendTo($arTemplateParameters);

if ($arCurrentValues['ELEMENTS_TABLE_TYPE_VIEW'] !== 'FROM_MODULE') {
    $arTemplateParametersParts[] = [
        'SECTION_ITEM_LIST_IMG_CORNER' => [
            'PARENT' => 'LIST_SETTINGS',
            'NAME' => GetMessage('SECTION_ITEM_LIST_IMG_CORNER'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'SORT' => 1501,
        ],
        'SECTION_ITEM_LIST_BORDERED' => [
            'PARENT' => 'LIST_SETTINGS',
            'NAME' => GetMessage('SECTION_ITEM_LIST_BORDERED'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'SORT' => 1502,
        ],
    ];
}

$arTemplateParameters['LANDING_IBLOCK_ID'] = [
    'NAME' => GetMessage('T_LANDING_IBLOCK_ID'),
    'TYPE' => 'STRING',
    'DEFAULT' => '',
    'PARENT' => 'LIST_SETTINGS',
];

$arTemplateParameters['LANDING_SECTION_COUNT'] = [
    'NAME' => GetMessage('T_LANDING_SECTION_COUNT'),
    'TYPE' => 'STRING',
    'DEFAULT' => '20',
    'PARENT' => 'LIST_SETTINGS',
];

$arTemplateParameters['LANDING_SECTION_COUNT_VISIBLE'] = [
    'NAME' => GetMessage('T_LANDING_SECTION_COUNT_VISIBLE'),
    'TYPE' => 'STRING',
    'DEFAULT' => '3',
    'PARENT' => 'LIST_SETTINGS',
];

if (Loader::includeModule('aspro.smartseo')) {
    $arTemplateParametersParts[] = [
        'SHOW_SMARTSEO_TAGS' => [
            'PARENT' => 'LIST_SETTINGS',
            'NAME' => GetMessage('SHOW_SMARTSEO_TAGS_TITLE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y',
        ],
    ];
    if ($arCurrentValues['SHOW_SMARTSEO_TAGS'] === 'Y') {
        $arTemplateParametersParts[] = [
            'SMARTSEO_TAGS_COUNT' => [
                'NAME' => GetMessage('SMARTSEO_TAGS_COUNT'),
                'TYPE' => 'STRING',
                'DEFAULT' => '10',
                'PARENT' => 'LIST_SETTINGS',
            ],
            /*"SMARTSEO_TAGS_COUNT_MOBILE" => array(
                "NAME" => GetMessage("SMARTSEO_TAGS_COUNT_MOBILE"),
                "TYPE" => "STRING",
                "DEFAULT" => "3",
                "PARENT" => "LIST_SETTINGS",
            ),*/
            'SMARTSEO_TAGS_BY_GROUPS' => [
                'NAME' => GetMessage('SMARTSEO_TAGS_BY_GROUPS'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N',
                'PARENT' => 'LIST_SETTINGS',
            ],
            'SMARTSEO_TAGS_SHOW_DEACTIVATED' => [
                'PARENT' => 'LIST_SETTINGS',
                'NAME' => GetMessage('SMARTSEO_TAGS_SHOW_DEACTIVATED'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N',
            ],
            'SMARTSEO_TAGS_SORT' => [
                'PARENT' => 'LIST_SETTINGS',
                'NAME' => GetMessage('SMARTSEO_TAGS_SORT'),
                'TYPE' => 'LIST',
                'VALUES' => [
                    'NAME' => GetMessage('SMARTSEO_TAGS_SORT_NAME'),
                    'SORT' => GetMessage('SMARTSEO_TAGS_SORT_SORT'),
                ],
                'DEFAULT' => 'SORT',
            ],
        ];
    }
}

$arTemplateParameters['SORT_PROP'] = [
    'PARENT' => 'LIST_SETTINGS',
    'NAME' => GetMessage('T_SORT_PROP'),
    'TYPE' => 'LIST',
    'VALUES' => array_merge(['CUSTOM' => GetMessage('SORT_BUTTONS_CUSTOM')], $arPropertySort),
    // "VALUES" => array("SORT"=>GetMessage("SORT_BUTTONS_SORT"),"POPULARITY"=>GetMessage("SORT_BUTTONS_POPULARITY"), "NAME"=>GetMessage("SORT_BUTTONS_NAME"), "PRICE"=>GetMessage("SORT_BUTTONS_PRICE"), "QUANTITY"=>GetMessage("SORT_BUTTONS_QUANTITY"), "CUSTOM"=>GetMessage("SORT_BUTTONS_CUSTOM")) + (array)$arPropertySort,
    'DEFAULT' => $arPropertySortDefault,
    'SIZE' => 5,
    'MULTIPLE' => 'Y',
    'REFRESH' => 'Y',
];

$arTemplateParameters['SORT_PROP_DEFAULT'] = [
    'PARENT' => 'LIST_SETTINGS',
    'NAME' => GetMessage('T_SORT_PROP_DEFAULT'),
    'TYPE' => 'LIST',
    'VALUES' => $arPropertyDefaultSort,
];

$arTemplateParameters['SORT_DIRECTION'] = [
    'PARENT' => 'LIST_SETTINGS',
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
            'VALUES' => $arPrice,
            'DEFAULT' => ['MINIMUM_PRICE'],
            'PARENT' => 'LIST_SETTINGS',
            'MULTIPLE' => 'N',
        ];
        $arTemplateParameters['SORT_REGION_PRICE'] = [
            'SORT' => 200,
            'NAME' => GetMessage('SORT_REGION_PRICE'),
            'TYPE' => 'LIST',
            'VALUES' => $arRegionPrice,
            'DEFAULT' => ['BASE'],
            'PARENT' => 'LIST_SETTINGS',
            'MULTIPLE' => 'N',
        ];
    }
}

$arTemplateParameters = array_merge(
    $arTemplateParameters,
    [
        'RESTART' => [
            'PARENT' => 'SEARCH_SETTINGS',
            'NAME' => GetMessage('RESTART'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'USE_LANGUAGE_GUESS' => [
            'PARENT' => 'SEARCH_SETTINGS',
            'NAME' => GetMessage('USE_LANGUAGE_GUESS'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'NO_WORD_LOGIC' => [
            'PARENT' => 'SEARCH_SETTINGS',
            'NAME' => GetMessage('NO_WORD_LOGIC'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
        'SHOW_SORT_RANK_BUTTON' => [
            'PARENT' => 'SEARCH_SETTINGS',
            'NAME' => GetMessage('SHOW_SORT_RANK_BUTTON_TITLE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y',
        ],
        'SHOW_LANDINGS_SEARCH' => [
            'PARENT' => 'SEARCH_SETTINGS',
            'NAME' => GetMessage('SHOW_LANDINGS_SEARCH_TITLE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y',
        ],
    ]
);

if ($arCurrentValues['SHOW_LANDINGS_SEARCH'] !== 'N') {
    $arTemplateParameters = array_merge(
        $arTemplateParameters,
        [
            'LANDING_SEARCH_COUNT' => [
                'NAME' => GetMessage('LANDING_SEARCH_COUNT'),
                'TYPE' => 'STRING',
                'DEFAULT' => '10',
                'PARENT' => 'SEARCH_SETTINGS',
            ],
            'LANDING_SEARCH_COUNT_VISIBLE' => [
                'NAME' => GetMessage('LANDING_SEARCH_COUNT_VISIBLE'),
                'TYPE' => 'STRING',
                'DEFAULT' => '3',
                'PARENT' => 'SEARCH_SETTINGS',
            ],
        ]
    );
}

$arTemplateParameters['VIEW_TYPE'] = [
    'NAME' => GetMessage('DEFAULT_LIST_TEMPLATE'),
    'TYPE' => 'LIST',
    'VALUES' => [
        'tile' => GetMessage('DEFAULT_LIST_TEMPLATE_TILE'),
        'table' => GetMessage('DEFAULT_LIST_TEMPLATE_BLOCK'),
        'list' => GetMessage('DEFAULT_LIST_TEMPLATE_LIST'),
        'price' => GetMessage('DEFAULT_LIST_TEMPLATE_TABLE')],
    'DEFAULT' => 'table',
    'PARENT' => 'LIST_SETTINGS',
];

$arTemplateParameters['SHOW_LIST_TYPE_SECTION'] = [
    'PARENT' => 'LIST_SETTINGS',
    'NAME' => GetMessage('T_SHOW_LIST_TYPE_SECTION'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
];

$arTemplateParameters['SECTION_DISPLAY_PROPERTY'] = [
    'NAME' => GetMessage('SECTION_DISPLAY_PROPERTY'),
    'TYPE' => 'LIST',
    'VALUES' => $arUserFields_E,
    'DEFAULT' => 'list',
    'MULTIPLE' => 'N',
    'PARENT' => 'LIST_SETTINGS',
];

$arTemplateParameters['SECTION_TOP_BLOCK_TITLE'] = [
    'NAME' => GetMessage('SECTION_TOP_BLOCK_TITLE'),
    'TYPE' => 'STRING',
    'DEFAULT' => GetMessage('SECTION_TOP_BLOCK_TITLE_VALUE'),
    'PARENT' => 'TOP_SETTINGS',
];

$arTemplateParameters['SHOW_ASK_BLOCK'] = [
    'NAME' => GetMessage('SHOW_ASK_BLOCK'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['ASK_FORM_ID'] = [
    'NAME' => GetMessage('ASK_FORM_ID'),
    'TYPE' => 'STRING',
    'DEFAULT' => '',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['SHOW_CHEAPER_FORM'] = [
    'NAME' => GetMessage('SHOW_CHEAPER_FORM'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['CHEAPER_FORM_NAME'] = [
    'NAME' => GetMessage('CHEAPER_FORM_NAME'),
    'TYPE' => 'STRING',
    'DEFAULT' => '',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['SEND_GIFT_FORM_NAME'] = [
    'NAME' => GetMessage('SEND_GIFT_FORM_NAME'),
    'TYPE' => 'STRING',
    'DEFAULT' => '',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['SHOW_SEND_GIFT'] = [
    'NAME' => GetMessage('SHOW_SEND_GIFT'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
    'PARENT' => 'DETAIL_SETTINGS',
];
/*
$arTemplateParameters["SHOW_HINTS"] = array(
    "NAME" => GetMessage("SHOW_HINTS"),
    "TYPE" => "CHECKBOX",
    "DEFAULT" => "Y",
);
*/

$arTemplateParameters['IBLOCK_TIZERS_ID'] = [
    'NAME' => GetMessage('IBLOCK_TIZERS_NAME'),
    'TYPE' => 'STRING',
    'DEFAULT' => '',
];

$arTemplateParameters['SHOW_LANDINGS'] = [
    'PARENT' => 'LIST_SETTINGS',
    'NAME' => GetMessage('SHOW_LANDINGS_TITLE'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
    'REFRESH' => 'Y',
];

$arTemplateParameters['OPT_BUY'] = [
    'PARENT' => 'LIST_SETTINGS',
    'NAME' => GetMessage('T_OPT_BUY'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
];

$arTemplateParameters['PROPERTIES_DISPLAY_TYPE'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('PROPERTIES_DISPLAY_TYPE'),
    'TYPE' => 'LIST',
    'MULTIPLE' => 'N',
    'VALUES' => [
        'BLOCK' => GetMessage('PROPERTIES_DISPLAY_TYPE_BLOCK'),
        'TABLE' => GetMessage('PROPERTIES_DISPLAY_TYPE_TABLE'),
    ],
    'DEFAULT' => 'TABLE',
];

$arTemplateParameters['VISIBLE_PROP_COUNT'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('VISIBLE_PROP_COUNT_TITLE'),
    'TYPE' => 'STRING',
    'DEFAULT' => '6',
];

$arTemplateParameters['BUNDLE_ITEMS_COUNT'] = [
    'NAME' => GetMessage('BUNDLE_ITEMS_COUNT_TITLE'),
    'TYPE' => 'STRING',
    'DEFAULT' => '3',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['USE_DETAIL_PREDICTION'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('USE_DETAIL_PREDICTION_TITLE'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N',
];

$arTemplateParameters['LINKED_ELEMENT_TAB_SORT_FIELD'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('LINKED_ELEMENT_TAB_SORT_FIELD'),
    'TYPE' => 'LIST',
    'VALUES' => $arSort,
    'ADDITIONAL_VALUES' => 'Y',
    'DEFAULT' => 'sort',
];

$arTemplateParameters['LINKED_ELEMENT_TAB_SORT_ORDER'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('LINKED_ELEMENT_TAB_SORT_ORDER'),
    'TYPE' => 'LIST',
    'VALUES' => $arAscDesc,
    'DEFAULT' => 'asc',
    'ADDITIONAL_VALUES' => 'Y',
];

$arTemplateParameters['LINKED_ELEMENT_TAB_SORT_FIELD2'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('LINKED_ELEMENT_TAB_SORT_FIELD2'),
    'TYPE' => 'LIST',
    'VALUES' => $arSort,
    'ADDITIONAL_VALUES' => 'Y',
    'DEFAULT' => 'id',
];

$arTemplateParameters['LINKED_ELEMENT_TAB_SORT_ORDER2'] = [
    'PARENT' => 'DETAIL_SETTINGS',
    'NAME' => GetMessage('LINKED_ELEMENT_TAB_SORT_ORDER2'),
    'TYPE' => 'LIST',
    'VALUES' => $arAscDesc,
    'DEFAULT' => 'desc',
    'ADDITIONAL_VALUES' => 'Y',
];

$arTemplateParameters['SHOW_KIT_PARTS'] = [
    'NAME' => GetMessage('SHOW_KIT_PARTS'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N',
    'REFRESH' => 'N',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['SHOW_KIT_PARTS_PRICES'] = [
    'NAME' => GetMessage('SHOW_KIT_PARTS_PRICES'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N',
    'REFRESH' => 'N',
    'PARENT' => 'DETAIL_SETTINGS',
];

$arTemplateParameters['STORES_FILTER'] = [
    'NAME' => GetMessage('STORES_FILTER_TITLE'),
    'TYPE' => 'LIST',
    'DEFAULT' => 'TITLE',
    'VALUES' => [
        'TITLE' => GetMessage('STORES_FILTER_NAME_TITLE'),
        'SORT' => GetMessage('STORES_FILTER_SORT_TITLE'),
        'AMOUNT' => GetMessage('STORES_FILTER_AMOUNT_TITLE'),
    ],
    'PARENT' => 'STORE_SETTINGS',
];

$arTemplateParameters['STORES_FILTER_ORDER'] = [
    'NAME' => GetMessage('STORES_FILTER_ORDER_TITLE'),
    'TYPE' => 'LIST',
    'DEFAULT' => 'SORT_ASC',
    'VALUES' => [
        'SORT_ASC' => GetMessage('STORES_FILTER_ORDER_ASC_TITLE'),
        'SORT_DESC' => GetMessage('STORES_FILTER_ORDER_DESC_TITLE'),
    ],
    'PARENT' => 'STORE_SETTINGS',
];

$arTemplateParameters['ADD_PICT_PROP'] = [
    'PARENT' => 'VISUAL',
    'NAME' => GetMessage('CP_BC_TPL_ADD_PICT_PROP'),
    'TYPE' => 'LIST',
    'MULTIPLE' => 'N',
    'ADDITIONAL_VALUES' => 'N',
    'REFRESH' => 'N',
    'DEFAULT' => '-',
    'VALUES' => $arProperty_F,
];

$arTemplateParameters['SALE_STIKER'] = [
    'PAREN' => 'ADDITIONAL_SETTINGS',
    'NAME' => GetMessage('SALE_STIKER'),
    'TYPE' => 'LIST',
    'VALUES' => array_merge(['-' => ' '], $arProperty_S),
    'DEFAULT' => '',
];

$arTemplateParameters['USE_COMPARE_GROUP'] = [
    'PARENT' => 'COMPARE_SETTINGS',
    'NAME' => GetMessage('T_USE_COMPARE_GROUP'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N',
];

$arTemplateParameters['USE_FILTER_PRICE'] = [
    'NAME' => GetMessage('USE_FILTER_PRICE_TITLE'),
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N',
    'PARENT' => 'FILTER_SETTINGS',
];

if (Loader::includeModule('blog')) {
    $arTemplateParametersParts[] = [
        'MAX_IMAGE_SIZE' => [
            'PARENT' => 'DETAIL_SETTINGS',
            'NAME' => GetMessage('CP_BC_TPL_MAX_IMAGE_SIZE'),
            'TYPE' => 'STRING',
            'DEFAULT' => '0.5',
        ],
        'NO_USE_IMAGE' => [
            'PARENT' => 'REVIEW_SETTINGS',
            'NAME' => GetMessage('CP_BC_TPL_NO_USE_IMAGE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
            'REFRESH' => 'N',
        ],
        'MAX_IMAGE_COUNT' => [
            'PARENT' => 'REVIEW_SETTINGS',
            'NAME' => GetMessage('CP_BC_TPL_MAX_IMAGE_COUNT'),
            'TYPE' => 'STRING',
            'DEFAULT' => '10',
        ],
        'REVIEW_COMMENT_REQUIRED' => [
            'NAME' => GetMessage('T_REVIEW_COMMENT_REQUIRED'),
            'PARENT' => 'REVIEW_SETTINGS',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'REVIEW_FILTER_BUTTONS' => [
            'NAME' => GetMessage('T_REVIEW_FILTER_BUTTONS'),
            'TYPE' => 'LIST',
            'DEFAULT' => [],
            'PARENT' => 'REVIEW_SETTINGS',
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'SIZE' => 3,
            'VALUES' => [
                'PHOTO' => GetMessage('FILTER_BUTTONS_PHOTO'),
                'RATING' => GetMessage('FILTER_BUTTONS_RATING'),
                'TEXT' => GetMessage('FILTER_BUTTONS_TEXT'),
                'OFFER' => GetMessage('FILTER_BUTTONS_OFFER'),
            ],
        ],
        'REAL_CUSTOMER_TEXT' => [
            'PARENT' => 'REVIEW_SETTINGS',
            'DEFAULT' => '',
            'NAME' => GetMessage('T_REAL_CUSTOMER_TEXT'),
            'TYPE' => 'STRING',
        ],
        'SHOW_REVIEW' => [
            'PARENT' => 'REVIEW_SETTINGS',
            'DEFAULT' => 'Y',
            'NAME' => GetMessage('T_SHOW_REVIEW'),
            'TYPE' => 'CHECKBOX',
        ],
        'DETAIL_BLOG_EMAIL_NOTIFY' => [
            'PARENT' => 'REVIEW_SETTINGS',
            'NAME' => GetMessage('ASPRO__ECP__BLOG__EMAIL_NOTIFY'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
    ];
}

if (ModuleManager::isModuleInstalled('sale')) {
    $arTemplateParametersParts[] = [
        'USE_BIG_DATA' => [
            'PARENT' => 'BIG_DATA_SETTINGS',
            'NAME' => GetMessage('CP_BC_TPL_USE_BIG_DATA'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
            'REFRESH' => 'Y',
        ],
    ];

    if (
        !isset($arCurrentValues['USE_BIG_DATA'])
        || $arCurrentValues['USE_BIG_DATA'] == 'Y'
    ) {
        $rcmTypeList = [
            'bestsell' => GetMessage('CP_BC_TPL_RCM_BESTSELLERS'),
            'personal' => GetMessage('CP_BC_TPL_RCM_PERSONAL'),
            'similar_sell' => GetMessage('CP_BC_TPL_RCM_SOLD_WITH'),
            'similar_view' => GetMessage('CP_BC_TPL_RCM_VIEWED_WITH'),
            'similar' => GetMessage('CP_BC_TPL_RCM_SIMILAR'),
            'any_similar' => GetMessage('CP_BC_TPL_RCM_SIMILAR_ANY'),
            'any_personal' => GetMessage('CP_BC_TPL_RCM_PERSONAL_WBEST'),
            'any' => GetMessage('CP_BC_TPL_RCM_RAND'),
        ];

        $arTemplateParametersParts[] = [
            'BIG_DATA_RCM_TYPE' => [
                'PARENT' => 'BIG_DATA_SETTINGS',
                'NAME' => GetMessage('CP_BC_TPL_BIG_DATA_RCM_TYPE'),
                'TYPE' => 'LIST',
                'VALUES' => $rcmTypeList,
            ],
        ];
        unset($rcmTypeList);

        $arTemplateParametersParts[] = [
            'BIGDATA_SHOW_FROM_SECTION' => [
                'PARENT' => 'BIG_DATA_SETTINGS',
                'NAME' => GetMessage('BIGDATA_SHOW_FROM_SECTION'),
                'TYPE' => 'CHECKBOX',
                'DEFAULT' => 'N',
            ],
        ];

        $arTemplateParametersParts[] = [
            'BIGDATA_COUNT' => [
                'PARENT' => 'BIG_DATA_SETTINGS',
                'NAME' => GetMessage('BIGDATA_COUNT_TEXT'),
                'TYPE' => 'STRING',
                'DEFAULT' => 5,
            ],
        ];
    }
}

// merge parameters
foreach ($arTemplateParametersParts as $i => $part) {
    $arTemplateParameters = array_merge($arTemplateParameters, $part);
}

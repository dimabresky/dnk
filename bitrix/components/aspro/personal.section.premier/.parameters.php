<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale,
	CPremier as Solution;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
Loc::loadMessages(__FILE__);

$bSaleMode = Solution::isSaleMode();
$bSaleAccounts = $bSaleMode && CBXFeatures::IsFeatureEnabled('SaleAccounts');
$bBlog = Loader::includeModule('blog');
$bSubscribe = Loader::includeModule('subscribe');

CBitrixComponent::includeComponentClass('aspro:personal.section.premier');

// pages
$arPages = [
	'SHOW_PRIVATE_PAGE' => [
		'NAME' => Loc::getMessage('T_PS_SHOW_PRIVATE_PAGE'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
		'REFRESH' => 'Y',
		'PARENT' => 'PAGES',
	],
];

if ($bSaleMode) {
	if ($bSaleAccounts) {
		$arPages['SHOW_ACCOUNT_PAGE'] = [
			'NAME' => Loc::getMessage('T_PS_SHOW_ACCOUNT_PAGE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'REFRESH' => 'Y',
			'PARENT' => 'PAGES',
		];
	}

	$arPages = array_merge(
		$arPages,
		[
			'SHOW_PROFILE_PAGE' => [
				'NAME' => Loc::getMessage('T_PS_SHOW_PROFILE_PAGE'),
				'TYPE' => 'CHECKBOX',
				'DEFAULT' => 'Y',
				'REFRESH' => 'Y',
				'PARENT' => 'PAGES',
			],
			'SHOW_ORDER_PAGE' => [
				'NAME' => Loc::getMessage('T_PS_SHOW_ORDER_PAGE'),
				'TYPE' => 'CHECKBOX',
				'DEFAULT' => 'Y',
				'REFRESH' => 'Y',
				'PARENT' => 'PAGES',
			],
		]
	);
}

$arPages = array_merge(
	$arPages,
	[
		'SHOW_SUBSCRIBE_PAGE' => [
			'NAME' => Loc::getMessage('T_PS_SHOW_SUBSCRIBE_PAGE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'REFRESH' => 'Y',
			'PARENT' => 'PAGES',
		],
		'SHOW_FAVORITE_PAGE' => [
			'NAME' => Loc::getMessage('T_PS_SHOW_FAVORITE_PAGE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
			'REFRESH' => 'Y',
			'PARENT' => 'PAGES',
		],
		'CUSTOM_PAGES' => [
			'NAME' => Loc::getMessage('T_PS_CUSTOM_PAGES'),
			'TYPE' => 'CUSTOM',
			'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'custom_sorted_items'),
			'JS_EVENT' => 'initCustomSortedItems',
			'JS_DATA' => str_replace('\'', "\"", CUtil::PhpToJSObject(
				[
					'checkable' => true,
					'sortable' => true,
					'header' => [
						'code' => 'name',
						'title' => 	Loc::getMessage('T_PS_NAME_OF_CUSTOM_PAGE'),
					],
					'props' => [
						[
							'code' => 'path',
							'title' => 	Loc::getMessage('T_PS_PATH_OF_CUSTOM_PAGE'),
							'type' => 'string',
						],
						[
							'code' => 'page',
							'title' => 	Loc::getMessage('T_PS_PAGE_OF_CUSTOM_PAGE'),
							'type' => 'string',
						],
					],
				]
			)),
			'DEFAULT' => '',
			'PARENT' => 'PAGES',
		],
	]
);

// SEF URLS
$arSef = [];
if ($arCurrentValues['SHOW_PRIVATE_PAGE'] !== 'N') {
	$arSef['private'] = [
		'NAME' => Loc::getMessage('T_PS_SEF_PRIVATE'),
		'DEFAULT' => 'private/',
		'VARIABLES' => [],
	];
}

if ($bSaleMode) {
	if ($bSaleAccounts) {
		if ($arCurrentValues['SHOW_ACCOUNT_PAGE'] !== 'N') {
			$arSef['account'] = [
				'NAME' => Loc::getMessage('T_PS_SEF_ACCOUNT'),
				'DEFAULT' => 'account/',
				'VARIABLES' => [],
			];
		}
	}

	if ($arCurrentValues['SHOW_PROFILE_PAGE'] !== 'N') {
		$arSef = array_merge(
			$arSef,
			[
				'profiles' => [
					'NAME' => Loc::getMessage('T_PS_SEF_PROFILE_LIST'),
					'DEFAULT' => 'profiles/',
					'VARIABLES' => [],
				],
				'profile' => [
					'NAME' => Loc::getMessage('T_PS_SEF_PROFILE_DETAIL'),
					'DEFAULT' => 'profiles/#ID#',
					'VARIABLES' => ['ID'],
				],
			]
		);
	}

	if ($arCurrentValues['SHOW_ORDER_PAGE'] !== 'N') {
		$arSef = array_merge(
			$arSef,
			[
				'orders' => [
					'NAME' => Loc::getMessage('T_PS_SEF_ORDERS_LIST'),
					'DEFAULT' => 'orders/',
					'VARIABLES' => [],
				],
				'order_detail' => [
					'NAME' => Loc::getMessage('T_PS_SEF_ORDER_DETAIL'),
					'DEFAULT' => 'orders/#ID#',
					'VARIABLES' => ['ID'],
				],
				'order_cancel' => [
					'NAME' => Loc::getMessage('T_PS_SEF_ORDER_CANCEL'),
					'DEFAULT' => 'orders/cancel/#ID#',
					'VARIABLES' => ['ID'],
				],
				'payment' => [
					'NAME' => Loc::getMessage('T_PS_SEF_ORDER_PAYMENT'),
					'DEFAULT' => 'payment/',
					'VARIABLES' => ['ID'],
				],
			]
		);
	}
}

if ($arCurrentValues['SHOW_SUBSCRIBE_PAGE'] !== 'N') {
	$arSef['subscribe'] = [
		'NAME' => Loc::getMessage('T_PS_SEF_SUBSCRIBE'),
		'DEFAULT' => 'subscribe/',
		'VARIABLES' => [],
	];
	$arSef['unsubscribe'] = [
		'NAME' => Loc::getMessage('T_PS_SEF_UNSUBSCRIBE'),
		'DEFAULT' => 'unsubscribe.php',
		'VARIABLES' => [],
	];
}

if ($arCurrentValues['SHOW_FAVORITE_PAGE'] !== 'N') {
	$arSef['favorite'] = [
		'NAME' => Loc::getMessage('T_PS_SEF_FAVORITE'),
		'DEFAULT' => 'favorite/',
		'VARIABLES' => [],
	];
}

// MAIN BLOCKS
$arMainBlocks = [];
$arAvailableMainBlocksOrder = [];
$arCurrentValues['MAIN_BLOCKS_ORDER'] = $arCurrentValues['MAIN_BLOCKS_ORDER'] ?? 'banners,private,account,links,orders,votes,recoms';
$arCurrentMainBlocksOrder = $arCurrentValues['MAIN_BLOCKS_ORDER'] && strlen($arCurrentValues['MAIN_BLOCKS_ORDER']) ? explode(',', $arCurrentValues['MAIN_BLOCKS_ORDER']) : [];

$arAvailableMainBlocksOrder['banners'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_BANNERS');
$arAvailableMainBlocksOrder['private'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_PRIVATE');

if ($bSaleMode) {
	if ($bSaleAccounts) {
		$arAvailableMainBlocksOrder['account'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_ACCOUNT');
	}
}

$arAvailableMainBlocksOrder['links'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_LINKS');

if ($bSaleMode) {
	if ($arCurrentValues['SHOW_ORDER_PAGE'] !== 'N') {
		$arAvailableMainBlocksOrder['orders'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_ORDERS');
	}
}

if ($bSaleMode) {
	if ($bBlog) {
		$arAvailableMainBlocksOrder['votes'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_VOTES');
	}

	$arAvailableMainBlocksOrder['recoms'] = Loc::getMessage('T_PS_MAIN_BLOCKS_SHOW_RECOMS');
}

// ADD CUSTOM MAIN BLOCKS
$arCurrentValues['CUSTOM_MAIN_BLOCKS'] = $arCurrentValues['CUSTOM_MAIN_BLOCKS'] ?? '';
try {
	$arCustomMainBlocks = \Bitrix\Main\Web\Json::decode(
		isset($_REQUEST['component_params_manager'])
			? $arCurrentValues['CUSTOM_MAIN_BLOCKS']
			: iconv(SITE_CHARSET, 'utf-8', $arCurrentValues['CUSTOM_MAIN_BLOCKS'])
	);
}
catch (Exception $e) {
	$arCustomMainBlocks = [];
}

if ($arCustomMainBlocks) {
	foreach ($arCustomMainBlocks as $arCustomMainBlock) {
		$id = trim($arCustomMainBlock['id'] ?? '');
		$name = trim($arCustomMainBlock['name'] ?? '');
		$page = trim($arCustomMainBlock['page'] ?? '');

		if (
			strlen($id) &&
			strlen($name) &&
			strlen($page)
		) {
			$arAvailableMainBlocksOrder['custom_'.$id] = $name;
		}
	}
}

$arMainBlocks['MAIN_BLOCKS_ORDER'] = [
	'NAME' => Loc::getMessage('T_PS_MAIN_BLOCKS_ORDER'),
	'TYPE' => 'CUSTOM',
	'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'available_sorted_items'),
	'JS_EVENT' => 'initAvailableSortedItems',
	'JS_DATA' => \Bitrix\Main\Web\Json::encode([
		'available' => $arAvailableMainBlocksOrder,
		'refresh' => ['banners', 'account', 'links', 'votes', 'recoms'],
	]),
	'DEFAULT' => 'banners,private,account,links,orders,votes,recoms',
	'REFRESH' => 'Y',
	'PARENT' => 'MAIN_PAGE',
];

$arMainBlocks['CUSTOM_MAIN_BLOCKS'] = [
	'NAME' => Loc::getMessage('T_PS_CUSTOM_MAIN_BLOCKS'),
	'TYPE' => 'CUSTOM',
	'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'custom_sorted_items'),
	'JS_EVENT' => 'initCustomSortedItems',
	'JS_DATA' => str_replace('\'', "\"", CUtil::PhpToJSObject(
		[
			'checkable' => false,
			'sortable' => false,
			'header' => [
				'code' => 'name',
				'title' => 	Loc::getMessage('T_PS_NAME_OF_CUSTOM_MAIN_BLOCK'),
			],
			'props' => [
				[
					'code' => 'id',
					'type' => 'hidden',
					'rand' => true,
				],
				[
					'code' => 'page',
					'title' => 	Loc::getMessage('T_PS_PAGE_OF_CUSTOM_MAIN_BLOCK'),
					'type' => 'string',
				],
			],
		]
	)),
	'DEFAULT' => '',
	'REFRESH' => 'Y',
	'PARENT' => 'MAIN_PAGE',
];

// MAIN LINKS
$arMainLinks = [];
if (!in_array('-links', $arCurrentMainBlocksOrder)) {
	$arCurrentValues['MAIN_LINKS_ORDER'] = $arCurrentValues['MAIN_LINKS_ORDER'] ?? 'favorites,orders,subscribes,profiles,help';
	$arCurrentMainLinksOrder = $arCurrentValues['MAIN_LINKS_ORDER'] && strlen($arCurrentValues['MAIN_LINKS_ORDER']) ? explode(',', $arCurrentValues['MAIN_LINKS_ORDER']) : [];
	
	$arAvailableMainLinksOrder = [];
	if ($bSaleMode) {
		if ($arCurrentValues['SHOW_ORDER_PAGE'] !== 'N') {
			$arAvailableMainLinksOrder['orders'] = Loc::getMessage('T_PS_MAIN_LINKS_SHOW_ORDERS_ITEM');
		}
		
		if ($arCurrentValues['SHOW_PROFILE_PAGE'] !== 'N') {
			$arAvailableMainLinksOrder['profiles'] = Loc::getMessage('T_PS_MAIN_LINKS_SHOW_PROFILES_ITEM');
		}
	}

	if ($arCurrentValues['SHOW_SUBSCRIBE_PAGE'] !== 'N') {
		$arAvailableMainLinksOrder['subscribes'] = Loc::getMessage('T_PS_MAIN_LINKS_SHOW_SUBSCRIBE_ITEM');
	}

	if ($arCurrentValues['SHOW_FAVORITE_PAGE'] !== 'N') {
		$arAvailableMainLinksOrder['favorites'] = Loc::getMessage('T_PS_MAIN_LINKS_SHOW_FAVORITE_ITEM');
	}

	$arAvailableMainLinksOrder['help'] = Loc::getMessage('T_PS_MAIN_LINKS_SHOW_HELP_ITEM');

	// ADD CUSTOM MAIN LINKS
	$arCurrentValues['CUSTOM_MAIN_LINKS'] = $arCurrentValues['CUSTOM_MAIN_LINKS'] ?? '';
	try {
		$arCustomMainLinks = \Bitrix\Main\Web\Json::decode(
			isset($_REQUEST['component_params_manager'])
				? $arCurrentValues['CUSTOM_MAIN_LINKS']
				: iconv(SITE_CHARSET, 'utf-8', $arCurrentValues['CUSTOM_MAIN_LINKS'])
		);
	}
	catch (Exception $e) {
		$arCustomMainLinks = [];
	}

	if ($arCustomMainLinks) {
		foreach ($arCustomMainLinks as $arCustomMainLink) {
			$id = trim($arCustomMainLink['id'] ?? '');
			$name = trim($arCustomMainLink['name'] ?? '');
			$url = trim($arCustomMainLink['url'] ?? '');

			if (
				strlen($id) &&
				strlen($name) &&
				strlen($url)
			) {
				$arAvailableMainLinksOrder['custom_'.$id] = $name;
			}
		}
	}

	$arMainLinks['MAIN_LINKS_ORDER'] = [
		'NAME' => Loc::getMessage('T_PS_MAIN_LINKS_ORDER'),
		'TYPE' => 'CUSTOM',
		'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'available_sorted_items'),
		'JS_EVENT' => 'initAvailableSortedItems',
		'JS_DATA' => \Bitrix\Main\Web\Json::encode([
			'available' => $arAvailableMainLinksOrder,
			'refresh' => ['help'],
		]),
		'DEFAULT' => 'favorites,orders,subscribes,profiles,help',
		'REFRESH' => 'Y',
		'PARENT' => 'MAIN_PAGE',
	];

	$arMainLinks['CUSTOM_MAIN_LINKS'] = [
		'NAME' => Loc::getMessage('T_PS_CUSTOM_MAIN_LINKS'),
		'TYPE' => 'CUSTOM',
		'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'custom_sorted_items'),
		'JS_EVENT' => 'initCustomSortedItems',
		'JS_DATA' => str_replace('\'', "\"", CUtil::PhpToJSObject(
			[
				'checkable' => false,
				'sortable' => false,
				'header' => [
					'code' => 'name',
					'title' => 	Loc::getMessage('T_PS_NAME_OF_CUSTOM_MAIN_LINK'),
				],
				'props' => [
					[
						'code' => 'id',
						'type' => 'hidden',
						'rand' => true,
					],
					[
						'code' => 'dsc',
						'title' => 	Loc::getMessage('T_PS_DSC_OF_CUSTOM_MAIN_LINK'),
						'type' => 'string',
					],
					[
						'code' => 'url',
						'title' => 	Loc::getMessage('T_PS_URL_OF_CUSTOM_MAIN_LINK'),
						'type' => 'string',
					],
					[
						'code' => 'icon',
						'title' => 	Loc::getMessage('T_PS_ICON_OF_CUSTOM_MAIN_LINK'),
						'type' => 'string',
					],
				],
			]
		)),
		'DEFAULT' => '',
		'REFRESH' => 'Y',
		'PARENT' => 'MAIN_PAGE',
	];
}

$arParameters = array_merge(
	$arPages,
	[
		'SEF_MODE' => $arSef,
	],
	[
		'SET_TITLE' => [],
		'CACHE_TIME'  =>  ['DEFAULT' => 3600],
		'CACHE_GROUPS' => [
			'PARENT' => 'CACHE_SETTINGS',
			'NAME' => Loc::getMessage('T_PS_CACHE_GROUPS'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
		],
	],
);

if (!in_array('-banners', $arCurrentMainBlocksOrder)) {
	$arParameters['BANNERS_HIDDEN_SM'] = [
		'PARENT' => 'MAIN_PAGE',
		'NAME' => Loc::getMessage('T_PS_BANNERS_HIDDEN_SM'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
	];

	$arParameters['BANNERS_HIDDEN_XS'] = [
		'PARENT' => 'MAIN_PAGE',
		'NAME' => Loc::getMessage('T_PS_BANNERS_HIDDEN_XS'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
	];
}

$arParameters = array_merge(
	$arParameters,
	$arMainBlocks,
	$arMainLinks,
);

if (
	!in_array('-links', $arCurrentMainBlocksOrder) &&
	!in_array('-help', $arCurrentMainLinksOrder)
) {
	$arParameters['PATH_TO_HELP'] = [
		'NAME' => Loc::getMessage('T_PS_PATH_TO_HELP'),
		'TYPE' => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT' => '/help/faq/',
		'COLS' => 25,
		'PARENT' => 'MAIN_PAGE',
	];
}

// PRIVATE PAGE
if ($arCurrentValues['SHOW_PRIVATE_PAGE'] !== 'N') {
	$arParameters['SEND_INFO_PRIVATE'] = [
		'PARENT' => 'PRIVATE',
		'NAME' => Loc::getMessage('T_PS_PRIVATE_SEND_INFO'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
	];

	$arParameters['CHECK_RIGHTS_PRIVATE'] = [
		'PARENT' => 'PRIVATE',
		'NAME' => Loc::getMessage('T_PS_PRIVATE_CHECK_RIGHTS'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
	];
}

// ACCOUNT PAGE
if (
	$bSaleAccounts &&
	(
		$arCurrentValues['SHOW_ACCOUNT_PAGE'] !== 'N' ||
		in_array('account', $arCurrentMainBlocksOrder)
	)
) {
	$arParameters['SHOW_ACCOUNT_COMPONENT'] = [
		'NAME' => Loc::getMessage('T_PS_SHOW_ACCOUNT_COMPONENT'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
		'PARENT' => 'ACCOUNT',
		'REFRESH' => 'Y'
	];

	$arParameters['SHOW_ACCOUNT_PAY_COMPONENT'] = [
		'NAME' => Loc::getMessage('T_PS_SHOW_ACCOUNT_COMPONENT_PAY'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
		'PARENT' => 'ACCOUNT',
		'REFRESH' => 'Y'
	];

	if ($arCurrentValues['SHOW_ACCOUNT_PAY_COMPONENT'] !== 'N') {
		$currencyList = [];
		$baseCurrencyCode = '';
			
		if (Loader::includeModule('currency')) {
			$currencyList = \Bitrix\Currency\CurrencyManager::getCurrencyList();
		}

		if ($_REQUEST['src_site'] && is_string($_REQUEST['src_site'])) {
			$siteId = $_REQUEST['src_site'];
		}
		else {
			$siteId = \CSite::GetDefSite();
		}

		if (Loader::includeModule('sale')) {
			$personTypeList = Bitrix\Sale\PersonType::load($siteId);
			foreach ($personTypeList as $personTypeElement) {
				$personTypes[$personTypeElement['ID']] = $personTypeElement['NAME'];
			}
			$baseCurrencyCode = Bitrix\Sale\Internals\SiteCurrencyTable::getSiteCurrency($siteId);
		}

		$arParameters['ACCOUNT_PAYMENT_SELL_CURRENCY'] = [
			'NAME' => Loc::getMessage('T_PS_SELL_CURRENCY'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'N',
			'VALUES' => $currencyList,
			'COLS' => 25,
			'ADDITIONAL_VALUES' => 'N',
			'PARENT' => 'ACCOUNT',
			'DEFAULT' => $baseCurrencyCode
		];

		$paySystemList = [Loc::getMessage('T_PS_NOT_CHOSEN')];

		$paySystemManagerResult = Bitrix\Sale\PaySystem\Manager::getList(['select' => ['ID','NAME']]);

		while ($paySystem = $paySystemManagerResult->fetch()) {
			if (!empty($paySystem['NAME'])) {
				$paySystemList[$paySystem['ID']] = $paySystem['NAME'].' ['.$paySystem['ID'].']';
			}
		}

		if (isset($personTypes)) {
			$arParameters['ACCOUNT_PAYMENT_PERSON_TYPE'] = [
				'NAME' => Loc::getMessage('T_PS_SELL_USER_TYPES'),
				'TYPE' => 'LIST',
				'MULTIPLE' => 'N',
				'VALUES' => $personTypes,
				'DEFAULT' => '1',
				'SIZE' => count($personTypes),
				'COLS' => 25,
				'ADDITIONAL_VALUES' => 'N',
				'PARENT' => 'ACCOUNT',
			];
		}

		if (isset($paySystemList)) {
			$arParameters['ACCOUNT_PAYMENT_ELIMINATED_PAY_SYSTEMS'] = [
				'NAME' => Loc::getMessage('T_PS_ELIMINATED_PAY_SYSTEMS'),
				'TYPE' => 'LIST',
				'MULTIPLE' => 'Y',
				'DEFAULT' => '0',
				'VALUES' => $paySystemList,
				'SIZE' => 6,
				'COLS' => 25,
				'ADDITIONAL_VALUES' => 'N',
				'PARENT' => 'ACCOUNT',
			];
		}

		$arParameters['ACCOUNT_PAYMENT_SELL_SHOW_FIXED_VALUES'] = [
			'NAME' => Loc::getMessage('T_PS_SELL_SHOW_FIXED_VALUES'),
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'ADDITIONAL_VALUES' => 'N',
			'REFRESH' => 'Y',
			'PARENT' => 'ACCOUNT',
		];

		if ($arCurrentValues['SELL_SHOW_FIXED_VALUES'] != 'N') {
			$arParameters['ACCOUNT_PAYMENT_SELL_TOTAL'] = [
				'NAME' => Loc::getMessage('T_PS_SELL_AMOUNT'),
				'TYPE' => 'CUSTOM',
				'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'custom_sorted_items'),
				'JS_EVENT' => 'initCustomSortedItems',
				'JS_DATA' => str_replace('\'', "\"", CUtil::PhpToJSObject(
					[
						'checkable' => true,
						'sortable' => true,
						'header' => [
							'code' => 'value',
							'title' => 	Loc::getMessage('T_PS_SELL_AMOUNT_VALUE'),
						],
						'props' => [
						],
					]
				)),
				'DEFAULT' => '',
				'PARENT' => 'ACCOUNT',
			];
		}

		$arParameters['ACCOUNT_PAYMENT_SELL_USER_INPUT'] = [
			'NAME' => Loc::getMessage('T_PS_ACCEPT_USER_AMOUNT'),
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'ADDITIONAL_VALUES' => 'N',
			'PARENT' => 'ACCOUNT',
		];
	}
}

if ($bSaleMode) {
	$statusList = [];
	$listStatusNames = \Bitrix\Sale\OrderStatus::getAllStatusesNames(LANGUAGE_ID);
	foreach ($listStatusNames as $key => $statusName) {
		$statusList[$key] = $statusName.' ['.$key.']';
	}

	$arDeliveryProps = $arPers2Prop = $arPers2ProfPropList = $arPersons = [];
	$dbPerson = \CSalePersonType::GetList(['SORT' => 'ASC', 'NAME' => 'ASC']);
	while ($arPerson = $dbPerson->GetNext()) {
		$arPerson['NAME'] = trim($arPerson['NAME']);
		$arPersons[$arPerson['ID']] = $arPerson;

		$arDeliveryProps[$arPerson['ID']] = ['' => Loc::getMessage('T_PS_NOT')];

		$dbProp = CSaleOrderProps::GetList(
			[
				'SORT' => 'ASC',
				'NAME' => 'ASC',
			],
			[
				'PERSON_TYPE_ID' => $arPerson['ID'],
				'ACTIVE' => 'Y',
			]
		);
		while ($arProp = $dbProp->GetNext()) {
			$propTitle = $arProp['NAME'].' ['.(strlen($arProp['CODE']) ? $arProp['CODE'] : $arProp['ID']).']';

			if ($arProp['UTIL'] !== 'Y') {
				$arPers2Prop[$arPerson['ID']][$arProp['ID']] = $propTitle;
	
				if ($arProp['USER_PROPS'] === 'Y') {
					if ($arProp['TYPE'] !== 'FILE') {
						$arPers2ProfPropList[$arPerson['ID']][$arProp['ID']] = $propTitle;
					}
				}
			}
			else {
				if (
					(
						$arProp['TYPE'] === 'TEXT' ||
						$arProp['TYPE'] === 'TEXTAREA'
					)
				) {
					if ($arProp['USER_PROPS'] == 'N') {
						$arDeliveryProps[$arPerson['ID']][$arProp['ID']] = $propTitle;
					}
				}
			}
		}
	}
}

// PROFILES PAGE
if (
	$bSaleMode &&
	$arCurrentValues['SHOW_PROFILE_PAGE'] !== 'N'
) {
	$arParameters['USE_AJAX_LOCATIONS_PROFILE'] = [
		'NAME' => Loc::getMessage('T_PS_USE_AJAX_LOCATIONS'),
		'TYPE' => 'CHECKBOX',
		'MULTIPLE' => 'N',
		'DEFAULT' => 'N',
		'PARENT' => 'PROFILE',
	];

	if ($arPers2ProfPropList) {
		foreach (array_keys($arPers2ProfPropList) as $personTypeId) {
			$arParameters['PROP_'.$personTypeId.'_PROFILE_LIST'] = [
				'NAME' => Loc::getMessage('T_PS_PROPS_PROFILE_LIST')." \"".$arPersons[$personTypeId]['NAME']."\" (".$arPersons[$personTypeId]["LID"].")",
				'TYPE' => 'LIST',
				'MULTIPLE' => 'Y',
				'VALUES' => $arPers2ProfPropList[$personTypeId],
				'DEFAULT' => [],
				'COLS' => 25,
				'PARENT' => 'PROFILE',
				'SIZE' => 5,
			];

		}
	}
}

// ORDERS PAGE
if (
	$bSaleMode &&
	$arCurrentValues['SHOW_ORDER_PAGE'] !== 'N'
) {
	$orderSortList = [
		'STATUS' => Loc::getMessage('T_PS_ORDER_LIST_SORT_STATUS'),
		'ID' => Loc::getMessage('T_PS_ORDER_LIST_SORT_ID'),
		'ACCOUNT_NUMBER'=> Loc::getMessage('T_PS_ORDER_LIST_SORT_ACCOUNT_NUMBER'),
		'DATE_INSERT'=> Loc::getMessage('T_PS_ORDER_LIST_SORT_DATE_CREATE'),
		'PRICE'=> Loc::getMessage('T_PS_ORDER_LIST_SORT_PRICE')
	];

	$arParameters['ORDER_DEFAULT_SORT'] = [
		'NAME' => Loc::getMessage('T_PS_ORDER_LIST_DEFAULT_SORT'),
		'TYPE' => 'LIST',
		'VALUES' => $orderSortList,
		'MULTIPLE' => 'N',
		'DEFAULT' => 'DATE_INSERT',
		'PARENT' => 'ORDER',
	];

	$arParameters['ORDERS_PER_MAIN_PAGE'] = [
		'NAME' => Loc::getMessage('T_PS_ORDERS_PER_MAIN_PAGE'),
		'TYPE' => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT' => '10',
		'PARENT' => 'ORDER',
	];

	$arParameters['ORDERS_PER_PAGE'] = [
		'NAME' => Loc::getMessage('T_PS_ORDERS_PER_PAGE'),
		'TYPE' => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT' => '20',
		'PARENT' => 'ORDER',
	];

	if ($arDeliveryProps) {
		foreach (array_keys($arDeliveryProps) as $personTypeId) {
			$arParameters['DELIVERY_INFO_PROP_'.$personTypeId] = [
				'NAME' => Loc::getMessage('T_PS_DELIVERY_INFO_PROP')." \"".$arPersons[$personTypeId]['NAME']."\" (".$arPersons[$personTypeId]["LID"].")",
				'TYPE' => 'LIST',
				'VALUES' => $arDeliveryProps[$personTypeId],
				'DEFAULT' => '',
				'COLS' => 25,
				'PARENT' => 'ORDER',
			];
		}
	}

	$arParameters['ORDER_HIDE_STATUSES'] = [
		'NAME' => Loc::getMessage('T_PS_ORDER_HIDE_STATUSES'),
		'TYPE' => 'LIST',
		'VALUES' => $statusList,
		'MULTIPLE' => 'Y',
		'DEFAULT' => [],
		'PARENT' => 'ORDER',
		'SIZE' => 5,
	];

	$arParameters['ORDER_CHANGE_STATUS_COLOR'] = [
		'NAME' => Loc::getMessage('T_PS_ORDER_CHANGE_STATUS_COLOR'),
		'TYPE' => 'LIST',
		'VALUES' => array_merge(
			[
				'' => Loc::getMessage('T_PS_NOT'),
			],
			$statusList,
		),
		'MULTIPLE' => 'N',
		'DEFAULT' => '',
		'PARENT' => 'ORDER',
	];

	$arParameters['ORDER_HISTORIC_STATUSES'] = [
		'NAME' => Loc::getMessage('T_PS_HISTORIC_STATUSES'),
		'TYPE' => 'LIST',
		'VALUES' => $statusList,
		'MULTIPLE' => 'Y',
		'DEFAULT' => ['F'],
		'PARENT' => 'ORDER',
		'SIZE' => 5,
	];

	$arParameters['ORDER_RESTRICT_CHANGE_PAYSYSTEM'] = [
		'NAME' => Loc::getMessage('T_PS_RESTRICT_CHANGE_PAYSYSTEM'),
		'TYPE' => 'LIST',
		'VALUES' => $statusList,
		'MULTIPLE' => 'Y',
		'DEFAULT' => [],
		'PARENT' => 'ORDER',
		'SIZE' => 5,
	];

	if ($arPers2Prop) {
		foreach (array_keys($arPers2Prop) as $personTypeId) {
			$arParameters['PROP_'.$personTypeId] = [
				'NAME' => Loc::getMessage('T_PS_PROPS_NOT_SHOW')." \"".$arPersons[$personTypeId]['NAME']."\" (".$arPersons[$personTypeId]["LID"].")",
				'TYPE' => 'LIST',
				'MULTIPLE' => 'Y',
				'VALUES' => $arPers2Prop[$personTypeId],
				'DEFAULT' => [],
				'COLS' => 25,
				'PARENT' => 'ORDER',
				'SIZE' => 5,
			];
		}
	}

	if (Loader::includeModule('iblock')) {
		$arParameters['CUSTOM_SELECT_PROPS'] = [
			'NAME' => Loc::getMessage('T_PS_PARAM_CUSTOM_SELECT_PROPS'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'Y',
			'VALUES' => [],
			'PARENT' => 'ORDER',
			'SIZE' => 5,
		];
	}

	$userInfo = [
		'LOGIN' => Loc::getMessage('T_PS_USER_INFO_LOGIN'),
		'EMAIL' => Loc::getMessage('T_PS_USER_INFO_EMAIL'),
		'PERSON_TYPE_NAME' => Loc::getMessage('T_PS_USER_INFO_PERSON_TYPE_NAME'),
		0 => Loc::getMessage('T_PS_SHOW_ALL'),
	];

	$arParameters['ORDER_HIDE_USER_INFO'] = [
		'NAME' => Loc::getMessage('T_PS_ORDER_HIDE_USER_INFO'),
		'TYPE' => 'LIST',
		'VALUES' => $userInfo,
		'MULTIPLE' => 'Y',
		'DEFAULT' => [0],
		'PARENT' => 'ORDER',
		'SIZE' => 5,
	];

	$arParameters['ORDER_REFRESH_PRICES'] = [
		'NAME' => Loc::getMessage('T_PS_REFRESH_PRICE_AFTER_PAYSYSTEM_CHANGE'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
		'PARENT' => 'ORDER',
	];

	$arParameters['ORDER_DISALLOW_CANCEL'] = [
		'NAME' => Loc::getMessage('T_PS_DISALLOW_CANCEL'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
		'PARENT' => 'ORDER',
		'REFRESH' => 'Y',
	];

	if ($arCurrentValues['ORDER_DISALLOW_CANCEL'] !== 'Y') {
		$arParameters['ORDER_CANCEL_REASON_REQUIRED'] = [
			'NAME' => Loc::getMessage('T_PS_ORDER_CANCEL_REASON_REQUIRED'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT' => 'ORDER',
		];

		$arParameters['ORDER_CANCEL_REASONS'] = [
			'NAME' => Loc::getMessage('T_PS_ORDER_CANCEL_REASONS'),
			'TYPE' => 'CUSTOM',
			'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'custom_sorted_items'),
			'JS_EVENT' => 'initCustomSortedItems',
			'JS_DATA' => str_replace('\'', "\"", CUtil::PhpToJSObject(
				[
					'checkable' => true,
					'sortable' => true,
					'header' => [
						'code' => 'name',
						'title' => 	Loc::getMessage('T_PS_NAME_OF_ORDER_CANCEL_REASON'),
					],
					'props' => [
					],
				]
			)),
			'DEFAULT' => '',
			'PARENT' => 'ORDER',
		];
	}

	if ($bSaleAccounts) {
		$arParameters['ALLOW_INNER'] = [
			'NAME' => Loc::getMessage('T_PS_ALLOW_INNER'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT' => 'ORDER',
		];

		$arParameters['ONLY_INNER_FULL'] = [
			'NAME' => Loc::getMessage('T_PS_ONLY_INNER_FULL'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT' => 'ORDER',
		];
	}
}

// VOTES BLOCK
if (
	$bSaleMode &&
	$bBlog &&
	!in_array('-votes', $arCurrentMainBlocksOrder)
) {
	$arParameters['VOTE_PRODUCTS_PER_MAIN_PAGE'] = [
		'NAME' => Loc::getMessage('T_PS_VOTE_PRODUCTS_PER_MAIN_PAGE'),
		'TYPE' => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT' => '10',
		'PARENT' => 'VOTES',
	];

	$arParameters['VOTE_ORDER_STATUSES'] = [
		'NAME' => Loc::getMessage('T_PS_VOTE_ORDER_STATUSES'),
		'TYPE' => 'LIST',
		'VALUES' => $statusList,
		'MULTIPLE' => 'Y',
		'DEFAULT' => ['F'],
		'PARENT' => 'VOTES',
		'SIZE' => 5,
	];

	$arParameters['VOTE_BLOG_URL'] = [
		'NAME' => Loc::getMessage('T_PS_VOTE_BLOG_URL'),
		'TYPE' => 'STRING',
		'PARENT' => 'VOTES',
		'DEFAULT' => 'catalog_comments',
	];
}

if (
	$bSaleMode &&
	!in_array('-recoms', $arCurrentMainBlocksOrder)
) {
	$arParameters['RCM_ELEMENTS_COUNT'] = [
		'NAME' => Loc::getMessage('T_PS_RCM_ELEMENTS_COUNT'),
		'TYPE' => 'STRING',
		'PARENT' => 'RCM',
		'DEFAULT' => '10',
	];
	
	$arParameters['RCM_TYPE'] = [
		'NAME' => GetMessage('T_PS_RCM_TYPE'),
		'TYPE' => 'LIST',
		'PARENT' => 'RCM',
		'MULTIPLE' => 'N',
		'VALUES' => [
			'personal' => GetMessage('T_PS_RCM_TYPE_PERSONAL'),
			'bestsell' => GetMessage('T_PS_RCM_TYPE_BESTSELLERS'),
			'any_personal' => GetMessage('T_PS_RCM_TYPE_PERSONAL_WBEST'),
			'any' => GetMessage('T_PS_RCM_TYPE_RAND')
		],
		'DEFAULT' => 'any_personal',
	];
}

$arTemplateInfo = CComponentUtil::GetTemplatesList('bitrix:system.pagenavigation');
if (!$arTemplateInfo) {
	$arParameters['NAV_TEMPLATE'] = [
		'NAME' => Loc::getMessage('T_PS_NAV_TEMPLATE'),
		'TYPE' => 'STRING',
		'DEFAULT' => '',
		'ADDITIONAL' => 'Y',
	];
}
else {
	sortByColumn($arTemplateInfo, array('TEMPLATE' => SORT_ASC, 'NAME' => SORT_ASC));

	$arTemplateList = [];
	$arSiteTemplateList = [
		'.default' => Loc::getMessage('T_IBLOCK_DESC_PAGER_TEMPLATE_SITE_DEFAULT'),
	];
	$arHiddenTemplates = [
		'js' => true,
	];

	$arTemplateID = [];
	foreach ($arTemplateInfo as &$template) {
		if ('' != $template['TEMPLATE'] && '.default' != $template['TEMPLATE']) {
			$arTemplateID[] = $template['TEMPLATE'];
		}
		if (!isset($template['TITLE'])) {
			$template['TITLE'] = $template['NAME'];
		}
	}
	unset($template);

	if (!empty($arTemplateID)) {
		$rsSiteTemplates = CSiteTemplate::GetList(
			[],
			['ID' => $arTemplateID],
			[]
		);
		while ($arSitetemplate = $rsSiteTemplates->Fetch()) {
			$arSiteTemplateList[$arSitetemplate['ID']] = $arSitetemplate['NAME'];
		}
	}

	foreach ($arTemplateInfo as &$template) {
		if (isset($arHiddenTemplates[$template['NAME']])) {
			continue;
		}

		$strDescr = $template['TITLE'].' ('.('' != $template['TEMPLATE'] && '' != $arSiteTemplateList[$template['TEMPLATE']] ? $arSiteTemplateList[$template['TEMPLATE']] : Loc::getMessage('T_IBLOCK_DESC_PAGER_TEMPLATE_SYSTEM')).')';
		$arTemplateList[$template['NAME']] = $strDescr;
	}
	unset($template);

	$arParameters['NAV_TEMPLATE'] = [
		'NAME' => Loc::getMessage('T_PS_NAV_TEMPLATE'),
		'TYPE' => 'LIST',
		'VALUES' => $arTemplateList,
		'DEFAULT' => '.default',
		'ADDITIONAL' => 'Y',
		'ADDITIONAL_VALUES' => 'Y',
	];
}

$arParameters['DATE_FORMAT'] = CIBlockParameters::GetDateFormat(Loc::getMessage('T_PS_DATE_FORMAT'), '');
$arParameters['DATE_FORMAT']['ADDITIONAL'] = 'Y';
$arParameters['DATE_FORMAT']['DEFAULT'] = 'j F Y';

// URLS
// hide next parameters, because default values are from module options
/*
$arParameters['PATH_TO_CATALOG'] = [
	'NAME' => Loc::getMessage('T_PS_PATH_TO_CATALOG'),
	'TYPE' => 'STRING',
	'MULTIPLE' => 'N',
	'DEFAULT' => '/catalog/',
	'COLS' => 25,
	'ADDITIONAL' => 'Y',
];
$arParameters['PATH_TO_BASKET'] = [
	'NAME' => Loc::getMessage('T_PS_PATH_TO_BASKET'),
	'TYPE' => 'STRING',
	'MULTIPLE' => 'N',
	'DEFAULT' => '/personal/cart/',
	'COLS' => 25,
	'ADDITIONAL' => 'Y',
];
*/

// GROUPS
$arGroups = [
	'PAGES' => [
		'NAME' => Loc::getMessage('T_PS_GROUP_PAGES'),
		'SORT' => '100',
	],
	'MAIN_PAGE' => [
		'NAME' => Loc::getMessage('T_PS_MAIN_PERSONAL'),
		'SORT' => '505',
	],
	'PRIVATE' => [
		'NAME' => Loc::getMessage('T_PS_GROUP_PRIVATE'),
		'SORT' => '510',
	],
];

if ($bSaleMode) {
	if (
		$bSaleAccounts &&
		(
			$arCurrentValues['SHOW_ACCOUNT_PAGE'] !== 'N' ||
			in_array('account', $arCurrentMainBlocksOrder)
		)
	) {
		$arGroups = array_merge(
			$arGroups,
			[
				'ACCOUNT' => [
					'NAME' => Loc::getMessage('T_PS_GROUP_ACCOUNT'),
					'SORT' => '520',
				],
			]
		);
	}

	$arGroups = array_merge(
		$arGroups,
		[
			'PROFILE' => [
				'NAME' => Loc::getMessage('T_PS_GROUP_PROFILE'),
				'SORT' => '530',
			],
			'ORDER' => [
				'NAME' => Loc::getMessage('T_PS_GROUP_ORDER'),
				'SORT' => '540',
			],
			'RCM' => [
				'NAME' => Loc::getMessage('T_PS_GROUP_RCM'),
				'SORT' => '560',
			],
		]
	);

	if ($bBlog) {
		$arGroups = array_merge(
			$arGroups,
			[
				'VOTES' => [
					'NAME' => Loc::getMessage('T_PS_GROUP_VOTES'),
					'SORT' => '550',
				],
			]
		);
	}
}

$arGroups = array_merge(
	$arGroups,
	[
		'SUBSCRIBE' => [
			'NAME' => Loc::getMessage('T_PS_GROUP_SUBSCRIBE'),
			'SORT' => '560',
		],
		'FAVORITE' => [
			'NAME' => Loc::getMessage('T_PS_GROUP_FAVORITE'),
			'SORT' => '570',
		],
	]
);

$arComponentParameters = [
	'GROUPS' => $arGroups,
	'PARAMETERS' => $arParameters,
];
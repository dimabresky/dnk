<?
use Bitrix\Main\Localization\Loc,
	CPremier as Solution;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
Loc::loadMessages(__FILE__);

$arComponentParameters['PARAMETERS'] = [];

$bSaleMode = Solution::isSaleMode();

if ($bSaleMode) {
	$statusList = [];
	$listStatusNames = \Bitrix\Sale\OrderStatus::getAllStatusesNames(LANGUAGE_ID);
	foreach ($listStatusNames as $key => $data) {
		$statusList[$key] = $data;
	}
}

$arComponentParameters = [
	'PARAMETERS' => [
		'PRODUCTS_PER_PAGE' => [
			'NAME' => Loc::getMessage('T_VP_PRODUCTS_PER_PAGE'),
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '10',
		],
	]
];

if ($bSaleMode) {
	$arComponentParameters['PARAMETERS']['ORDER_STATUSES'] = [
		'NAME' => Loc::getMessage('T_VP_ORDER_STATUSES'),
		'TYPE' => 'LIST',
		'VALUES' => $statusList,
		'MULTIPLE' => 'Y',
		'DEFAULT' => ['F'],
		'SIZE' => 5,
	];
}

$arComponentParameters['PARAMETERS'] = array_merge(
	$arComponentParameters['PARAMETERS'],
	[
		'BLOG_URL' => [
			'NAME' => Loc::getMessage('T_VP_BLOG_URL'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'catalog_comments',
		],
	],
);

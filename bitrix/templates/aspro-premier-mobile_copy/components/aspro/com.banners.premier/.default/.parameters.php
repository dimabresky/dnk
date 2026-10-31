<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\main\Localization\Loc;

/** @var array $arCurrentValues */

if (!Loader::includeModule('iblock')) {
	return;
}

$arFromTheme = [];
/* check for custom option */
if (isset($_REQUEST['src_path'])) {
	$_SESSION['src_path_component'] = $_REQUEST['src_path'];
}
if (strpos($_SESSION['src_path_component'], 'custom') === false) {
	$arFromTheme = ['FROM_THEME' => Loc::getMessage('T_FROM_THEME')];
}

$arTemplateParameters = [
	'BANNER_TYPE' => [
		'NAME' => Loc::getMessage('T_BANNER_TYPE'),
		'TYPE' => 'LIST',
		'REFRESH' => 'Y',
		'VALUES' => [
			'type_1' => Loc::getMessage('T_BANNER_TYPE_TYPE_1'),
			'type_2' => Loc::getMessage('T_BANNER_TYPE_TYPE_2'),
			'type_3' => Loc::getMessage('T_BANNER_TYPE_TYPE_3'),
		],
		'DEFAULT' => 'type_1',
	],
];

if (
	$arCurrentValues['SET_BANNER_FROM_TYPE'] == 'Y' &&
	$arCurrentValues['BANNER_TYPE'] == 'type_3'
) {
	$arIBlocks = [];
	$db_iblock = CIBlock::GetList(
		['SORT' => 'ASC'],
		[
			'SITE_ID' => $_REQUEST['site'], 
			'TYPE' => ($arCurrentValues['IBLOCK_TYPE'] != '-' ? $arCurrentValues['IBLOCK_TYPE'] : '')
		]
	);
	while ($arRes = $db_iblock->Fetch()) {
		$arIBlocks[$arRes['ID']] = $arRes['NAME'];
	}

	$arTypes = [];
	if ($arCurrentValues['TYPE_BANNERS_IBLOCK_ID']) {
		$rsTypes = CIBlockElement::GetList(
			[],
			[
				'IBLOCK_ID' => $arCurrentValues['TYPE_BANNERS_IBLOCK_ID'],
				'ACTIVE' => 'Y',
			],
			false,
			false,
			[
				'ID', 'IBLOCK_ID', 'NAME', 'CODE',
			]
		);
		while ($arr = $rsTypes->Fetch()) {
			$arTypes[$arr['CODE']] = '['.$arr['CODE'].'] '.$arr['NAME'];
		}
	}

	$arTemplateParameters = array_merge(
		$arTemplateParameters,
		[
			'CHILD_BANNERS_IBLOCK_ID' => [
				'PARENT' => 'BASE',
				'NAME' => Loc::getMessage('T_IBLOCK_CHILD_BANNERS_IBLOCK_ID'),
				'TYPE' => 'LIST',
				'VALUES' => $arIBlocks,
				'DEFAULT' => '',
				'ADDITIONAL_VALUES' => 'Y',
			],
			'BANNER_TYPE_THEME_CHILD' => [
				'PARENT' => 'BASE',
				'NAME' => Loc::getMessage('BANNER_TYPE_THEME_CHILD'),
				'TYPE' => 'LIST',
				'VALUES' => $arTypes,
				'DEFAULT' => '',
				'ADDITIONAL_VALUES' => 'Y',
			],
		]
	);
}

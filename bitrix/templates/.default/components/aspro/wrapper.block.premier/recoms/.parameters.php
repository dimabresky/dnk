<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arTemplateParameters = [
	'SHOW_TITLE' => [
		'PARENT' => 'VISUAL',
		'NAME' => Loc::getMessage('T_SHOW_TITLE'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
		'REFRESH' => 'Y',
	],
];

if ($arCurrentValues['SHOW_TITLE'] == 'Y') {
	$arTemplateParameters['TITLE'] = [
		'PARENT' => 'VISUAL',
		'NAME' => Loc::getMessage('T_TITLE'),
		'TYPE' => 'STRING',
		'DEFAULT' => Loc::getMessage('V_TITLE'),
	];
}

$arTemplateParameters['ELEMENT_COUNT'] = [
	'PARENT' => 'VISUAL',
	'NAME' => Loc::getMessage('IBLOCK_PAGE_ELEMENT_COUNT'),
	'TYPE' => 'STRING',
	'DEFAULT' => '30',
];

$arTemplateParameters['RCM_TYPE'] = [
	'NAME' => Loc::getMessage('CP_BCS_TPL_TYPE_TITLE'),
	'TYPE' => 'LIST',
	'MULTIPLE' => 'N',
	'VALUES' => [
		'personal' => Loc::getMessage('CP_BCS_TPL_PERSONAL'),
		'bestsell' => Loc::getMessage('CP_BCS_TPL_BESTSELLERS'),
		'similar_sell' => Loc::getMessage('CP_BCS_TPL_SOLD_WITH'),
		'similar_view' => Loc::getMessage('CP_BCS_TPL_VIEWED_WITH'),
		'similar' => Loc::getMessage('CP_BCS_TPL_SIMILAR'),
		'any_similar' => Loc::getMessage('CP_BCS_TPL_SIMILAR_ANY'),
		'any_personal' => Loc::getMessage('CP_BCS_TPL_PERSONAL_WBEST'),
		'any' => Loc::getMessage('CP_BCS_TPL_RAND')
	],
	'DEFAULT' => 'any_personal',
];

$arTemplateParameters['RCM_PROD_ID'] = [
	'NAME' => Loc::getMessage('CP_BCS_TPL_PRODUCT_ID_PARAM'),
	'TYPE' => 'STRING',
	'DEFAULT' => '={$_REQUEST["PRODUCT_ID"]}',
];

$arTemplateParameters['SHOW_FROM_SECTION'] = [
	'NAME' => Loc::getMessage('CP_BCS_TPL_SHOW_FROM_SECTION'),
	'TYPE' => 'CHECKBOX',
	'DEFAULT' => 'N',
];

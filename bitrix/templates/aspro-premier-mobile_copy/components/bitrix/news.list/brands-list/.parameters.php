<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Aspro\Premier\Functions\ExtComponentParameter;

ExtComponentParameter::init(__DIR__, []);

$arFromTheme = $arTmpConfig = [];
/* check for custom option */
if (isset($_REQUEST['src_path'])) {
	$_SESSION['src_path_component'] = $_REQUEST['src_path'];
}
if (strpos($_SESSION['src_path_component'], 'custom') === false) {
	$arFromTheme = ['FROM_THEME' => Loc::getMessage('ASPRO__SELECT_PARAM__FROM_THEME')];
}

ExtComponentParameter::addSelectParameter('SHOW_TITLE_IN_BLOCK', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'N',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('TITLE_POSITION', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'NORMAL' => Loc::getMessage('ASPRO__SELECT_PARAM__NORMAL'),
		'CENTERED' => Loc::getMessage('ASPRO__SELECT_PARAM__CENTERED'),
	],
	'DEFAULT' => 'N',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('FON', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'N',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('BORDERED', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'N',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('ELEMENTS_IN_ROW', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => [
		'4' => Loc::getMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 4]),
		'5' => Loc::getMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 5]),
		'6' => Loc::getMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 6]),
	],
	'DEFAULT' => '6',
	'SORT' => 999
]);

$arTemplateParameters = [
	'TITLE' => [
		'NAME' => Loc::getMessage('TITLE'),
		'TYPE' => 'STRING',
		'DEFAULT' => Loc::getMessage('TITLE_DEFAULT'),
	],
	'RIGHT_LINK' => [
		'NAME' => Loc::getMessage('ALL_URL_NAME'),
		'TYPE' => 'STRING',
		'DEFAULT' => 'company/partners/',
	],
	'SHOW_PREVIEW_TEXT' => [
		'NAME' => Loc::getMessage('T_SHOW_PREVIEW_TEXT'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
	],
	'SLIDER' => [
		'NAME' => Loc::getMessage('T_SLIDER'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
	],
	'MAXWIDTH_WRAP' => [
		'NAME' => Loc::getMessage('T_MAXWIDTH_WRAP'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
	],
];

ExtComponentParameter::appendTo($arTemplateParameters);

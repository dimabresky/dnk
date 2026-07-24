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
	'DEFAULT' => 'NORMAL',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('IMAGES', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'PICTURES' => Loc::getMessage('ASPRO__SELECT_PARAM__PICTURES'),
		'ICONS' => Loc::getMessage('ASPRO__SELECT_PARAM__ICONS'),
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

ExtComponentParameter::addSelectParameter('FON', [
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
	'VALUES' => $arFromTheme + [
		'3' => Loc::getMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 3]),
		'4' => Loc::getMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 4]),
		'5' => Loc::getMessage('ASPRO__SELECT_PARAM__ELEMENTS_IN_ROW_VALUE', ['#ELEMENTS#' => 5]),
	],
	'DEFAULT' => '4',
	'SORT' => 999
]);

$arTemplateParameters = array(
	'SHOW_DETAIL_LINK' => array(
		'NAME' => GetMessage('T_SHOW_DETAIL_LINK'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
	),
	'TITLE' => array(
		'NAME' => GetMessage('T_TITLE'),
		'TYPE' => 'STRING',
		'DEFAULT' => GetMessage('V_TITLE'),
	),
	'RIGHT_TITLE' => array(
		'NAME' => GetMessage('T_RIGHT_TITLE'),
		'TYPE' => 'STRING',
		'DEFAULT' => GetMessage('V_RIGHT_TITLE'),
	),
	'RIGHT_LINK' => array(
		'NAME' => GetMessage('T_RIGHT_LINK'),
		'TYPE' => 'STRING',
		'DEFAULT' => 'company/reviews/',
	),
	'SHOW_PREVIEW_TEXT' => array(
		'NAME' => GetMessage('T_SHOW_PREVIEW_TEXT'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'Y',
	),
);

ExtComponentParameter::appendTo($arTemplateParameters);

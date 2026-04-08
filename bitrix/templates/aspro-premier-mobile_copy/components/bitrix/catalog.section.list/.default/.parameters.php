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

ExtComponentParameter::addSelectParameter('BORDERED', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'Y',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('IMAGES', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'ICONS' => Loc::getMessage('ASPRO__SELECT_PARAM__ICONS'),
		'PICTURES' => Loc::getMessage('ASPRO__SELECT_PARAM__PICTURES'),
	],
	'DEFAULT' => 'PICTURES',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('IMAGE_ON_FON', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'Y',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('USE_CUSTOM_RESIZE', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'Y',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('SHOW_TITLE_IN_BLOCK', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'Y' => Loc::getMessage('ASPRO__SELECT_PARAM__YES'),
		'N' => Loc::getMessage('ASPRO__SELECT_PARAM__NO'),
	],
	'DEFAULT' => 'Y',
	'SORT' => 999
]);

ExtComponentParameter::addSelectParameter('TITLE_POSITION', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'VALUES' => $arFromTheme + [
		'NORMAL' => Loc::getMessage('ASPRO__SELECT_PARAM__NORMAL'),
		'CENTERED' => Loc::getMessage('ASPRO__SELECT_PARAM__CENTERED'),
	],
	'DEFAULT' => 'Y',
	'SORT' => 999
]);

ExtComponentParameter::addCheckboxParameter('SHOW_PREVIEW_TEXT', [
	'PARENT' => ExtComponentParameter::PARENT_GROUP_ADDITIONAL,
	'DEFAULT' => 'N',
	'SORT' => 999
]);

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
		'DEFAULT' => 'catalog/',
	],
	'SECTION_COUNT' => array(
		'NAME' => GetMessage('SECTION_COUNT_NAME'),
		'TYPE' => 'STRING',
		'DEFAULT' => '',
	),
];

ExtComponentParameter::appendTo($arTemplateParameters);

<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\ModuleManager,
	Bitrix\Main\Web\Json,
	Bitrix\Iblock,
	Bitrix\Main\Localization\Loc,
	Aspro\Premier\Functions\ExtComponentParameter;

if(
	!Loader::includeModule('iblock') ||
	!Loader::includeModule('aspro.premier')
){
	return;
}

ExtComponentParameter::init(__DIR__, $arCurrentValues);
ExtComponentParameter::addBaseParameters(array(
	array(
		array('SECTION' => 'SECTION', 'OPTION' => 'PAGE_CONTACTS'),
		'SECTIONS_TYPE_VIEW'
	),
));

ExtComponentParameter::appendTo($arTemplateParameters);

if (strpos($arCurrentValues['SECTIONS_TYPE_VIEW'], 'sections_1') === false) {
	$arTemplateParameters['CHOOSE_REGION_TEXT'] = array(
		'PARENT' => 'LIST_SETTINGS',
		'SORT' => 100,
		'NAME' => GetMessage('CHOOSE_REGION_TEXT'),
		'TYPE' => 'TEXT',
		'DEFAULT' => '',
	);
}
?>
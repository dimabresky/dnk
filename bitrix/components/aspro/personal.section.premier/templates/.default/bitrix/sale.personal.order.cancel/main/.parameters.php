<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

//CBitrixComponent::includeComponentClass('aspro:personal.section.premier');
$component = new CBitrixComponent;
$component->initComponent('aspro:personal.section.premier');
$componentPath = $component->getPath();

$arTemplateParameters = [
	'REASON_REQUIRED' => [
		'NAME' => Loc::getMessage('SPOC_REASON_REQUIRED'),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
	],
	'REASONS' => [
		'NAME' => Loc::getMessage('SPOC_REASONS'),
		'TYPE' => 'CUSTOM',
		'JS_FILE' => PersonalSection::getSettingsScript($componentPath, 'custom_sorted_items'),
		'JS_EVENT' => 'initCustomSortedItems',
		'JS_DATA' => str_replace('\'', "\"", CUtil::PhpToJSObject(
			[
				'checkable' => true,
				'sortable' => true,
				'header' => [
					'code' => 'name',
					'title' => 	Loc::getMessage('SPOC_NAME_OF_ORDER_CANCEL_REASON'),
				],
				'props' => [
				],
			]
		)),
		'DEFAULT' => '',
		'PARENT' => 'ORDER',
	],
];

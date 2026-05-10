<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arTemplateParameters = [
	'ELEMENT_COUNT' => [
		'PARENT' => 'VISUAL',
		'NAME' => Loc::getMessage('IBLOCK_PAGE_ELEMENT_COUNT'),
		'TYPE' => 'STRING',
		'DEFAULT' => '30',
	],
];

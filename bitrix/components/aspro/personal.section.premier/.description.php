<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('T_PS_NAME'),
	'DESCRIPTION' => GetMessage('T_PS_DESCRIPTION'),
	'ICON' => '/images/personal_section.gif',
	'CACHE_PATH' => 'Y',
	'SORT' => 1000,
	'PATH' => array(
		'ID' => 'aspro',
		'NAME' => GetMessage('T_PS_ASPRO'),
		'SORT' => 2,
	),
);

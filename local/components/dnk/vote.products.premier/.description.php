<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = array(
	'NAME' => GetMessage('T_VP_NAME'),
	'DESCRIPTION' => GetMessage('T_VP_DESCRIPTION'),
	'ICON' => '/images/vote.products.gif',
	'CACHE_PATH' => 'Y',
	'SORT' => 1010,
	'PATH' => array(
		'ID' => 'aspro',
		'NAME' => GetMessage('T_VP_ASPRO'),
		'SORT' => 100,
	),
);

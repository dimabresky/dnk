<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arTemplateParameters = array(
	"SHOW_RATING" => Array(
		"NAME" => GetMessage("SHOW_RATING"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "Y",
	),
);

$arTemplateParameters['RCM_TYPE'] = array(
	'PARENT' => 'BIG_DATA_SETTINGS',
	'NAME' => GetMessage('CP_BCS_TPL_TYPE_TITLE'),
	'TYPE' => 'LIST',
	'MULTIPLE' => 'N',
	'VALUES' => array(
		'personal' => GetMessage('CP_BCS_TPL_PERSONAL'),
		'bestsell' => GetMessage('CP_BCS_TPL_BESTSELLERS'),
		'similar_sell' => GetMessage('CP_BCS_TPL_SOLD_WITH'),
		'similar_view' => GetMessage('CP_BCS_TPL_VIEWED_WITH'),
		'similar' => GetMessage('CP_BCS_TPL_SIMILAR'),
		'any_similar' => GetMessage('CP_BCS_TPL_SIMILAR_ANY'),
		'any_personal' => GetMessage('CP_BCS_TPL_PERSONAL_WBEST'),
		'any' => GetMessage('CP_BCS_TPL_RAND')
	),
	'DEFAULT' => 'any_personal',
);
$arTemplateParameters['RCM_PROD_ID'] = array(
	'PARENT' => 'BIG_DATA_SETTINGS',
	'NAME' => GetMessage('CP_BCS_TPL_PRODUCT_ID_PARAM'),
	'TYPE' => 'STRING',
	'DEFAULT' => '={$_REQUEST["PRODUCT_ID"]}',
);
$arTemplateParameters['SHOW_FROM_SECTION'] = array(
	'PARENT' => 'BIG_DATA_SETTINGS',
	'NAME' => GetMessage('CP_BCS_TPL_SHOW_FROM_SECTION'),
	'TYPE' => 'CHECKBOX',
	'DEFAULT' => 'N',
);
?>

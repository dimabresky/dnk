<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arExtensions = ['catalog', 'notice', 'prices', 'stickers', 'input_numeric'];
if ($arParams['SHOW_RATING'] === 'Y') {
	$arExtensions[] = 'rating';
	$arExtensions[] = 'rate';
}
if ($templateData['HAS_CHARACTERISTICS']) {
    $arExtensions[] = 'hint';
}
if ($arParams['TYPE_SKU'] !== 'TYPE_2') {
	$arExtensions[] = 'select_offer_load';
}
TSolution\Extensions::init($arExtensions);

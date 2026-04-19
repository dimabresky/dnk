<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arResult = TSolution::getChilds2($arResult);

$MENU_TYPE = TSolution::getFrontParametrValue('MEGA_MENU_TYPE');
if ($MENU_TYPE == 3) {
	TSolution::replaceMenuChilds($arResult, $arParams);

	// if items do not have links, select the first available item
	$isSelected = false;
	foreach ($arResult as &$arItem) {
		foreach ($arItem['CHILD'] as $index => &$arChild) {
			if ($arChild['SELECTED']) {
				if (!$isSelected) {
					$isSelected = true;
					continue;
				}			
				$arChild['SELECTED'] = false;			
			}
		}
		unset($arChild);
	}
	unset($arItem);
}

if (
	$arParams["CATALOG_WIDE"] === "Y"
	&& is_array($arResult)
	&& count($arResult) > 0
) {
	$arResult = reset($arResult)['CHILD'];
}

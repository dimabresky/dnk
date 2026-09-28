<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if ($arResult['suggest']['enabled']) {
	// add file to import for overload js object functions & properties by export
	$suggestTemplate = $GLOBALS['APPLICATION']->oAsset->getFullAssetPath($this->__folder.'/suggest.js');
	if ($suggestTemplate) {
		$arResult['suggest']['params']['template'] = $GLOBALS['APPLICATION']->oAsset->getFullAssetPath($this->__folder.'/suggest.js');
	}
}

if ($arResult['history']['enabled']) {
	// add additional ext|js|css
	$arResult['history']['load']['ext'][] = 'aspro_chip';
	
	// add params for js object
	// $arResult['history']['params']['maxwidth_theme'] = $arParams['MAXWIDTH_THEME'] !== 'N';
	
	// add file to import for overload js object functions & properties by export
	$historyTemplate = $GLOBALS['APPLICATION']->oAsset->getFullAssetPath($this->__folder.'/history.js');
	if ($historyTemplate) {
		$arResult['history']['params']['template'] = $GLOBALS['APPLICATION']->oAsset->getFullAssetPath($this->__folder.'/history.js');
	}
}

<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if ($arResult['ID']) {
	if (empty($arParams['PATH_TO_DELETE'])){
		$arResult['URL_TO_DETELE'] = htmlspecialcharsbx($arParams['PATH_TO_LIST'].'?del_id='.$arResult['ID']).'&'.bitrix_sessid_get();
	}
	else {
		$arResult['URL_TO_DETELE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DELETE'], Array('ID' => $arResult['ID'])).'&'.bitrix_sessid_get();
	}

	$arDatePropertiesIds = [];
	foreach ($arResult['ORDER_PROPS'] as &$block) {
		if (!empty($block['PROPS'])) {
			foreach($block['PROPS'] as $i => $property) {
				if ($property['TYPE'] === 'DATE') {
					$arDatePropertiesIds[] = $property['ID'];

					$existentProperty = Bitrix\Sale\Internals\OrderPropsTable::getList([
						'filter' => [
							'ID' => $property['ID'],
						],
						'select' => [
							'ID', 'SETTINGS',
						],
					])->fetch();

					if (
						$existentProperty && 
						is_array($existentProperty) && 
						$existentProperty['SETTINGS']
					) {
						$block['PROPS'][$i]['SETTINGS'] = $existentProperty['SETTINGS'];
					}
				}
			}
		}
	}
	unset($block);
}

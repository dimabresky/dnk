<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true ) die();

$arParams = array_merge(
	array(
		'BORDER' => false,
		'ITEM_HOVER_SHADOW' => false,
		'DARK_HOVER' => false,
		'ROUNDED' => true,
		'ITEMS_OFFSET' => true,
		'IS_AJAX' => false,
	),
	$arParams
);

if($arResult['ITEMS']){
	$arAllSections = TSolution::GetSections($arResult['ITEMS'], $arParams);

	foreach($arResult['ITEMS'] as &$arItem){
		TSolution::getFieldImageData($arItem, array('PREVIEW_PICTURE'));

		$arItem['MIDDLE_PROPS'] = array();
		if($arItem['DISPLAY_PROPERTIES']){
			foreach($arItem['DISPLAY_PROPERTIES'] as $propertyCode => $arProperty){
				if(
					in_array($propertyCode, array('ADDRESS', 'PHOTOS', 'REGION_LINK', 'EMAIL', 'PHONE', 'METRO', 'MAP', 'SCHEDULE', 'PAY_TYPE')) && 
					$arProperty['VALUE']
				){
					// get values from highload
					if(
						$arProperty['USER_TYPE'] === 'directory' &&
						isset($arProperty['USER_TYPE_SETTINGS']['TABLE_NAME'])
					){
						$arProperty['FORMAT'] = array();

						$rsData = \Bitrix\Highloadblock\HighloadBlockTable::getList(
							array(
								'filter' => array(
									'=TABLE_NAME' => $arProperty['USER_TYPE_SETTINGS']['TABLE_NAME']
								)
							)
						);
						if($arData = $rsData->fetch()){
							$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arData);
							$entityDataClass = $entity->getDataClass();
							$arFilter = array(
								'filter' => array(
									'=UF_XML_ID' => $arProperty['VALUE']
								)
							);
							$rsValues = $entityDataClass::getList($arFilter);
							while($arValue = $rsValues->fetch()){
								$arProperty['FORMAT'][] = $arValue;
							}
						}
					}

					$arItem['MIDDLE_PROPS'][$propertyCode] = $arProperty;
					unset($arItem['DISPLAY_PROPERTIES'][$propertyCode]);
				}
			}
		}

		$arItem['FORMAT_PROPS'] = TSolution::PrepareItemProps($arItem['DISPLAY_PROPERTIES']);
	}
	unset($arItem);

	if(
		isset($arAllSections['ALL_SECTIONS']) &&
		$arAllSections['ALL_SECTIONS']
	){
		foreach($arAllSections['ALL_SECTIONS'] as &$arSection){
			// has child sections
			$bHasChild = (isset($arSection['CHILD_IDS']) && $arSection['CHILD_IDS']);

			foreach($arResult['ITEMS'] as $arItem){
				$SID = ($arItem['IBLOCK_SECTION_ID'] ? $arItem['IBLOCK_SECTION_ID'] : 0);

				if($bHasChild){
					if($arSection['CHILD_IDS'][$SID]){
						$arSection['ITEMS'][$arItem['ID']] = $arItem;
					}
				}
				elseif($arAllSections['ALL_SECTIONS'][$SID]){
					$arAllSections['ALL_SECTIONS'][$SID]['ITEMS'][$arItem['ID']] = $arItem;
				}
			}
		}
		unset($arSection);

		$arResult['SECTIONS'] = $arAllSections['ALL_SECTIONS'];
	}
	else{
		$arResult['SECTIONS'][0]['ITEMS'] = $arResult['ITEMS'];
	}
}

<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arResult['PERSON_TYPES'] = $arResult['ORDER_PROPS'] = [];
if (is_array($arResult['PROFILES'])) {
	foreach ($arResult['PROFILES'] as $arProfile) {
		$personTypeId = $arProfile['PERSON_TYPE']['ID'];
	
		if (!isset($arResult['PERSON_TYPES'][$personTypeId])) {
			$arResult['PERSON_TYPES'][$personTypeId] = $arProfile['PERSON_TYPE'];
		}

		$arProfile['PERSON_TYPE'] =& $arResult['PERSON_TYPES'][$personTypeId];
	}
}

\Bitrix\Main\Type\Collection::sortByColumn(
	$arResult['PERSON_TYPES'],
	[
		'SORT' => SORT_ASC,
		'ID' => SORT_ASC,
	]
);

if ($arResult['PERSON_TYPES']) {
	$orderPropertyList = [];
	foreach ($arResult['PERSON_TYPES'] as $arPersonType) {
		$arFilter = [
			'PERSON_TYPE_ID' => $arPersonType['ID'],
			'USER_PROPS' => 'Y', 
			'ACTIVE' => 'Y', 
			'UTIL' => 'N',
		];

		if (
			isset($arParams['PROP_'.$arPersonType['ID'].'_PROFILE_LIST']) &&
			$arParams['PROP_'.$arPersonType['ID'].'_PROFILE_LIST'] &&
			is_array($arParams['PROP_'.$arPersonType['ID'].'_PROFILE_LIST'])
		) {
			$arFilter['ID'] = $arParams['PROP_'.$arPersonType['ID'].'_PROFILE_LIST'];
		}

		$orderPropertiesList = \CSaleOrderProps::GetList(
			[
				'SORT' => 'ASC',
				'NAME' => 'ASC',
			],
			$arFilter,
			false,
			false,
			[
				'ID', 'PERSON_TYPE_ID', 'NAME', 'TYPE', 'REQUIED', 'DEFAULT_VALUE', 'SORT', 'USER_PROPS',
				'IS_LOCATION', 'PROPS_GROUP_ID', 'SIZE1', 'SIZE2', 'DESCRIPTION', 'IS_EMAIL', 'IS_PROFILE_NAME',
				'IS_PAYER', 'IS_LOCATION4TAX', 'CODE', 'SORT', 'MULTIPLE',
			]
		);
		while ($orderProperty = $orderPropertiesList->GetNext()) {
			if (
				$orderProperty['REQUIED'] == 'Y' ||
				$orderProperty['IS_EMAIL'] == 'Y' ||
				$orderProperty['IS_PROFILE_NAME'] == 'Y' ||
				$orderProperty['IS_LOCATION'] == 'Y' ||
				$orderProperty['IS_PAYER'] == 'Y'
			) {
				$orderProperty['REQUIED'] = 'Y';
			}

			if (in_array($orderProperty['TYPE'], ['SELECT', 'MULTISELECT', 'RADIO'])) {
				$dbVars = \CSaleOrderPropsVariant::GetList(
					($by = 'SORT'),
					($order = 'ASC'),
					[
						'ORDER_PROPS_ID' => $orderProperty['ID'],
					]
				);
				while ($vars = $dbVars->GetNext()) {
					$orderProperty['VALUES'][] = $vars;
				}
			}
			elseif ($orderProperty['TYPE'] == 'LOCATION') {
				$orderProperty['VALUES'] = [];
			}

			$orderPropertyList[$orderProperty['ID']] = $orderProperty;
		}
	}

	$arResult['ORDER_PROPS'] =& $orderPropertyList;

	$htmlConvector = \Bitrix\Main\Text\Converter::getHtmlConverter();

	// get prop values
	$locationCodes = [];
	foreach ($arResult['PROFILES'] as &$arProfile) {
		$propertiesValueList = [];

		$profileData = \Bitrix\Sale\OrderUserProperties::getProfileValues($arProfile['ID']);
		if (!empty($profileData)) {
			foreach ($profileData as $propertyId => $value) {
				if (isset($orderPropertyList[$propertyId])) {
					if ($orderPropertyList[$propertyId]['TYPE'] === 'LOCATION') {
						$locationCodes = array_merge($locationCodes, (array)$value);
					}
	
					if (is_array($value)) {
						foreach ($value as &$elementValue) {
							if (!is_array($elementValue)) {
								$elementValue = $htmlConvector->encode($elementValue);
							}
							else {
								$elementValue = htmlspecialcharsEx($elementValue);
							}
						}
					}
					else {
						$value = $htmlConvector->encode($value);
					}
	
					$propertiesValueList[$propertyId] = $value;
				}
			}
		}

		$arProfile['ORDER_PROPS_VALUES'] = $propertiesValueList;
	}
	unset($arProfile);

	if ($locationCodes) {
		$locationCodes = array_unique($locationCodes);

		$locationValues = [];
		$locationData = \Bitrix\Sale\Location\LocationTable::getList(
			[
				'filter' => [
					'=CODE' => $locationCodes,
					'=NAME.LANGUAGE_ID' => LANGUAGE_ID,
					'=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID,
				],
				'select' => [
					'ID', 
					'CODE', 
					'CITY_NAME' => 'NAME.NAME',
					'TYPE_ID', 
					'TYPE_CODE' => 'TYPE.CODE',
					'PARENTS.ID', 
					'PARENTS.NAME',
					'PARENTS.TYPE.CODE',
				]
			]
		);
		while ($location = $locationData->fetch()) {
			if (!isset($locationValues[$location['CODE']])) {
				$locationValues[$location['CODE']] = [
					'ID' => $location['ID'],
					'CODE' => $location['CODE'],
					'CITY_NAME' => $location['CITY_NAME'],
					'TYPE_ID' => $location['TYPE_ID'],
					'TYPE_CODE' => $location['TYPE_CODE'],
					'PARENTS' => [],
				];
			}

			if (
				$location['SALE_LOCATION_LOCATION_PARENTS_ID'] &&
				$location['SALE_LOCATION_LOCATION_PARENTS_NAME_NAME'] &&
				!isset($locationValues[$location['CODE']]['PARENTS'][$location['SALE_LOCATION_LOCATION_PARENTS_ID']])
			) {
				$locationValues[$location['CODE']]['PARENTS'][$location['SALE_LOCATION_LOCATION_PARENTS_ID']] = [
					'ID' => $location['SALE_LOCATION_LOCATION_PARENTS_ID'],
					'NAME' => $location['SALE_LOCATION_LOCATION_PARENTS_NAME_NAME'],
					'TYPE_CODE' => $location['SALE_LOCATION_LOCATION_PARENTS_TYPE_CODE'],
				];
			}
		}

		foreach ($orderPropertyList as &$orderProperty) {
			if ($orderProperty['TYPE'] === 'LOCATION') {
				$orderProperty['VALUES'] =& $locationValues;
			}
		}
		unset($orderProperty);

		foreach ($arResult['PROFILES'] as &$arProfile) {
			foreach($arProfile['ORDER_PROPS_VALUES'] as $propertyId => $propertyValue) {
				if ($orderPropertyList[$propertyId]['TYPE'] === 'LOCATION') {
					foreach ((array)$propertyValue as $value) {
						if (isset($orderPropertyList[$propertyId]['VALUES'][$value])) {
							$arProfile['ORDER_PROPS_VALUES'][$propertyId] = implode(', ', array_column($orderPropertyList[$propertyId]['VALUES'][$value]['PARENTS'], 'NAME'));
						}							
					}
				}
			}
		}
		unset($arProfile);
	}
}

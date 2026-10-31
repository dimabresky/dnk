<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bannerTheme = (string) ($arParams['BANNER_TYPE_THEME'] ?? '');
$missingIds = [];
$iblockId = 0;
foreach ($arResult['ITEMS'] as $arItem) {
	if ($bannerTheme !== '' && (string) ($arItem['TYPE_BANNER'] ?? '') !== $bannerTheme) {
		continue;
	}
	if (is_array($arItem['PROPERTIES'] ?? null) && array_key_exists('CS_DATETIME', $arItem['PROPERTIES'])) {
		continue;
	}
	$id = (int) ($arItem['ID'] ?? 0);
	if ($id > 0) {
		$missingIds[] = $id;
		$iblockId = (int) ($arItem['IBLOCK_ID'] ?? $iblockId);
	}
}

if ($missingIds) {
	$propsById = [];
	$filter = ['ID' => $missingIds];
	if ($iblockId > 0) {
		$filter['IBLOCK_ID'] = $iblockId;
	}
	$res = CIBlockElement::GetList([], $filter, false, false, [
		'ID',
		'PROPERTY_CS_DATETIME',
		'PROPERTY_CS_TITLE',
		'PROPERTY_CS_PICTURE',
		'PROPERTY_CS_COLOR_CODE',
	]);
	while ($row = $res->Fetch()) {
		$id = (int) $row['ID'];
		$propsById[$id] = [
			'CS_DATETIME' => ['VALUE' => $row['PROPERTY_CS_DATETIME_VALUE'] ?? ''],
			'CS_TITLE' => ['VALUE' => $row['PROPERTY_CS_TITLE_VALUE'] ?? ''],
			'CS_PICTURE' => ['VALUE' => $row['PROPERTY_CS_PICTURE_VALUE'] ?? ''],
			'CS_COLOR_CODE' => ['VALUE' => $row['PROPERTY_CS_COLOR_CODE_VALUE'] ?? ''],
		];
	}
	foreach ($arResult['ITEMS'] as &$arItem) {
		$id = (int) ($arItem['ID'] ?? 0);
		if (!isset($propsById[$id])) {
			continue;
		}
		if (!is_array($arItem['PROPERTIES'] ?? null)) {
			$arItem['PROPERTIES'] = [];
		}
		foreach ($propsById[$id] as $code => $prop) {
			if (!array_key_exists($code, $arItem['PROPERTIES'])) {
				$arItem['PROPERTIES'][$code] = $prop;
			}
		}
	}
	unset($arItem);
}

$arTmpItems = [];
foreach ($arResult['ITEMS'] as $arItem) {
	if (!isset($arTmpItems[$arItem['TYPE_BANNER']])) {
		$arTmpItems[$arItem['TYPE_BANNER']] = [
			'ITEMS' => [],
		];
	}

	$arTmpItems[$arItem['TYPE_BANNER']]['ITEMS'][] = $arItem;
}

$arResult['HAS_SLIDE_BANNERS'] = $arParams['BANNER_TYPE_THEME'] && $arTmpItems[$arParams['BANNER_TYPE_THEME']];
$arResult['HAS_CHILD_BANNERS'] = $arParams['BANNER_TYPE_THEME_CHILD'] && $arTmpItems[$arParams['BANNER_TYPE_THEME_CHILD']];

$arResult['ITEMS'] = $arTmpItems;

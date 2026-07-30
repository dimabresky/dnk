<?
use TSolution\SearchQuery;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $arTheme, $searchQuery;

$arLanding = [];

// current region
$arRegion = TSolution\Regionality::getCurrentRegion();

$oSearchQuery = new SearchQuery($searchQuery);
$arLandingsFilter = array('ACTIVE' => 'Y');
if ($arRegion) {
	// filter landings by property LINK_REGION (empty or ID of current region)
	$arLandingsFilter[] = array(
		'LOGIC' => 'OR',
		array('PROPERTY_LINK_REGION' => false),
		array('PROPERTY_LINK_REGION' => $arRegion['ID']),
	);
}

if (isset($_REQUEST['ls'])) {
	if (($landingID = intval($_REQUEST['ls'])) > 0) {
		$arLandingsFilter['ID'] = $landingID;
		if (!strlen($searchQuery)) {
			// query is empty
			$dbRes = \CIBlockElement::GetByID($landingID);
			if ($arElement = $dbRes->Fetch()) {
				$arElement = \TSolution\Cache::CIBlockElement_GetList(
					array(
						'CACHE' => array(
							'TAG' => \TSolution\Cache::GetIBlockCacheTag($arElement['IBLOCK_ID']),
							'MULTI' => 'N'
						)
					),
					array('ID' => $landingID),
					false,
					false,
					array(
						'ID',
						'IBLOCK_ID',
						'PROPERTY_QUERY',
					)
				);

				$arQuery = (array)$arElement['PROPERTY_QUERY_VALUE'];
				if (strlen($query = $arQuery ? trim(htmlspecialchars_decode($arQuery[0])) : '')) {
					if (strlen($query = SearchQuery::getSentenceExampleQuery($query))) {
						$searchQuery = $_GET['q'] = $_POST['q'] = $_REQUEST['q'] = $query;
						$_GET['spell'] = $_POST['spell'] = $_REQUEST['spell'] = 1;
						$oSearchQuery->setQuery($searchQuery);

						// include bitrix.search_page and replace $arElements by default query example
						ob_start();
						include 'include_search_page.php';
						$searchPageContent = ob_get_clean();

						$_REQUEST['q'] = '';
						unset($_GET['q'], $_GET['ls']);
					}
				}
			}
		}
	}
}

// get one landing
$arLanding = $oSearchQuery->getLandings(
	array(),
	$arLandingsFilter,
	false,
	false,
	array(
		'ID',
		'IBLOCK_ID',
		'NAME',
		'PREVIEW_TEXT',
		'DETAIL_TEXT',
		'DETAIL_PICTURE',
		'PROPERTY_IS_INDEX',
		'PROPERTY_PHOTOPOS',
		'PROPERTY_FORM_QUESTION',
		'PROPERTY_HIDE_QUERY_INPUT',
		'PROPERTY_TIZERS',
		'PROPERTY_H3_GOODS',
		'PROPERTY_SIMILAR',
		'PROPERTY_REDIRECT_URL',
		'PROPERTY_URL_CONDITION',
		'PROPERTY_QUERY_REPLACEMENT',
		'PROPERTY_CUSTOM_FILTER',
		'PROPERTY_CUSTOM_FILTER_TYPE',
		'PROPERTY_I_ELEMENT_PAGE_TITLE',
		'PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_ALT',
		'PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_TITLE',
		'PROPERTY_I_SKU_PAGE_TITLE',
		'PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_ALT',
		'PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_TITLE',
	),
	true
);

if ($arLanding) {
	$urlCondition = '';
	$arCustomFilter = array();
	$arCustomFilterTypeEnums = array();
	$bReplaceElementsByCustomFilter = false;

	// $bShowLeftBlock = ($arTheme["LEFT_BLOCK_CATALOG_SECTIONS"]["VALUE"] == "Y" && !defined("ERROR_404") && !$bHideLeftBlock);

	if (!$arLanding['PROPERTY_IS_INDEX_VALUE']) {
		$APPLICATION->AddHeadString('<meta name="robots" content="noindex,nofollow" />');
	}

	if (strlen($arLanding['PROPERTY_URL_CONDITION_VALUE'])) {
		$urlCondition = ltrim(trim($arLanding['PROPERTY_URL_CONDITION_VALUE']), '/');
		$canonicalUrl = '/'.$urlCondition;

		if (!isset($_REQUEST['ls'])) {
			LocalRedirect($canonicalUrl, true, '301 Moved permanently');
			die();
		}
	}

	if (strlen($arLanding['PROPERTY_REDIRECT_URL_VALUE']) && !strlen($urlCondition)) {
		if (!isset($_REQUEST['ls'])) {
			LocalRedirect($arLanding['PROPERTY_REDIRECT_URL_VALUE'], false, '301 Moved Permanently');
			die();
		}
	}

	if ($arLanding['PROPERTY_HIDE_QUERY_INPUT_VALUE']) {
		$searchPageContent = '';
	}

	if ($arLanding['PROPERTY_CUSTOM_FILTER_VALUE'] && $arLanding['PROPERTY_CUSTOM_FILTER_TYPE_VALUE']) {
		// decode CUSTOM_FILTER
		if (\Bitrix\Main\Loader::includeModule('catalog') && class_exists('TSolution\Condition')) {
			$arCustomFilter = array();
			$cond = new TSolution\Condition();
			$arLanding['PROPERTY_CUSTOM_FILTER_VALUE'] = (array)$arLanding['PROPERTY_CUSTOM_FILTER_VALUE'];

			foreach ($arLanding['PROPERTY_CUSTOM_FILTER_VALUE'] as $customFilter) {
				if (isset($customFilter) && is_string($customFilter)) {
					try {
						$customFilter = $cond->parseCondition(\Bitrix\Main\Web\Json::decode($customFilter), $arParams);
					}
					catch (\Exception $e){
						$customFilter = array();
					}
				}

				if ($customFilter) {
					$arCustomFilter = array_merge($arCustomFilter, $customFilter);
				}
			}
		}

		// get CUSTOM_FILTER_TYPE enums
		$arCustomFilterTypeEnums = TSolution\Cache::CIBlockPropertyEnum_GetList(
			array('CACHE' => array()),
			array(
				'IBLOCK_ID' => $arLanding['IBLOCK_ID'],
				'CODE' => 'CUSTOM_FILTER_TYPE',
			)
		);
	}

	if (
		$arLanding['DETAIL_PICTURE'] &&
		$arLanding['PROPERTY_PHOTOPOS_ENUM_ID']
	) {
		// get PHOTOPOS enums
		$arPhotoPosTypeEnums = TSolution\Cache::CIBlockPropertyEnum_GetList(
			array('CACHE' => array()),
			array(
				'IBLOCK_ID' => $arLanding['IBLOCK_ID'],
				'CODE' => 'PHOTOPOS',
			)
		);
	}

	if (
		$bReplaceElementsByCustomFilter = $arCustomFilter &&
		$arLanding['PROPERTY_CUSTOM_FILTER_TYPE_VALUE'] &&
		$arCustomFilterTypeEnums &&
		$arLanding['PROPERTY_CUSTOM_FILTER_TYPE_VALUE'] === $arCustomFilterTypeEnums[SearchQuery::CUSTOM_FILTER_TYPE_SET_XML_ID]
	) {
		// replace $arElements by CUSTOM_FILTER
		$arItemsFilter = array_merge(
			array(
				'IBLOCK_ID' => $catalogIBlockID,
				'ACTIVE' => 'Y',
			),
			array($arCustomFilter)
		);

		$arElements = TSolution\Cache::CIBLockElement_GetList(
			array(
				'CACHE' => array(
					'MULTI' => 'Y',
					'TAG' => TSolution\Cache::GetIBlockCacheTag($catalogIBlockID),
					'RESULT' => array('ID'),
				)
			),
			$arItemsFilter,
			false,
			false,
			array(
				'ID',
			)
		);
	}

	if (
		!$bReplaceElementsByCustomFilter && 
		$arLanding['PROPERTY_QUERY_REPLACEMENT_VALUE'] && 
		$arLanding['PROPERTY_QUERY_REPLACEMENT_VALUE'] !== $searchQuery
	) {
		// save original query
		$originalSearchQuery = $searchQuery;

		// replace query
		$searchQuery = $_GET['q'] = $_POST['q'] = $_REQUEST['q'] = $arLanding['PROPERTY_QUERY_REPLACEMENT_VALUE'];
		$_GET['spell'] = $_POST['spell'] = $_REQUEST['spell'] = 1;

		// include bitrix.search_page and replace $arElements by other search results
		ob_start();
		include 'include_search_page.php';
		ob_end_clean();

		// restore original query
		$searchQuery = $_GET['q'] = $_POST['q'] = $_REQUEST['q'] = $originalSearchQuery;
	}

	$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($arLanding['IBLOCK_ID'], $arLanding['ID']);
	$arLanding['IPROPERTY_VALUES'] = $ipropValues->getValues();

	if ($arLanding['PROPERTY_SIMILAR_VALUE']) {
		$arLanding['PROPERTY_SIMILAR_VALUE'] = (array)$arLanding['PROPERTY_SIMILAR_VALUE'];
		if (in_array($arLanding['ID'], $arLanding['PROPERTY_SIMILAR_VALUE'])) {
			unset($arLanding['PROPERTY_SIMILAR_VALUE'][array_search($arLanding['ID'], $arLanding['PROPERTY_SIMILAR_VALUE'])]);
		}
	}

	$arIBInheritTemplates = array(
		"ELEMENT_PAGE_TITLE" => $arLanding["PROPERTY_I_ELEMENT_PAGE_TITLE_VALUE"],
		"ELEMENT_PREVIEW_PICTURE_FILE_ALT" => $arLanding["PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_ALT_VALUE"],
		"ELEMENT_PREVIEW_PICTURE_FILE_TITLE" => $arLanding["PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_TITLE_VALUE"],
		"SKU_PAGE_TITLE" => $arLanding["PROPERTY_I_SKU_PAGE_TITLE_VALUE"],
		"SKU_PREVIEW_PICTURE_FILE_ALT" => $arLanding["PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_ALT_VALUE"],
		"SKU_PREVIEW_PICTURE_FILE_TITLE" => $arLanding["PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_TITLE_VALUE"],
	);
}

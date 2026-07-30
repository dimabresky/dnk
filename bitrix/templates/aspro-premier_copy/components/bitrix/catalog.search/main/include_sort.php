
<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

//$bRankSort
global $preFilterCatalog;
$bSearchPage = true;
$searchFilterPath = __DIR__;
$arParams['FILTER_VIEW'] = "COMPACT";
$preFilterCatalog = ['ID' => $arElements];

if($arParams["SHOW_SORT_RANK_BUTTON"] !== "N"){
	$arAvailableRank = array(
		'KEY' => "RANK",
		'SORT' => "RANK",
		'ORDER_VALUES' => array(
			'desc' => GetMessage('sort_rank_desc'),
		)
	);

	if(!$_SESSION['rank_sort'] && $arParams["SHOW_SORT_RANK_BUTTON"] !== "N"){
		$_SESSION['rank_sort'] = 'Y';
	}

	$bSortRank = false;
	if (array_key_exists('sort', $_REQUEST) && !empty($_REQUEST['sort'])) {
		if($_REQUEST['sort'] === "RANK" && $arParams["SHOW_SORT_RANK_BUTTON"] !== "N"){
			$_SESSION["rank_sort"] = "Y";
			$bSortRank = true;
		}else{
			$_SESSION["rank_sort"] = "N";
		}
	} else if($_SESSION["rank_sort"] === "Y" && $arParams["SHOW_SORT_RANK_BUTTON"] !== "N"){
		$bSortRank = true;
	}
}

$arParams["SEF_URL_TEMPLATES"]['smart_filter'] = ''; //for TSolution\Functions::checkActiveFilterPage
@include_once( $_SERVER["DOCUMENT_ROOT"].$arParams["CATALOG_TEMPLATE_PATH"].'/include_sort.php' );

<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if ($_SESSION['UF_VIEWTYPE_BRAND_'.$arParams['IBLOCK_ID']] === NULL){
	$arUserFieldViewType = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'IBLOCK_'.$arParams['IBLOCK_ID'].'_SECTION', 'FIELD_NAME' => $arParams["SECTION_DISPLAY_PROPERTY"]))->Fetch();
	$resUserFieldViewTypeEnum = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => $arUserFieldViewType['ID']));
	while($arUserFieldViewTypeEnum = $resUserFieldViewTypeEnum->GetNext()){
		$_SESSION['UF_VIEWTYPE_BRAND_'.$arParams['IBLOCK_ID']][$arUserFieldViewTypeEnum['ID']] = $arUserFieldViewTypeEnum['XML_ID'];
	}
}

$sort_default = 'SHOWS';
$order_default = 'desc';

$arAvailableSort = array(
	'IS_NEW' => array(
		'KEY' => 'IS_NEW',
		'SORT' => 'PROPERTY_IS_NEW',
		'ORDER_VALUES' => array(
			'desc' => GetMessage('sort_is_new'),
		),
	),
	'SHOWS' => array(
		'KEY' => 'SHOWS',
		'SORT' => 'SHOWS',
		'ORDER_VALUES' => array(
			'desc' => GetMessage('sort_shows_desc'),
		)
	),
);

if (Bitrix\Main\Loader::includeModule("catalog")) {
	$arAvailableSort['PRICES'] = array(
		'KEY' => 'PRICES',
		'SORT' => 'PRICE',
		'ORDER_VALUES' => array(
			'asc' => GetMessage('sort_price_asc'),
			'desc' => GetMessage('sort_price_desc'),
		),
	);
	$arSortPrices = $arParams["SORT_PRICES"];
	if ($arSortPrices == "MINIMUM_PRICE" || $arSortPrices == "MAXIMUM_PRICE") {
		$arAvailableSort["PRICES"]["SORT"] = "PROPERTY_".$arSortPrices;
	} else {
		if ($arSortPrices == "REGION_PRICE") {
			$arRegion = TSolution\Regionality::getCurrentRegion();
			if ($arRegion) {
				if (!$arRegion["PROPERTY_SORT_REGION_PRICE_VALUE"] || $arRegion["PROPERTY_SORT_REGION_PRICE_VALUE"] == "component") {
					$price = CCatalogGroup::GetList(array(), array("NAME" => $arParams["SORT_REGION_PRICE"]), false, false, array("ID", "NAME"))->GetNext();
					$arAvailableSort["PRICES"]["SORT"] = "CATALOG_PRICE_".$price["ID"];
				} else {
					$arAvailableSort["PRICES"]["SORT"] = "CATALOG_PRICE_".$arRegion["PROPERTY_SORT_REGION_PRICE_VALUE"];
				}
			} else {
				$price_name = ($arParams["SORT_REGION_PRICE"] ? $arParams["SORT_REGION_PRICE"] : "BASE");
				$price = CCatalogGroup::GetList(array(), array("NAME" => $price_name), false, false, array("ID", "NAME"))->GetNext();
				$arAvailableSort["PRICES"]["SORT"] = "CATALOG_PRICE_".$price["ID"];
			}
		} else {
			$priceName = $arParams["SORT_PRICES"] ? $arParams["SORT_PRICES"] : "BASE";
			$price = CCatalogGroup::GetList(array(), array("NAME" => $priceName), false, false, array("ID", "NAME"))->GetNext();
			$arAvailableSort["PRICES"]["SORT"] = "CATALOG_PRICE_".$price["ID"];
		}
	}
}

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$obDisplayType = TSolution\Template\DisplayTypes::getInstance();

if ($request['display'] && $obDisplayType->isValid($request['display'])) {
	setcookie('catalogViewMode', $request['display'], 0, SITE_DIR);
	$_COOKIE['catalogViewMode'] = $request['display'];
}
if ($request['sort'] && !(isset($bSortRank) && $bSortRank)) {
	setcookie('catalogSort', $request['sort'], 0, SITE_DIR);
	$_COOKIE['catalogSort'] = $request['sort'];
}
if ($request['order']) {
	setcookie('catalogOrder', $request['order'], 0, SITE_DIR);
	$_COOKIE['catalogOrder'] = $request['order'];
}
if (array_key_exists('show', $_REQUEST) && !empty($_REQUEST['show'])) {
	setcookie('catalogPageElementCount', $_REQUEST['show'], 0, SITE_DIR);
	$_COOKIE['catalogPageElementCount'] = $_REQUEST['show'];
}

if (isset($_COOKIE['catalogViewMode']) && $_COOKIE['catalogViewMode']) {
	$display = $_COOKIE['catalogViewMode'];
} else {
	if (
		$arSection[$arParams["SECTION_DISPLAY_PROPERTY"]] && 
		isset($_SESSION[$arParams["SECTION_DISPLAY_PROPERTY"].'_'.$arParams['IBLOCK_ID']][$arSection[$arParams["SECTION_DISPLAY_PROPERTY"]]])
	) {
		$display = $_SESSION[$arParams["SECTION_DISPLAY_PROPERTY"].'_'.$arParams['IBLOCK_ID']][$arSection[$arParams["SECTION_DISPLAY_PROPERTY"]]];
	} else {
		$display = $arParams['VIEW_TYPE'];
	}
}

$bForceDisplay = false;
if ($arSection["DISPLAY"] && $obDisplayType->isValid($arSection["DISPLAY"])) {
	if ($arParams['SHOW_LIST_TYPE_SECTION'] != 'N') {
		if (!isset($_COOKIE['catalogViewMode'])) {
			$display = $arSection["DISPLAY"];
		}
	} else {
		$display = $arSection["DISPLAY"];
		$bForceDisplay = true;
	}
}
$obDisplayType->setCurrent($display);

$show = !empty($_COOKIE['catalogPageElementCount']) ? $_COOKIE['catalogPageElementCount'] : $arParams['PAGE_ELEMENT_COUNT'];
$sort = !empty($_COOKIE['catalogSort']) ? $_COOKIE['catalogSort'] : $sort_default;
$order = !empty($_COOKIE['catalogOrder']) ? $_COOKIE['catalogOrder'] : $order_default;

if (isset($bSortRank) && $bSortRank) {
	$sort = 'RANK';
	$order = 'desc';
}

$sortKey = array_search($sort, array_column($arAvailableSort, 'SORT', 'KEY'));
if ($sortKey === false) {
	$sortKey = array_search($sort, array_column($arAvailableSort, 'KEY', 'KEY'));
}
if ($sortKey === false) {
	$sortKey = 'SHOWS';
	$sort = $arAvailableSort[$sortKey]['SORT'];
	$order = $order_default;
}
if (empty($arAvailableSort[$sortKey]['ORDER_VALUES'][$order])) {
	if ($sortKey === 'SHOWS' || $sortKey === 'IS_NEW') {
		$order = 'desc';
	} else {
		$order = 'asc';
	}
}

$arDelUrlParams = array('sort', 'order', 'control_ajax', 'ajax_get_filter', 'linerow', 'display', 'ajax_get', 'is_aspro_mobile');
?>
<!-- noindex -->
<div class="filter-panel sort_header view_<?=$display?> flexbox flexbox--direction-row flexbox--justify-between ">
	<div class="filter-panel__part-left">
		<div class="line-block line-block--gap line-block--gap-8 filter-panel__main-info flexbox--justify-between-to-600 line-block--flex-wrap">
			<?if (TSolution::getFrontParametrValue('SHOW_SMARTFILTER') !== 'N' && $arItems):?>
				<div class="filter-panel__filter <?=TSolution::isMobileTemplate() ? '' : ($arParams['FILTER_VIEW'] == "COMPACT" ? 'visible-767' : 'visible-991');?>">
					<div class="dark_link dropdown-select">
						<button type="button" class="btn--no-btn-appearance dropdown-select__title font_14 fill-dark-light bordered rounded-x bx-filter-title filter_title <?=($bActiveFilter && $bActiveFilter[1] != 'clear' ? 'active-filter' : '');?>">
							<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/catalog/item_icons.svg#filter', 'mr mr--12', ['WIDTH' => 13, 'HEIGHT' => 12]);?>
							<span class="dropdown-select__title-text"><?=\Bitrix\Main\Localization\Loc::getMessage("CATALOG_SMART_FILTER_TITLE");?></span>
						</button>
					</div>
				</div>
			<?endif;?>

			<?if ($arAvailableSort):?>
				<?ob_start();?>

					<div class="filter-panel__sort min-width-0">
						<div class="dropdown-select dropdown-select--with-dropdown">
							<div class="dropdown-select__title font_14 fill-dark-light bordered rounded-x">
								<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/catalog/item_icons.svg#sort', 'mr mr--12', ['WIDTH' => 12, 'HEIGHT' => 12]);?>
								<span class="dropdown-select__title-text">
									<?if ($order && $sort):?>
										<?=$arAvailableSort[$sortKey]['ORDER_VALUES'][$order];?>
									<?else:?>
										<?=\Bitrix\Main\Localization\Loc::getMessage('NOTHING_SELECTED');?>
									<?endif;?>
								</span>
								<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', 'dropdown-select__icon-down', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
							</div>
							<div class="dropdown-select__list dropdown-menu-wrapper dropdown-menu-wrapper--woffset" role="menu">
								<div class="dropdown-menu-inner outer-rounded-x">
									<?foreach ($arAvailableSort as $arSort):?>
										<?$newSort = $arSort['SORT'];?>
										<?if (is_array($arSort['ORDER_VALUES'])):?>
											<?foreach ($arSort['ORDER_VALUES'] as $newOrder => $sortTitle):?>
												<div class="dropdown-select__list-item font_15">
													<?
													$url = TSolution\Utils::getCurPageParamRawUrlEncoded(
														['sort' => $newSort, 'order' => $newOrder], 
														$arDelUrlParams
													);
													?>	
													<?if ($bCurrentLink = (
														($sort == $newSort || $sortKey == $arSort['KEY']) && $order == $newOrder)
													):?>
														<span class="dropdown-menu-item color_222 dropdown-menu-item--current">
													<?else:?>
														<a href="<?=$url;?>" class="dropdown-menu-item <?=$value?> <?=$key?> dark_link <?=($arParams['AJAX_CONTROLS'] == 'Y' ? ' js-load-link' : '');?>" data-url="<?=$url;?>" rel="nofollow prefetch">
													<?endif;?>
														<span>
															<?=$sortTitle?>
														</span>
													<?if ($bCurrentLink):?>
														<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/form_icons.svg#checkmark', 'stroke-dark-light', ['WIDTH' => 12, 'HEIGHT' => 9]);?>
														</span>
													<?else:?>
														</a>
													<?endif;?>
												</div>
											<?endforeach?>
										<?endif;?>
									<?endforeach;?>
								</div>
							</div>
						</div>
					</div>

				<?$sortHTML = ob_get_clean();?>
				<?=$sortHTML;?>
			<?endif;?>
		</div>

		<?if (TSolution::isMobileTemplate()):?>
			<?ob_start();?>
		<?endif;?>

		<?include_once(__DIR__."/{$filterDir}/filter.php");?>		

		<?if (TSolution::isMobileTemplate()):?>
			<?
			$filterHTML = ob_get_clean();
			$APPLICATION->AddViewContent('filter_content', $filterHTML);
			?>
		<?endif;?>
	</div>

	<?if (!$bForceDisplay):?>
		<?TSolution\Functions::showBlockHtml([
			'FILE' => '/catalog/display_types.php',
			'PARAMS' => [
				'DEL_URL_PARAMS' => $arDelUrlParams,
			],
		]);?>
	<?endif;?>
</div>
<?TSolution\Extensions::init(['filter_panel', 'dropdown_select']);?>
<!-- /noindex -->
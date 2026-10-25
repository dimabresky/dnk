<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Localization\Loc::loadMessages(
	$_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/components/bitrix/news/landings/lang/'.LANGUAGE_ID.'/detail.php'
);

$sort_default = $arParams['SORT_PROP_DEFAULT'] ?: 'SHOWS';
$order_default = $arParams['SORT_DIRECTION'] ?: 'desc';
$sortProps = (array)($arParams['SORT_PROP'] ?? []);
if (!$sortProps) {
	$sortProps = ['SHOWS', 'NAME', 'PRICES', 'QUANTITY'];
}

$arAvailableSort = [
	'NAME' => [
		'KEY' => 'NAME',
		'SORT' => 'NAME',
		'ORDER_VALUES' => [
			'asc' => GetMessage('sort_name_asc'),
			'desc' => GetMessage('sort_name_desc'),
		],
	],
	'SORT' => [
		'KEY' => 'SORT',
		'SORT' => 'SORT',
		'ORDER_VALUES' => [
			'asc' => GetMessage('sort_sort_asc'),
			'desc' => GetMessage('sort_sort_desc'),
		],
	],
	'SHOWS' => [
		'KEY' => 'SHOWS',
		'SORT' => 'SHOWS',
		'ORDER_VALUES' => [
			'asc' => GetMessage('sort_shows_asc'),
			'desc' => GetMessage('sort_shows_desc'),
		],
	],
];

if (Bitrix\Main\Loader::includeModule('catalog')) {
	$arAvailableSort['PRICES'] = [
		'KEY' => 'PRICES',
		'SORT' => 'PRICE',
		'ORDER_VALUES' => [
			'asc' => GetMessage('sort_price_asc'),
			'desc' => GetMessage('sort_price_desc'),
		],
	];
	if (in_array('PRICES', $sortProps, true)) {
		$arSortPrices = $arParams['SORT_PRICES'];
		if ($arSortPrices == 'MINIMUM_PRICE' || $arSortPrices == 'MAXIMUM_PRICE') {
			$arAvailableSort['PRICES']['SORT'] = 'PROPERTY_'.$arSortPrices;
		} elseif ($arSortPrices == 'REGION_PRICE') {
			$arRegion = TSolution\Regionality::getCurrentRegion();
			if ($arRegion && $arRegion['PROPERTY_SORT_REGION_PRICE_VALUE'] && $arRegion['PROPERTY_SORT_REGION_PRICE_VALUE'] != 'component') {
				$arAvailableSort['PRICES']['SORT'] = 'CATALOG_PRICE_'.$arRegion['PROPERTY_SORT_REGION_PRICE_VALUE'];
			} else {
				$priceName = ($arParams['SORT_REGION_PRICE'] ?: 'BASE');
				$price = CCatalogGroup::GetList([], ['NAME' => $priceName], false, false, ['ID', 'NAME'])->GetNext();
				if ($price) {
					$arAvailableSort['PRICES']['SORT'] = 'CATALOG_PRICE_'.$price['ID'];
				}
			}
		} else {
			$price = CCatalogGroup::GetList([], ['NAME' => $arParams['SORT_PRICES']], false, false, ['ID', 'NAME'])->GetNext();
			if ($price) {
				$arAvailableSort['PRICES']['SORT'] = 'CATALOG_PRICE_'.$price['ID'];
			}
		}
	}

	$arAvailableSort['QUANTITY'] = [
		'KEY' => 'QUANTITY',
		'SORT' => 'CATALOG_AVAILABLE',
		'ORDER_VALUES' => [
			'asc' => GetMessage('sort_quantity_asc'),
			'desc' => GetMessage('sort_quantity_desc'),
		],
	];
}

foreach ($arAvailableSort as $prop => $arProp) {
	if (!in_array($prop, $sortProps, true) && $sort_default !== $prop) {
		unset($arAvailableSort[$prop]);
	}
}

foreach ($sortProps as $prop) {
	if (isset($arAvailableSort[$prop]) || $prop === 'CUSTOM') {
		continue;
	}
	$propWithPrefix = 'PROPERTY_'.$prop;
	$dbRes = CIBlockProperty::GetList([], ['ACTIVE' => 'Y', 'IBLOCK_ID' => $arParams['IBLOCK_ID'], 'CODE' => $prop]);
	if ($arPropperty = $dbRes->Fetch()) {
		$arAvailableSort[$propWithPrefix] = [
			'KEY' => $propWithPrefix,
			'SORT' => $propWithPrefix,
			'ORDER_VALUES' => [
				'asc' => GetMessage('sort_title_property', ['#CODE#' => $arPropperty['NAME'], '#ORDER#' => GetMessage('sort_prop_asc')]),
				'desc' => GetMessage('sort_title_property', ['#CODE#' => $arPropperty['NAME'], '#ORDER#' => GetMessage('sort_prop_desc')]),
			],
		];
		if ($sort_default === $prop) {
			$sort_default = $propWithPrefix;
		}
	}
}

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$obDisplayType = TSolution\Template\DisplayTypes::getInstance();

if ($request['display'] && $obDisplayType->isValid($request['display'])) {
	setcookie('catalogViewMode', $request['display'], 0, SITE_DIR);
	$_COOKIE['catalogViewMode'] = $request['display'];
}
if ($request['sort']) {
	setcookie('catalogSort', $request['sort'], 0, SITE_DIR);
	$_COOKIE['catalogSort'] = $request['sort'];
}
if ($request['order']) {
	setcookie('catalogOrder', $request['order'], 0, SITE_DIR);
	$_COOKIE['catalogOrder'] = $request['order'];
}

$display = !empty($_COOKIE['catalogViewMode']) ? $_COOKIE['catalogViewMode'] : ($arParams['VIEW_TYPE'] ?: 'table');
$bForceDisplay = false;
$obDisplayType->setCurrent($display);

$sort = !empty($_COOKIE['catalogSort']) ? $_COOKIE['catalogSort'] : $sort_default;
$order = !empty($_COOKIE['catalogOrder']) ? $_COOKIE['catalogOrder'] : $order_default;

$sortKey = array_search($sort, array_column($arAvailableSort, 'SORT', 'KEY'));
if ($sortKey === false) {
	$sortKey = array_search($sort, array_column($arAvailableSort, 'KEY', 'KEY'));
}
if ($sortKey === false || empty($arAvailableSort[$sortKey])) {
	$sortKey = array_key_exists($sort_default, $arAvailableSort) ? $sort_default : array_key_first($arAvailableSort);
	$sort = $arAvailableSort[$sortKey]['SORT'];
	$order = $order_default;
}
if (empty($arAvailableSort[$sortKey]['ORDER_VALUES'][$order])) {
	$order = array_key_first($arAvailableSort[$sortKey]['ORDER_VALUES']);
}

$arDelUrlParams = ['sort', 'order', 'control_ajax', 'ajax_get_filter', 'linerow', 'display', 'ajax_get', 'is_aspro_mobile'];
?>
<!-- noindex -->
<div class="filter-panel sort_header view_<?=$display?> flexbox flexbox--direction-row flexbox--justify-between ">
	<div class="filter-panel__part-left">
		<div class="line-block line-block--gap line-block--gap-8 filter-panel__main-info flexbox--justify-between-to-600 line-block--flex-wrap">
			<?global $arTheme;?>
			<?if (($arTheme['SHOW_SMARTFILTER']['VALUE'] ?? 'Y') !== 'N'):?>
				<?$bActiveFilter = TSolution\Functions::checkActiveFilterPage([
					'GLOBAL_FILTER' => $filterName,
				]);?>
				<div class="filter-panel__filter <?=($arParams['FILTER_VIEW'] == 'COMPACT' ? 'visible-767' : 'visible-991');?>">
					<div class="dark_link dropdown-select">
						<button type="button" class="btn--no-btn-appearance dropdown-select__title font_14 fill-dark-light bordered rounded-x bx-filter-title filter_title <?=($bActiveFilter ? 'active-filter' : '')?>">
							<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/catalog/item_icons.svg#filter', 'mr mr--12', ['WIDTH' => 13, 'HEIGHT' => 12]);?>
							<span class="dropdown-select__title-text"><?=\Bitrix\Main\Localization\Loc::getMessage('CATALOG_SMART_FILTER_TITLE');?></span>
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
													<?if ($bCurrentLink = ($sort == $newSort && $order == $newOrder)):?>
														<span class="dropdown-menu-item color_222 dropdown-menu-item--current">
													<?else:?>
														<a href="<?=$url;?>" class="dropdown-menu-item dark_link <?=($arParams['AJAX_CONTROLS'] == 'Y' ? ' js-load-link' : '');?>" data-url="<?=$url;?>" rel="nofollow prefetch">
													<?endif;?>
														<span><?=$sortTitle?></span>
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
<?TSolution\Extensions::init(['filter_panel', 'dropdown_select', 'smart_filter']);?>
<!-- /noindex -->

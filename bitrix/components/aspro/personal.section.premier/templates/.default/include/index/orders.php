<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	CPremier as Solution;

$arFilter = [
	'USER_ID' => $arResult['USER_ID'],
	'CANCELED' => 'N',
    'LID' => SITE_ID,
];

// bug in bitrix:sale.personal.order.list
if (
	!is_array($arParams['ORDER_HISTORIC_STATUSES']) ||
	empty($arParams['ORDER_HISTORIC_STATUSES'])
) {
	$arParams['ORDER_HISTORIC_STATUSES'] = ['F'];
}

if ($arParams['ORDER_HISTORIC_STATUSES']) {
	$arFilter['!STATUS_ID'] = $arParams['ORDER_HISTORIC_STATUSES'];
}

$cnt = CSaleOrder::GetList(
	[],
	$arFilter,
	[]
);

if ($cnt > 0) {
	$arResult['PATH_TO_ORDERS'] = $arResult['PATH_TO_ORDERS'] ?? '';

	$arComponentParams = [
		"PATH_TO_ORDERS" => $arResult["PATH_TO_ORDERS"],
		"PATH_TO_DETAIL" => $arResult["PATH_TO_ORDER_DETAIL"],
		"PATH_TO_CANCEL" => $arResult["PATH_TO_ORDER_CANCEL"],
		"PATH_TO_CATALOG" => $arResult["PATH_TO_CATALOG"],
		"PATH_TO_COPY" => $arResult["PATH_TO_ORDER_COPY"],
		"PATH_TO_BASKET" => $arResult["PATH_TO_BASKET"],
		"PATH_TO_PAYMENT" => $arResult["PATH_TO_PAYMENT"],
		"SAVE_IN_SESSION" => "N",
		"ORDERS_PER_PAGE" => $arParams["ORDERS_PER_MAIN_PAGE"],
		"SET_TITLE" => "N",
		"ID" => $arResult["VARIABLES"]["ID"],
		"NAV_TEMPLATE" => $arParams["NAV_TEMPLATE"],
		"ACTIVE_DATE_FORMAT" => $arParams["DATE_FORMAT"],
		"HISTORIC_STATUSES" => $arParams["ORDER_HISTORIC_STATUSES"],
		"ALLOW_INNER" => $arParams["ALLOW_INNER"],
		"ONLY_INNER_FULL" => $arParams["ONLY_INNER_FULL"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"DEFAULT_SORT" => $arParams["ORDER_DEFAULT_SORT"],
		"RESTRICT_CHANGE_PAYSYSTEM" => $arParams["ORDER_RESTRICT_CHANGE_PAYSYSTEM"],
		"HIDE_STATUSES" => $arParams["ORDER_HIDE_STATUSES"],
		"CHANGE_STATUS_COLOR" => $arParams["ORDER_CHANGE_STATUS_COLOR"],
		"REFRESH_PRICES" => $arParams["ORDER_REFRESH_PRICES"],
		"DISALLOW_CANCEL" => $arParams["ORDER_DISALLOW_CANCEL"],
		"SHOW_DETAIL_LINK" => $arParams["SHOW_ORDER_PAGE"],
		"SHOW_ALL_LINK" => $cnt > $arParams["ORDERS_PER_MAIN_PAGE"] ? 'Y' : 'N',
	];

	foreach($arParams as $key => $value) {
		if (preg_match('/^DELIVERY_INFO_PROP_\d+/', $key)) {
			$arComponentParams[$key] = $value;
		}
	}
	?>
	<div class="main-block__title-wrapper mt mt--40">
		<h3 class="main-block__title switcher-title">
			<div class="main-block__title-inner">
				<span><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_ORDERS')?></span>
				<span class="main-block__title-count bordered rounded-x font_14"><?=$cnt?></span>

				<?if (strlen($arResult['PATH_TO_ORDERS'])):?>
					<a href="<?=$arResult['PATH_TO_ORDERS']?>" class="main-block__link stroke-dark-light-block" title="<?=Loc::getMessage('SPS_MAIN_BLOCK_ALL_ORDERS')?>">
						<span class="main-block__arrow">
							<?=Solution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-7-12', '', [
								'WIDTH' => 7,
								'HEIGHT' => 12
							]);?>
						</span>
					</a>
				<?endif;?>
			</div>
		</h3>
	</div>

	<?$APPLICATION->IncludeComponent(
		"bitrix:sale.personal.order.list",
		"slider",
		$arComponentParams,
		$component,
		array("HIDE_ICONS" => "Y")
	);?>
	<?
}

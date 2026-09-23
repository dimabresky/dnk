<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	CPremier as Solution,
	Aspro\Functions\CAsproPremier as SolutionFunctions,
	Aspro\Premier\Functions\Extensions,
	Aspro\Premier\Itemaction;

if (
	!empty($arParams['CUSTOM_MAIN_LINKS']) &&
	strpos($arParams['CUSTOM_MAIN_LINKS'], 'fa-') !== false
) {
	Extensions::init('font-awesome');
}

$svgSprite = $this->__folder.'/images/svg/main_links.svg';
$customSvgDir = $this->__folder.'/images/svg/';

$arCustomMainLinks = CUtil::JsObjectToPhp($arParams['~CUSTOM_MAIN_LINKS'] ?? '[]', true);
$arCustomMainLinks = is_array($arCustomMainLinks) ? $arCustomMainLinks : [];
foreach ($arCustomMainLinks as $i => $arCustomMainLink) {
	if (!is_array($arCustomMainLink)) {
		unset($arCustomMainLinks[$i]);
		continue;
	}

	$id = $arCustomMainLinks[$i]['id'] = trim($arCustomMainLink['id']);
	$name = $arCustomMainLinks[$i]['name'] = trim($arCustomMainLink['name']);
	$url = $arCustomMainLinks[$i]['url'] = trim($arCustomMainLink['url']);

	if (
		!strlen($id) ||
		!strlen($name) ||
		!strlen($url)
	) {
		unset($arCustomMainLinks[$i]);
		continue;
	}
}

$arCustomMainLinksIds = array_column($arCustomMainLinks, 'id');

$arParams['MAIN_LINKS_ORDER'] = isset($arParams['MAIN_LINKS_ORDER']) && strlen($arParams['MAIN_LINKS_ORDER']) ? explode(',', $arParams['MAIN_LINKS_ORDER']) : [];

$arParams['MAIN_LINKS_ORDER'] = array_filter($arParams['MAIN_LINKS_ORDER'], function($block) {
	return !preg_match('/^-/', $block);
});

$arMainLinks = [];
foreach ($arParams['MAIN_LINKS_ORDER'] as $link) {
	if ('favorites' === $link) {
		if ($arParams['SHOW_FAVORITE_PAGE'] === 'N') {
			continue;
		}

		$icon = $customSvgDir.'favorite.svg';
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$icon)) {
			$icon = Solution::showIconSvg('favorite fill-theme svg-inline-more_icon', $icon);
		}
		else {
			$icon = $svgSprite.'#favorite-32-32';
			$icon = Solution::showSpriteIconSvg($icon, 'favorite fill-theme svg-inline-more_icon', ['WIDTH' => 48, 'HEIGHT' => 48]);
		}

		$arFavoriteItems = Itemaction\Favorite::getItems();
		$cnt = count($arFavoriteItems);

		$arMainLinks[] = [
			'name' => Loc::getMessage('SPS_FAVORITE_PAGE_NAME'),
			'dsc' => $cnt ? Loc::getMessage(
				'SPS_FAVORITE_PAGE_DSC',
				[
					'#VAIL#' => SolutionFunctions::declOfNum(
						$cnt,
						[
							Loc::getMessage('SPS_PRODUCT_1'),
							Loc::getMessage('SPS_PRODUCT_2'),
							Loc::getMessage('SPS_PRODUCT_0'),
						]
					),
				]
			) : Loc::getMessage('SPS_NO_PRODUCTS'),
			'url' => $arResult['PATH_TO_FAVORITE'],
			'icon' => $icon,
		];
	}
	elseif ('orders' === $link) {
		if ($arParams['SHOW_ORDER_PAGE'] === 'N') {
			continue;
		}

		$icon = $customSvgDir.'/images/svg/orders.svg';
		$icon = file_exists($_SERVER['DOCUMENT_ROOT'].$icon) ? $icon : SITE_TEMPLATE_PATH.'/images/svg/personal.svg#orders-32-32';
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$icon)) {
			$icon = Solution::showIconSvg('orders fill-theme svg-inline-more_icon', $icon);
		}
		else {
			$icon = $svgSprite.'#orders-32-32';
			$icon = Solution::showSpriteIconSvg($icon, 'orders fill-theme svg-inline-more_icon', ['WIDTH' => 48, 'HEIGHT' => 48]);
		}

		$cnt = CSaleOrder::GetList(
			[],
			[
				'USER_ID' => $arResult['USER_ID'],
                'LID' => SITE_ID,
			],
			[],
		);

		$arMainLinks[] = [
			'name' => Loc::getMessage('SPS_ORDER_PAGE_NAME'),
			'dsc' => Loc::getMessage('SPS_ORDER_PAGE_DSC'),
			'dsc' => $cnt ? Loc::getMessage(
				'SPS_ORDER_PAGE_DSC',
				[
					'#VAIL#' => SolutionFunctions::declOfNum(
						$cnt,
						[
							Loc::getMessage('SPS_ORDER_1'),
							Loc::getMessage('SPS_ORDER_2'),
							Loc::getMessage('SPS_ORDER_0'),
						]
					),
				]
			) : Loc::getMessage('SPS_NO_ORDERS'),
			'url' => $arResult['PATH_TO_ORDERS'],
			'icon' => $icon,
		];
	}
	elseif ('subscribes' === $link) {
		if ($arParams['SHOW_SUBSCRIBE_PAGE'] === 'N') {
			continue;
		}

		$icon = $customSvgDir.'/images/svg/subscribe.svg';
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$icon)) {
			$icon = Solution::showIconSvg('subscribe fill-theme svg-inline-more_icon', $icon);
		}
		else {
			$icon = $svgSprite.'#subscribe-32-32';
			$icon = Solution::showSpriteIconSvg($icon, 'subscribe fill-theme svg-inline-more_icon', ['WIDTH' => 48, 'HEIGHT' => 48]);
		}

		$arMainLinks[] = [
			'name' => Loc::getMessage('SPS_SUBSCRIBE_PAGE_NAME'),
			'dsc' => $arResult['SALE_MODE'] ? Loc::getMessage('SPS_SUBSCRIBE_PAGE_DSC_SALE') : Loc::getMessage('SPS_SUBSCRIBE_PAGE_DSC'),
			'url' => $arResult['PATH_TO_SUBSCRIBE'],
			'icon' => $icon,
		];
	}
	elseif ('profiles' === $link) {
		if ($arParams['SHOW_PROFILE_PAGE'] === 'N') {
			continue;
		}

		$icon = $customSvgDir.'/images/svg/profile.svg';
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$icon)) {
			$icon = Solution::showIconSvg('profile fill-theme svg-inline-more_icon', $icon);

		}
		else {
			$icon = $svgSprite.'#profile-32-32';
			$icon = Solution::showSpriteIconSvg($icon, 'profile fill-theme svg-inline-more_icon', ['WIDTH' => 48, 'HEIGHT' => 48]);
		}

		$cnt = CSaleOrderUserProps::GetList(
			[],
			[
				'USER_ID' => $arResult['USER_ID']
			],
			[],
		);

		$arMainLinks[] = [
			'name' => Loc::getMessage('SPS_PROFILE_PAGE_NAME'),
			'dsc' => $cnt ? Loc::getMessage(
				'SPS_PROFILE_PAGE_DSC',
				[
					'#VAIL#' => SolutionFunctions::declOfNum(
						$cnt,
						[
							Loc::getMessage('SPS_PROFILE_1'),
							Loc::getMessage('SPS_PROFILE_2'),
							Loc::getMessage('SPS_PROFILE_0'),
						]
					),
				]
			) : Loc::getMessage('SPS_NO_PROFILES'),
			'url' => $arResult['PATH_TO_PROFILES'],
			'icon' => $icon,
		];
	}
	elseif ('help' === $link) {
		$icon = $customSvgDir.'/images/svg/help.svg';
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$icon)) {
			$icon = Solution::showIconSvg('help fill-theme svg-inline-more_icon', $icon);
		}
		else {
			$icon = $svgSprite.'#help-32-32';
			$icon = Solution::showSpriteIconSvg($icon, 'help fill-theme svg-inline-more_icon', ['WIDTH' => 48, 'HEIGHT' => 48]);
		}

		$arMainLinks[] = [
			'name' => Loc::getMessage('SPS_HELP_PAGE_NAME'),
			'dsc' => Loc::getMessage('SPS_HELP_PAGE_DSC'),
			'url' => $arResult['PATH_TO_HELP'],
			'icon' => $icon,
		];
	}
	else {
		if (strpos($link, 'custom_') !== false) {
			$id = str_replace('custom_', '', $link);

			if (in_array($id, $arCustomMainLinksIds)) {
				foreach ($arCustomMainLinks as $i => $arCustomMainLink) {
					if ($arCustomMainLink['id'] === $id) {
						$icon = $customSvgDir.htmlspecialcharsbx($arCustomMainLink['icon']).'.svg';
						if (file_exists($_SERVER['DOCUMENT_ROOT'].$icon)) {
							$icon = Solution::showIconSvg('custom fill-theme svg-inline-more_icon', $icon);
						}
						else {
							$icon = '<i class="svg-inline-more_icon color-theme fa '.htmlspecialcharsbx($arCustomMainLink['icon']).'"></i>';
						}

						$arMainLinks[] = array(
							'name' => $arCustomMainLink['name'],
							'dsc' => $arCustomMainLink['dsc'],
							'url' => $arCustomMainLink['url'],
							'icon' => $icon,
						);

						break;
					}
				}
			}
		}
	}
}
?>
<?if ($arMainLinks): ?>
	<div class="grid-list grid-list--items grid-list--no-gap gap gap--12 grid-list--items-4-from-768 grid-list--items-3-from-601 mobile-scrolled mobile-scrolled--items-2 mobile-offset">
		<?foreach ($arMainLinks as $arItem): ?>
			<div class="personal__main-link__wrapper grid-list__item">
				<a href="<?=htmlspecialcharsbx($arItem['url'])?>" class="line-block line-block--column line-block--align-normal line-block--gap line-block--gap-36 no-decoration p p--24 bordered outer-rounded-x shadow-hovered shadow-hovered-f600 shadow-no-border-hovered stroke-grey-parent height-100">
					<div class="line-block line-block--gap line-block--gap-16 line-block--justify-between">
						<div class="personal__main-link__image"><?=$arItem['icon']?></div>
						<div class="grid-center personal__arrow line-block--self-start">
							<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'stroke-grey-target', ['WIDTH' => 6,'HEIGHT' => 12]);?>
						</div>
					</div>
					<div class="personal__main-link__text">
						<div class="word-break switcher-title font_clamp--16-14 color_dark"><?=htmlspecialcharsbx($arItem['name'])?></div>
						<div class="word-break font_13 secondary-color mt mt--2"><?=htmlspecialcharsbx($arItem['dsc'])?></div>
					</div>
				</a>
			</div>
		<?endforeach;?>
	</div>
<?endif;?>
<?

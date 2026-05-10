<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arComponentParams = [
	"PRODUCTS_PER_PAGE" => $arParams['VOTE_PRODUCTS_PER_MAIN_PAGE'],
	"ORDER_STATUSES" => $arParams['VOTE_ORDER_STATUSES'],
	"BLOG_URL" => $arParams['VOTE_BLOG_URL'],
];

ob_start();
$cnt = $APPLICATION->IncludeComponent(
	"aspro:vote.products.premier",
	"",
	$arComponentParams,
	$component,
	array("HIDE_ICONS" => "Y")
);
$html = ob_get_clean();

if ($cnt > 0) {
	?>
	<div class="main-block__title-wrapper mt mt--40">
		<h3 class="main-block__title switcher-title">
			<div class="main-block__title-inner">
				<span><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_VOTES')?></span>
				<span class="main-block__title-count bordered rounded-x font_14"><?=$cnt?></span>
				<div class="js_clear_votes font_13 link-opacity-color secondary-to-title-hover"><?=Loc::getMessage('SPS_CLEAR_VOTES')?></div>
			</div>
		</h3>
	</div>
	
	<?=$html?>
	<?
}

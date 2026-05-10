<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use CPremier as Solution;
use Dnk\PhpInterface\Utils;

$bonusBalance = Utils::getAsproBonusBalance((int)($arResult['USER_ID'] ?? 0));
$bonusFormatted = htmlspecialcharsbx(number_format(Utils::roundMoney($bonusBalance), 2, ',', ' '));
$bonusHistoryUrl = htmlspecialcharsbx(rtrim(SITE_DIR, '/') . '/personal/bonus/history/');
?>
<div class="grid-list grid-list--items grid-list--fill-bg grid-list--items-1">
	<div class="personal__main-private__wrapper personal__main-private__wrapper--bonuses grid-list__item">
		<div class="personal__main-private p p--24 bordered outer-rounded-x shadow-hovered shadow-hovered-f600 shadow-no-border-hovered stroke-grey-parent">
			<a class="item-link-absolute" href="<?=$bonusHistoryUrl?>" title="<?=htmlspecialcharsbx(Loc::getMessage('SPS_MAIN_BLOCK_TITLE_BONUSES'))?>"></a>

			<div class="personal__main-private__inner">
				<div class="personal__main-private__top">
					<span class="main-block__link">
						<span class="main-block__arrow">
							<?=Solution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'stroke-grey-target', [
								'WIDTH' => 6,
								'HEIGHT' => 12,
							]);?>
						</span>
					</span>

					<div class="personal__main-private__title font_clamp--16-14 color-theme-target"><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_BONUSES')?></div>
					<div class="personal__main-private__value switcher-title color_dark font_24 mt mt--4">
						<?=$bonusFormatted?> <?=htmlspecialcharsbx(Loc::getMessage('SPS_BONUS_BALANCE_UNIT'))?>
					</div>
				</div>
				<div class="personal__main-private__bottom font_clamp--16-14">
					<a class="btn btn-xs btn-default btn-transparent-bg personal__main-account__more-details" href="<?=$bonusHistoryUrl?>"><?=Loc::getMessage('SPS_MORE_DETAILS')?></a>
				</div>
			</div>
		</div>
	</div>
</div>

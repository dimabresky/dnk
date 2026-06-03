<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\Utils;

$userId = (int)($arResult['USER_ID'] ?? 0);
$levelData = Utils::getUserBonusClientLevelDisplayData($userId);

$levelName = htmlspecialcharsbx($levelData['name']);
$showNextLevel = $levelData['show_next_level'];
$nextLevelFormatted = '';
if ($showNextLevel && $levelData['next_level_cost'] !== null) {
	$nextLevelFormatted = htmlspecialcharsbx(
		number_format(Utils::roundMoney($levelData['next_level_cost']), 2, ',', ' ')
	);
}
?>
<div class="grid-list grid-list--items grid-list--fill-bg grid-list--items-1">
	<div class="personal__main-private__wrapper personal__main-private__wrapper--user-level grid-list__item">
		<div class="personal__main-private p p--24 bordered outer-rounded-x shadow-hovered shadow-hovered-f600 shadow-no-border-hovered stroke-grey-parent">
			<div class="personal__main-private__inner">
				<div class="personal__main-private__top">
					<div class="personal__main-private__title font_clamp--16-14 color-theme-target"><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_USER_LEVEL')?></div>
					<div class="personal__main-private__value switcher-title color_dark font_24 mt mt--4">
						<?=$levelName?>
					</div>
					<?if ($showNextLevel):?>
						<div class="personal__main-private__subtitle font_clamp--16-14 color_666 mt mt--8">
							<?=Loc::getMessage('SPS_USER_LEVEL_NEXT_LEVEL', ['#SUM#' => $nextLevelFormatted])?>
						</div>
					<?endif;?>
				</div>
			</div>
		</div>
	</div>
</div>

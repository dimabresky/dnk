<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);

$arItems = $arResult['SECTIONS'];
if (!$arItems) return;

$bHighElement = $arParams['HIGH_ELEMENT'] === 'Y';
$bShowImage = $arParams['IMAGES'] !== 'N' && in_array('PICTURE', $arParams['SECTION_FIELDS']);
$bIcons = $arParams['IMAGES'] === 'ICONS';
$bBordered = $arParams['BORDERED'] === 'Y';
$bFon = $arParams['FON'] === 'Y';
$bMaxWidthWrap = $arParams['MAXWIDTH_WRAP'] !== "N";

$gridClass = ['grid-list'];
$gridClass[] = \TSolution\Functions::getGridClassByCount(['768', '992', '1200'], $arParams['ELEMENTS_IN_ROW']);
if (
	!isset($arParams['MOBILE_SCROLLED'])
	|| isset($arParams['MOBILE_SCROLLED']) && $arParams['MOBILE_SCROLLED']
) {
	$gridClass[] = 'mobile-scrolled mobile-scrolled--items-2 mobile-offset';
} else {
	$gridClass[] = 'grid-list--normal';
}

$itemWrapperClasses = ['ui-card grid-list__item ui-card--image-scale stroke-theme-parent-all colored_theme_hover_bg-block color-theme-parent-all'];
if ($bBordered) {
	$itemWrapperClasses[] = 'bordered';
}
if ($bFon) {
	$itemWrapperClasses[] = 'grey-bg';
}
if ($bBordered || $bFon) {
	$itemWrapperClasses[] = 'outer-rounded-x';
}

$gridClass = TSolution\Utils::implodeClasses($gridClass);
$itemWrapperClasses = TSolution\Utils::implodeClasses($itemWrapperClasses);
?>

<div class="sections-block <?=$templateName?>-template">
	<?=TSolution\Functions::showTitleBlock([
		'PATH' => 'sections-list',
		'PARAMS' => $arParams
	]);?>
	<?if ($bMaxWidthWrap):?>
	<div class="maxwidth-theme">
	<?endif;?>
		<div class="<?=$gridClass;?>">
			<?foreach($arItems as $i => $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				$arSectionButtons = CIBlock::GetPanelButtons($arItem['IBLOCK_ID'], 0, $arItem['ID'], array('SESSID' => false, 'CATALOG' => true));
				$this->AddEditAction($arItem['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_EDIT'));
				$this->AddDeleteAction($arItem['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

				// detail url
				$detailUrl = $arItem['SECTION_PAGE_URL'];
				if ($arParams['USE_FILTER_SECTION'] == 'Y' && $arParams['BRAND_NAME'] && $arParams['BRAND_CODE']) {
					$detailUrl .= "filter/brand-is-".$arParams['BRAND_CODE']."/apply/";
				}

				// preview image
				if ($bShowImage) {
					if ($bIcons) {
						$nImageID = $arItem['UF_CATALOG_ICON'];
					} else {
						$nImageID = is_array($arItem['PICTURE']) 
							? $arItem['PICTURE']['ID'] 
							: $arItem['~PICTURE'];
					}
					$imageSrc = $nImageID ? CFile::ResizeImageGet($nImageID, $arResult["RESIZE_OPTIONS"], BX_RESIZE_IMAGE_PROPORTIONAL_ALT)['src'] : '';
				}
				?>
				<div class="<?=$itemWrapperClasses;?>" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
					<a href="<?=$detailUrl;?>" class="flexbox height-100 no-decoration">
						<?if ($bShowImage && $imageSrc):?>
							<?
							$imageWrapperClassList = [];

							if (!$bBordered && !$bFon) {
								$imageWrapperClassList[] = 'image-rounded-x';
							}
							if ($bIcons) {
								$imageWrapperClassList[] = 'p-inline p-inline--24 pt pt--24 pb pb--20';
							} else {
								$imageWrapperClassList[] = 'ui-card__image';
							}
							
							$imageWrapperClassList = TSolution\Utils::implodeClasses($imageWrapperClassList);
							?>
							<div class="<?=$imageWrapperClassList;?>">

								<?if ($bIcons && $nImageID):?>
									<?=TSolution::showIconSvg('fill-theme', $imageSrc);?>
								<?else:?>
									<img src="<?=$imageSrc;?>" class="ui-card__img img" alt="<?=$arItem['PREVIEW_PICTURE']['ALT'] ?? $arItem['NAME'];?>" title="<?=$arItem['PREVIEW_PICTURE']['ALT'] ?? $arItem['NAME'];?>" decoding="async">
								<?endif;?>

							</div>
						<?endif;?>

						<div class="p-block p-block--20 p-inline p-inline--24 flexbox">
							<span class="blog-item__title color-theme-target font_16 no-decoration switcher-title lineclamp-3">
								<?=$arItem['NAME'];?>
							</span>

							<?if (strlen($arItem['DESCRIPTION'])):?>
								<div class="blog-item__text lineclamp-4 font_14 secondary-color mt mt--8">
									<?=$arItem['DESCRIPTION'];?>
								</div>
							<?endif;?>

						</div>
					</a>
				</div>
			<?endforeach;?>
		</div>
	<?if ($bMaxWidthWrap):?>
	</div>
	<?endif;?>
</div> <?// .sections-block?>
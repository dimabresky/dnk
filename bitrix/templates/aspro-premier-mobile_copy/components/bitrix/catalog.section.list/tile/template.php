<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);

$arItems = $arResult['SECTIONS'];
if (empty($arItems)) return;

$arParams['IMAGES'] = $arParams['IMAGES'] ?? 'PICTURES';
$bIcons = $arParams['IMAGES'] === 'ICONS';
$bPictures = $arParams['IMAGES'] === 'PICTURES';
$bTransparentPictures = $arParams['IMAGES'] === 'TRANSPARENT_PICTURES';
$bNarrow = $arParams['NARROW'] === 'Y';
$bMobileScrolled = $arParams['MOBILE_SCROLLED'] !== 'N';
$bBordered = $arParams['BORDERED'] !== 'N';
$bFon = $arParams['IMAGE_ON_FON'] !== 'N';
$bShowImage = $bIcons || in_array('PICTURE', $arParams['SECTION_FIELDS']) || in_array('UF_TRANSPARENT_PICTURE', $arParams['SECTION_USER_FIELDS']);

$gridClass = ['grid-list'];
$gridClass[] = \TSolution\Functions::getGridClassByCount(['768', '992', '1200'], $arParams['ELEMENTS_IN_ROW']);

if ($bMobileScrolled) {
	$gridClass[] = 'mobile-scrolled mobile-scrolled--items-3 mobile-scrolled--small-offset mobile-offset';
}

$itemWrapperClasses = ['grid-list__item stroke-theme-parent-all colored_theme_hover_bg-block animate-arrow-hover'];

$imageWrapperClasses = [
	'no-shrinked line-block__item line-block line-block--gap line-block--justify-center',
	'sections-tile__item-image-wrapper--'.$arParams['IMAGES'],
];
switch ($arParams['ELEMENTS_IN_ROW']) {
	case '6':
		$imageWrapperClasses[] = 'sections-tile__item-image-wrapper--items-small';
		break;
	case '5':
		$imageWrapperClasses[] = 'sections-tile__item-image-wrapper--items-medium';
		break;
}

$itemClasses = ['height-100 outer-rounded-x color-dark-parent-all p p--24'];
if ($bBordered) {
	$itemClasses[] = 'bordered';
} 
if ($bFon) {
	$itemClasses[] = 'grey-bg';
} else {
	$itemClasses[] = 'white-bg';
}

if ($bBordered || $bFon) {
	$itemClasses[] = 'shadow-hovered shadow-no-border-hovered';
} 

$imageClass = [];
if ($bPictures) {
	$imageWrapperClasses[] = 'rounded relative overflow-block';
	$imageClass[] = 'absolute fit-image';
}

$gridClass = TSolution\Utils::implodeClasses($gridClass);
$imageWrapperClasses = TSolution\Utils::implodeClasses($imageWrapperClasses);
$imageClass = TSolution\Utils::implodeClasses($imageClass);
$itemClasses = TSolution\Utils::implodeClasses($itemClasses);
$itemWrapperClasses = TSolution\Utils::implodeClasses($itemWrapperClasses);
?>

<div class="sections-tile <?=$templateName?>-template">
	<?TSolution\Functions::showTitleBlock([
		'PATH' => 'sections-tile',
		'PARAMS' => $arParams
	]);?>

	<?if (!$bNarrow):?>
		<div class="maxwidth-theme">
	<?endif;?>

		<div class="<?=$gridClass;?>">
			<?foreach ($arItems as $i => $arItem):?>
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
						$nImageID = $arItem['~UF_CATALOG_ICON'];
					} elseif ($bTransparentPictures) {
						$nImageID = $arItem['~UF_TRANSPARENT_PICTURE'];
					} else {
						$nImageID = is_array($arItem['PICTURE']) ? $arItem['PICTURE']['ID'] : $arItem['~PICTURE'];
					}
					$imageSrc = ($nImageID ? CFile::ResizeImageGet($nImageID, $arResult["RESIZE_OPTIONS"], BX_RESIZE_IMAGE_PROPORTIONAL_ALT)['src'] : SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg');
				}?>
				<div class="sections-tile__wrapper <?=$itemWrapperClasses?>">
					<div class="sections-tile__item <?=$itemClasses?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
						<a class="sections-tile__item-link flexbox gap gap--40 dark_link height-100" href="<?=$detailUrl?>">
							<span class="flexbox gap gap--6">
								<div class="line-block line-block--gap line-block--gap-4 line-block--justify-between line-block--align-flex-start">
									<span class="sections-tile__item-text lineclamp-4 font_16 color-dark-target fw-500">
										<?=$arItem['NAME'];?>
									</span>
									<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'sections-tile__item-arrow mt mt--2 wrapper stroke-dark-light', [
										'WIDTH' => 6,
										'HEIGHT' => 12,
									]);?>
								</div>
								<?if($arParams['COUNT_ELEMENTS'] && $arItem['ELEMENT_CNT']):?>
									<span class="font_12 secondary-color"><?=TSolution\Functions::declOfNum($arItem['ELEMENT_CNT'], [Loc::getMessage('COUNT_ELEMENTS_TITLE'), Loc::getMessage('COUNT_ELEMENTS_TITLE_2'), Loc::getMessage('COUNT_ELEMENTS_TITLE_3')])?></span>
								<?endif;?>
							</span>
							<span class="sections-tile__item-inner flexbox">
								<?if ($bShowImage && $imageSrc):?>
									<span class="sections-tile__item-image-wrapper <?=$imageWrapperClasses?>">
										<?if ($bIcons && $nImageID):?>
											<?$svgInline = strpos($imageSrc, ".svg") !== false && TSolution::getFrontParametrValue('COLORED_CATALOG_ICON') === 'Y';?>
											<?if ($svgInline):?>
												<?TSolution\Functions::showSVG([
													'PATH' => $imageSrc,
												]);?>
											<?else:?>
												<img src="<?=$imageSrc?>" class="sections-tile__item-image" alt="<?=$arItem['NAME'];?>" title="<?=$arItem['NAME'];?>" />
											<?endif;?>
										<?else:?>
											<img src="<?=$imageSrc?>" class="sections-tile__item-image img <?=$imageClass?>" alt="<?=$arItem['NAME'];?>" title="<?=$arItem['NAME'];?>" />
										<?endif;?>
									</span>
								<?endif;?>
							</span>
						</a>
					</div>
				</div>
			<?endforeach;?>
		</div>
		
	<?if (!$bNarrow):?>
		</div>
	<?endif;?>
</div> <?// .sections-tile?>
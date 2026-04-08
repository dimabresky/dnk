<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc,
	Aspro\Premier\Utils;

$this->setFrameMode(true);

$arItems = $arResult['SECTIONS'];
?>
<? if ($arItems) : ?>
	<?
	$arParams['IMAGES'] = $arParams['IMAGES'] ?? 'PICTURES';
	$bIcons = $arParams['IMAGES'] === 'ICONS';
	$bPictures = $arParams['IMAGES'] === 'PICTURES';
	$bTransparentPictures = $arParams['IMAGES'] === 'TRANSPARENT_PICTURES';
	$bNarrow = $arParams['NARROW'] === 'Y';
	$bMobileScrolled = $arParams['MOBILE_SCROLLED'] === 'Y';
	$bTopImage = $arParams['IMAGES_POSITION'] === 'TOP';
	$iVisibleItemsMenu = TSolution::GetFrontParametrValue('MAX_VISIBLE_ITEMS_MENU');
	$bClickToShowForthDepth = TSolution::GetFrontParametrValue('CLICK_TO_SHOW_4DEPTH') === 'Y';

	$gridClass = ['grid-list row-gap row-gap--48 column-gap column-gap--24'];
	$gridClass[] = \TSolution\Functions::getGridClassByCount(['992', '1200'], $arParams['ELEMENTS_IN_ROW']);
	if ($bMobileScrolled) {
		$gridClass[] = 'mobile-scrolled mobile-offset  mobile-scrolled--items-2';
	} else {
		$gridClass[] = $bTopImage ? 'grid-list--items-2-to-600' : '';
	}

	$itemClasses = ['grid-list__item'];
	if($bTopImage){
		$itemClasses[] = 'line-block--column';
	}

	$imageWrapperClasses = ['no-shrinked line-block__item line-block line-block--gap line-block--justify-center sections-list-full__item-image-wrapper--' . $arParams['IMAGES']]; 
	$imageClass = [];
	
	if ($bPictures) {
		$imageWrapperClasses[] = 'rounded relative overflow-block';
		$imageClass[] = 'absolute fit-image';
	}

	$imageWrapperClasses = Utils::implodeClasses($imageWrapperClasses);
	$imageClass = Utils::implodeClasses($imageClass);
	$itemClasses = Utils::implodeClasses($itemClasses);
	$gridClass = Utils::implodeClasses($gridClass);
	?>

	<div class="sections-list-full <?= $templateName ?>-template">
		<?= TSolution\Functions::showTitleBlock([
			'PATH' => 'sections-list',
			'PARAMS' => $arParams
		]); ?>

		<? if (!$bNarrow) : ?>
			<div class="maxwidth-theme">
			<? endif; ?>
			<div class="sections-list-full__items <?= $gridClass ?> <?=$bTopImage ? 'sections-list-full__items--top-image' : ''?>">
				<? $bShowImage = $bIcons || in_array('PICTURE', $arParams['SECTION_FIELDS']) || in_array('UF_TRANSPARENT_PICTURE', $arParams['SECTION_USER_FIELDS']);

				foreach ($arItems as $i => $arItem) : ?>
					<?
					// edit/add/delete buttons for edit mode
					$arSectionButtons = CIBlock::GetPanelButtons($arItem['IBLOCK_ID'], 0, $arItem['ID'], array('SESSID' => false, 'CATALOG' => true));
					$this->AddEditAction($arItem['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_EDIT'));
					$this->AddDeleteAction($arItem['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

					// detail url
					$detailUrl = $arItem['SECTION_PAGE_URL'];
					if ($arParams['USE_FILTER_SECTION'] == 'Y' && $arParams['BRAND_NAME'] && $arParams['BRAND_CODE']) {
						$detailUrl .= "filter/brand-is-" . $arParams['BRAND_CODE'] . "/apply/";
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
						$imageSrc = ($nImageID ? CFile::ResizeImageGet($nImageID, $arResult["RESIZE_OPTIONS"], BX_RESIZE_IMAGE_PROPORTIONAL_ALT)['src'] : SITE_TEMPLATE_PATH . '/images/svg/noimage_product.svg');
					} ?>
					<div class="sections-list-full__item line-block line-block--gap line-block--gap-20 line-block--align-normal <?= $itemClasses ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
							<? if ($bShowImage && $imageSrc) : ?>
								<a class="sections-list-full__item-image-wrapper <?= $imageWrapperClasses ?>" href="<?= $detailUrl ?>">
									<? if ($bIcons && $nImageID) : ?>
										<? $svgInline = strpos($imageSrc, ".svg") !== false && TSolution::getFrontParametrValue('COLORED_CATALOG_ICON') === 'Y';
											?>
										<? if ($svgInline) : ?>
											<? TSolution\Functions::showSVG([
												'PATH' => $imageSrc,
											]); ?>
										<? else : ?>
											<img src="<?= $imageSrc ?>" class="sections-list-full__item-image" alt="<?= $arItem['NAME']; ?>" />
										<? endif; ?>
									<? else : ?>
										<img src="<?= $imageSrc ?>" class="sections-list-full__item-image img <?=$imageClass?>" alt="<?= $arItem['NAME']; ?>" />
									<? endif; ?>
								</a>
							<? endif; ?>
							<div class="sections-list-full__item-info line-block__item line-block line-block--column line-block--align-normal line-block--gap line-block--gap-12">
								<div class="line-block__item line-block line-block--column line-block--align-normal line-block--gap line-block--gap-6">
									<a class="underline-hover link " href="<?= $detailUrl ?>">
										<span class="sections-list-full__item-text font_16 switcher-title lineclamp-3"><?= $arItem['NAME']; ?></span>
									</a>
									<?if($arParams['COUNT_ELEMENTS'] && $arItem['ELEMENT_CNT']):?>
										<span class="font_12 secondary-color"><?=TSolution\Functions::declOfNum($arItem['ELEMENT_CNT'], [Loc::getMessage('COUNT_ELEMENTS_TITLE'), Loc::getMessage('COUNT_ELEMENTS_TITLE_2'), Loc::getMessage('COUNT_ELEMENTS_TITLE_3')])?></span>
									<?endif;?>
								</div>
								<?if($arItem["SECTIONS"]):?>
									<div class="sections-list-full__submenu line-block__item line-block line-block--column line-block--gap line-block--gap-8 line-block--align-normal">
										<?
										$iCountChilds = count($arItem["SECTIONS"]);
										$counterSubSections = 1;										
										?>
										<?foreach ($arItem["SECTIONS"] as $j => $arSubItem) : ?>
											<?
											$bCollapsed = $counterSubSections > $iVisibleItemsMenu;
											?>
											<div class="line-block__item sections-list-full__submenu-item font_14 <?=$bCollapsed ? 'collapsed' : ''?>" <?=($bCollapsed ? 'style="display: none;"' : '');?>>
												<a class="no-decoration stroke-monochrome primary-color" href="<?= $arSubItem["SECTION_PAGE_URL"] ?>">
													<span class="sections-list-full__item-text link underline-hover lineclamp-3"><?= $arSubItem['NAME']; ?></span>
												</a>
											</div>
											<?$counterSubSections++;?>
										<?endforeach;?>
										<?if ($iCountChilds > $iVisibleItemsMenu):?>
											<div class="sections-list-full__submenu-item--more_items show-more-items-btn font_14" role="none">
												<button type="button" class="dotted no-decoration-hover width-100 text-align-left with_dropdown svg relative btn--no-btn-appearance color_dark">
													<?=\Bitrix\Main\Localization\Loc::getMessage("S_MORE_ITEMS");?>
												</button>
											</div>
										<?endif;?>
									</div>
								<?endif;?>
							</div>
						
					</div>
				<? endforeach; ?>
			</div>
			<? if (!$bNarrow) : ?>
			</div>
		<? endif; ?>
	</div> <? // .sections-list-full
					?>
<? endif; ?>
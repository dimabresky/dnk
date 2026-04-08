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
	$bBordered = $arParams['BORDERED'] !== 'N';
	$bFon = $arParams['IMAGE_ON_FON'] !== 'N';

	$listWrapClasses = [];
	if ($bMobileScrolled) {
		$listWrapClasses[] = 'mobile-scrolled mobile-scrolled--items-auto mobile-scrolled--small-offset mobile-offset';
	} else {
		$listWrapClasses[] = 'sections-list__inner--mobile-normal';
	}

	$itemWrapperClasses = ['line-block__item stroke-theme-parent-all colored_theme_hover_bg-block animate-arrow-hover'];
	$itemClasses = ['height-100 outer-rounded-x color-dark-parent-all'];
	$imageWrapperClasses = ['no-shrinked line-block__item line-block line-block--gap line-block--justify-center sections-list__item-image-wrapper--' . $arParams['IMAGES']]; 
	$imageClass = [];

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
	
	if ($bPictures) {
		$imageWrapperClasses[] = 'rounded relative overflow-block';
		$imageClass[] = 'absolute fit-image';
	}

	$imageWrapperClasses = Utils::implodeClasses($imageWrapperClasses);
	$imageClass = Utils::implodeClasses($imageClass);
	$itemClasses = Utils::implodeClasses($itemClasses);
	$itemWrapperClasses = Utils::implodeClasses($itemWrapperClasses);
	$listWrapClasses = Utils::implodeClasses($listWrapClasses);
	?>

	<div class="sections-list <?= $templateName ?>-template">
		<?= TSolution\Functions::showTitleBlock([
			'PATH' => 'sections-list',
			'PARAMS' => $arParams
		]); ?>

		<? if (!$bNarrow) : ?>
			<div class="maxwidth-theme">
			<? endif; ?>
			<div class="sections-list__inner line-block line-block--align-normal line-block--flex-wrap line-block--gap line-block--gap-8-to-600 <?= $listWrapClasses ?>">
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
					<div class="sections-list__wrapper <?= $itemWrapperClasses ?>">
						<div class="sections-list__item <?= $itemClasses ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
							<a class="sections-list__item-link d-block dark_link height-100" href="<?= $detailUrl ?>">

								<span class="sections-list__item-inner line-block line-block--gap line-block--gap-24 line-block--gap-16-to-600 height-100">
									<? if ($bShowImage && $imageSrc) : ?>
										<span class="sections-list__item-image-wrapper <?= $imageWrapperClasses ?>">
											<? if ($bIcons && $nImageID) : ?>
												<? $svgInline = strpos($imageSrc, ".svg") !== false && TSolution::getFrontParametrValue('COLORED_CATALOG_ICON') === 'Y';
												 ?>
												<? if ($svgInline) : ?>
													<? TSolution\Functions::showSVG([
														'PATH' => $imageSrc,
													]); ?>
												<? else : ?>
													<img src="<?= $imageSrc ?>" class="sections-list__item-image" alt="<?= $arItem['NAME']; ?>" />
												<? endif; ?>
											<? else : ?>
												<img src="<?= $imageSrc ?>" class="sections-list__item-image img <?=$imageClass?>" alt="<?= $arItem['NAME']; ?>" />
											<? endif; ?>
										</span>
									<? endif; ?>
									<span class="flexbox gap gap--4">
										<span class="sections-list__item-text lineclamp-4 font_15 color-dark-target fw-500"><?= $arItem['NAME']; ?></span>
										<?if($arParams['COUNT_ELEMENTS'] && $arItem['ELEMENT_CNT']):?>
											<span class="font_12 secondary-color"><?=TSolution\Functions::declOfNum($arItem['ELEMENT_CNT'], [Loc::getMessage('COUNT_ELEMENTS_TITLE'), Loc::getMessage('COUNT_ELEMENTS_TITLE_2'), Loc::getMessage('COUNT_ELEMENTS_TITLE_3')])?></span>
										<?endif;?>
									</span>
								</span>
							</a>
						</div>
					</div>
				<? endforeach; ?>

				<? if ($arParams['RIGHT_LINK']) : ?>
					<div class="sections-list__wrapper sections-list-right-link stroke-dark-light-block <?= $itemWrapperClasses ?>">
						<div class="sections-list__item <?= $itemClasses ?>">
							<a class="sections-list__item-link flexbox flexbox--row height-100 dark_link" href="<?= $arParams['RIGHT_LINK']; ?>">
								<span class="sections-list__item-inner line-block line-block--gap line-block--gap-20">
									<span class="sections-list__item-text font_15 fw-500"><?= $arParams['RIGHT_TITLE'] ?: Loc::getMessage('ALL_CATALOG'); ?></span>
									<?= TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#right-hollow', 'arrow stroke-dark-light', ['WIDTH' => 6, 'HEIGHT' => 12]); ?>
								</span>
							</a>
						</div>
					</div>
				<? endif; ?>
			</div>
			<? if (!$bNarrow) : ?>
			</div>
		<? endif; ?>
	</div> <? // .sections-list
					?>
<? endif; ?>
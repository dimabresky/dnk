<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc,
	Aspro\Premier\Utils;
$this->setFrameMode(true);

$arItems = $arResult['SECTIONS'];?>
<?if($arItems):?>
	<?
	$bNarrow = $arParams['NARROW'] === 'Y';
	$arParams['IMAGES'] = $arParams['IMAGES'] ?? 'PICTURES';
	$bIcons = $arParams['IMAGES'] === 'ICONS';
	$bPictures = $arParams['IMAGES'] === 'PICTURES';
	$bTransparentPictures = $arParams['IMAGES'] === 'TRANSPARENT_PICTURES';
	$bPaddings = $bTransparentPictures || $bIcons;
	$bBig = $arParams['COMPACT_VIEW'] === 'N';

	$itemWrapperClasses = ['swiper-slide'];
	$itemClasses = ['height-100  color-dark-parent-all'];
	$imageWrapperClasses = ['outer-rounded-x relative overflow-block no-shrinked line-block line-block--gap line-block--justify-center sections-slider__item-image-wrapper--'.$arParams['IMAGES']];
	$imageClass = [];

	if ($bBig) {
		$itemClasses[] = 'sections-slider__item--big';
	}

	if ($arParams['IMAGE_ON_FON'] !== 'N') {
		$imageWrapperClasses[] = 'grey-bg';
	} else {
		$imageWrapperClasses[] = 'white-bg';
	}

	if ($arParams['BORDERED'] !== 'N') {
		$imageWrapperClasses[] = 'bordered';
	}

	if ($bPaddings) {
		$imageWrapperClasses[] = $bBig ? 'p p--48' : 'p p--32';
	}

	if ($bPictures) {
		$imageWrapperClasses[] = 'image-rounded-x';
		$imageClass[] = 'absolute fit-image';
	}

	$imageWrapperClasses = Utils::implodeClasses($imageWrapperClasses);
	$imageClass = Utils::implodeClasses($imageClass);
	$itemClasses = Utils::implodeClasses($itemClasses);
	$itemWrapperClasses = Utils::implodeClasses($itemWrapperClasses);
	?>
	
	<div class="sections-slider <?=$templateName?>-template">
		<?=TSolution\Functions::showTitleBlock([
			'PATH' => 'sections-list',
			'PARAMS' => $arParams
		]);?>
	
		<?if (!$bNarrow):?>
		<div class="maxwidth-theme">
		<?endif;?>
			<?
			$countSlides = count($arItems);
			$arOptions = [
				// Disable preloading of all images
				'preloadImages' => false,
				// Enable lazy loading
				'lazy' => false,
				'keyboard' => true,
				'init' => false,
				'countSlides' => $countSlides,
				'rewind'=> true,
				'freeMode' => ['enabled' => true, 'momentum' => true],
				'slidesPerView' => 'auto',
				'pagination' => false,
				// 'autoplay' => ['delay' => $slideshowSpeed,],
				'type' => 'main_sections',
			];				
			?>
			<div class="swiper-nav-offset relative">
				<div class="swiper slider-solution slider-solution--static-dots appear-block mobile-offset mobile-offset--right" data-plugin-options='<?=json_encode($arOptions)?>'>
					<div class="swiper-wrapper">
						<?$bShowImage = $bIcons ||  in_array('PICTURE', $arParams['SECTION_FIELDS']) || in_array('UF_TRANSPARENT_PICTURE', $arParams['SECTION_USER_FIELDS']);

						foreach($arItems as $i => $arItem):?>
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
							<div class="sections-slider__wrapper <?=$itemWrapperClasses?>">
								<div class="sections-slider__item <?=$itemClasses?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
									<a class="sections-slider__item-link centered d-block dark_link height-100" href="<?=$detailUrl?>">
									
										<span class="sections-slider__item-inner height-100">
											<?if($bShowImage && $imageSrc):?>
												<span class="sections-slider__item-image-wrapper <?=$imageWrapperClasses?>">
													<?if($bIcons && $nImageID):?>
														<? $svgInline = strpos($imageSrc, ".svg") !== false && TSolution::getFrontParametrValue('COLORED_CATALOG_ICON') === 'Y';
														?>
														<?if ($svgInline):?>
															<? TSolution\Functions::showSVG([
																'PATH' => $imageSrc,
															]); ?>
														<?else:?>
															<img src="<?=$imageSrc?>" class="sections-slider__item-image" alt="<?=$arItem['NAME'];?>"/>
														<?endif;?>
													<?else:?>
														<img src="<?=$imageSrc?>" class="sections-slider__item-image img <?=$imageClass?>" alt="<?=$arItem['NAME'];?>"/>
													<?endif;?>
												</span>
											<?endif;?>
											<span class="sections-slider__item-text mt mt--12 d-block color-dark-target fw-500 font_14 font_short">
												<?=$arItem['NAME'];?>
												<?=($arParams['COUNT_ELEMENTS'] && $arItem['ELEMENT_CNT']) ? "({$arItem['ELEMENT_CNT']})" : '';?>
											</span>
										</span>
									</a>
								</div>
							</div>
						<?endforeach;?>

					</div>
				</div>
				<?if ($arOptions['countSlides'] > 1):?>
					<div class="slider-nav swiper-button-prev slider-nav--shadow"><?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#left-hollow', 'stroke-dark-light', ['WIDTH' => 6,'HEIGHT' => 12]);?></div>
					<div class="slider-nav swiper-button-next slider-nav--shadow"><?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'stroke-dark-light', ['WIDTH' => 6,'HEIGHT' => 12]);?></div>
				<?endif;?>
			</div>
		<?if (!$bNarrow):?>
		</div>
		<?endif;?>
	</div> <?// .sections-slider?>
<?endif;?>
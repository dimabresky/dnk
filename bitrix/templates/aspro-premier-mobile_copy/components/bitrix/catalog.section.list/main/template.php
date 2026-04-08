<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);

$arItems = $arResult['SECTIONS'];?>
<?if($arItems):?>
	<?
	$bNarrow = $arParams['NARROW'] === 'Y';
	$bMobileScrolled = $arParams['MOBILE_SCROLLED'] !== 'N';
	$bHighElement = $arParams['HIGH_ELEMENT'] === 'Y';

	$gridClass = ['ui-cards grid-list'];
	$gridClass[] = \TSolution\Functions::getGridClassByCount(['768', '992', '1200'], $arParams['ELEMENTS_IN_ROW']);

	$imageWrapClass = [];
	$imageWrapClass[] = $bHighElement ? 'ui-card__image--ratio-1' : 'ui-card__image--ratio-1-235';

	if ($bMobileScrolled) {
		$gridClass[] = 'mobile-scrolled mobile-offset  mobile-scrolled--items-2';
	} else {
		$gridClass[] = 'normal grid-list--items-2-to-600';
	}
	
	$gridClass = implode(' ', $gridClass);
	$imageWrapClass = implode(' ', $imageWrapClass);
	?>
	
	<div class="sections-block <?=$templateName?>-template">
		<?=TSolution\Functions::showTitleBlock([
			'PATH' => 'sections-list',
			'PARAMS' => $arParams
		]);?>
		<?if (!$bNarrow):?>
		<div class="maxwidth-theme">
		<?endif;?>
			<div class="<?=$gridClass;?>">
				
				<?$bShowImage = in_array('PICTURE', $arParams['SECTION_FIELDS']);

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
						$nImageID = is_array($arItem['PICTURE']) ? $arItem['PICTURE']['ID'] : $arItem['~PICTURE'];
						$imageSrc = ($nImageID ? CFile::ResizeImageGet($nImageID, $arResult["RESIZE_OPTIONS"], BX_RESIZE_IMAGE_PROPORTIONAL_ALT)['src'] : SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg');
					}?>
					<div  class="ui-card grid-list__item outer-rounded-x ui-card--image-scale" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
						<a href="<?=$detailUrl?>" class="cover d-block">
							<div class="ui-card__image <?=$imageWrapClass?>">
								<?if($bShowImage && $imageSrc):?>
									<img src="<?=$imageSrc?>" class="ui-card__img img" alt="<?=$arItem['NAME'];?>" title="<?=$arItem['NAME'];?>" />
								<?endif;?>
							</div>
							<div class="ui-card__info ui-card__info--absolute flexbox flexbox--direction-row flexbox--justify-between flexbox--align-end gap gap--24 z-index-1">
								<div class="flexbox gap gap--6">
									<div class="ui-card__title font_16 fw-500 color_light lineclamp-2"><?=$arItem['NAME'];?></div>
									<?if($arParams['COUNT_ELEMENTS'] && $arItem['ELEMENT_CNT']):?>
										<div class="font_13 color_light--opacity"><?=TSolution\Functions::declOfNum($arItem['ELEMENT_CNT'], [Loc::getMessage('COUNT_ELEMENTS_TITLE'), Loc::getMessage('COUNT_ELEMENTS_TITLE_2'), Loc::getMessage('COUNT_ELEMENTS_TITLE_3')])?></div>
									<?endif;?>
								</div>
								
								<?= TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#right-hollow', 'arrow white-stroke hide-600 mb mb--4', ['WIDTH' => 6, 'HEIGHT' => 12]); ?>
							</div>
						</a>
					</div>
				<?endforeach;?>
			</div>
		<?if (!$bNarrow):?>
		</div>
		<?endif;?>
	</div> <?// .sections-block?>
<?endif;?>
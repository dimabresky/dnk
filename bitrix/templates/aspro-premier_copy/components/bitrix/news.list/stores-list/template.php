<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
use \Bitrix\Main\Localization\Loc;

global $arTheme;
?>
<?if($arResult['SECTIONS']):?>
	<?
	$blockClasses = ($arParams['ITEMS_OFFSET'] ? 'contacts-list--items-offset' : 'contacts-list--items-close');

	$gridClass = 'grid-list grid-list--items';
	if(!$arParams['ITEMS_OFFSET']){
		$gridClass .= ' gap';
	}
	if($arParams['GRID_GAP']){
		$gridClass .= ' gap--'.$arParams['GRID_GAP'];
	}

	$itemWrapperClasses = ' grid-list__item stroke-theme-parent-all colored_theme_hover_bg-block';
	if(!$arParams['ITEMS_OFFSET'] && $arParams['BORDER']){
		$itemWrapperClasses .= ' grid-list-border-outer';
	}

	$itemClasses = 'height-100 flexbox flexbox--direction-row animate-arrow-hover color-theme-parent-all';

	if($arParams['DARK_HOVER']){
		$itemClasses .= ' dark-block-hover';
	}

	$imageWrapperClasses = '';
	$imageClasses = 'image-rounded-x';
	?>
	<?if(!$arParams['IS_AJAX']):?>
		<div class="contacts-list <?=$blockClasses?> <?=$templateName?>-template">
	<?endif;?>

		<?foreach($arResult['SECTIONS'] as $arSection):?>
			<?
			$areaSectionId = '';
			
			$bHasSection = (isset($arSection['SECTION']) && $arSection['SECTION']);
			if($bHasSection){
				// edit/add/delete buttons for edit mode
				$areaSectionId = $this->GetEditAreaId($arSection['SECTION']['ID']);
				$arSectionButtons = CIBlock::GetPanelButtons($arSection['SECTION']['IBLOCK_ID'], 0, $arSection['SECTION']['ID'], array('SESSID' => false, 'CATALOG' => true));
				$this->AddEditAction($arSection['SECTION']['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arSection['SECTION']['IBLOCK_ID'], 'SECTION_EDIT'));
				$this->AddDeleteAction($arSection['SECTION']['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arSection['SECTION']['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			}
			?>
			<div id="<?=$areaSectionId?>" class="contacts-list__section mt mt--48">
				<?if($bHasSection):?>
					<div class="contacts-list__section-content">
						<?if($arParams['SHOW_SECTION_NAME'] != 'N'):?>
							<?if(strlen($arSection['SECTION']['NAME'])):?>
								<div class="contacts-list__section-title switcher-title font_24 color_dark mb mb--16">
									<?=$arSection['SECTION']['NAME']?>
								</div>
							<?endif;?>
						<?endif;?>

						<?if($arParams['SHOW_SECTION_PREVIEW_DESCRIPTION'] == 'Y' && strlen($arSection['SECTION']['DESCRIPTION']) && strpos($_SERVER['REQUEST_URI'], 'PAGEN') === false):?>
							<div class="contacts-list__section-description">
								<?=$arSection['SECTION']['DESCRIPTION']?>
							</div>
						<?endif;?>
					</div>
				<?endif;?>
				<div class="mt mt--24 <?=$gridClass?>">
					<?foreach($arSection['ITEMS'] as $i => $arItem):?>
						<?
						// edit/add/delete buttons for edit mode
						$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
						$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

						// use detail link?
						$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);

						// detail url
						$detailUrl = $arItem['DETAIL_PAGE_URL'];

						// preview picture
						$bImage = isset($arItem['FIELDS']['PREVIEW_PICTURE']) && strlen($arItem['PREVIEW_PICTURE']['SRC']);
						$imageSrc = ($bImage ? $arItem['PREVIEW_PICTURE']['SRC'] : false);
						$imageDetailSrc = ($bImage ? $arItem['DETAIL_PICTURE']['SRC'] : false);

						// address
						$address = $arItem['NAME'].($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? ', '.$arItem['PROPERTIES']['ADDRESS']['VALUE'] : '');
						?>
						<div class="contacts-list__wrapper <?=$itemWrapperClasses?>">
							<div class="contacts-list__item p-block p-block--28 <?=$itemClasses?><?=($bDetailLink ? '' : ' contacts-list__item--cursor-initial')?><?=(!$imageSrc ? ' contacts-list__item-without-image' : '')?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
								<?if($bDetailLink):?>
									<a class="arrow-all arrow-all--wide stroke-theme-target" href="<?=$detailUrl?>">
										<?=TSolution::showIconSvg(' arrow-all__item-arrow', SITE_TEMPLATE_PATH.'/images/svg/Arrow_lg.svg');?>
										<div class="arrow-all__item-line colored_theme_hover_bg-el"></div>
									</a>
								<?endif;?>
								
								<?if($imageSrc):?>
									<div class="contacts-list__item-image-wrapper <?=$imageWrapperClasses?>">
										<?if($bDetailLink):?>
											<a class="contacts-list__item-link" href="<?=$detailUrl?>">
										<?else:?>
											<span class="contacts-list__item-link">
										<?endif;?>
											<span class="contacts-list__item-image <?=$imageClasses?>" style="background-image: url(<?=$imageSrc?>);"></span>
										<?if($bDetailLink):?>
											</a>
										<?else:?>
											</span>
										<?endif;?>
									</div>
								<?endif;?>

								<div class="contacts-list__item-text-wrapper flex-1">
									<div class="contacts-list__item-text-top-part flexbox flexbox--direction-row">
										<div class="contacts-list__item-col contacts-list__item-col--left">
											<?// element name?>
											<div class="contacts-list__item-title switcher-title font_16">
												<?if($bDetailLink):?>
													<a class="no-decoration color-theme-target" href="<?=$detailUrl?>"><?=$address?></a>
												<?else:?>
													<span class="color_222"><?=$address?></span>
												<?endif;?>
											</div>

											<?if(
												$arItem['MIDDLE_PROPS']['MAP']['VALUE'] &&
												$arParams['USE_MAP'] === 'Y'
											):?>
												<?ob_start();?>
												<span class="text_wrap font_14 color-theme" data-coordinates="<?=$arItem['MIDDLE_PROPS']['MAP']['VALUE'];?>">
													<a class="text dotted fw-500"><?=GetMessage('SHOW_ON_MAP')?></a>
												</span>
												<?$htmlCoord = trim(ob_get_clean());?>

												<?if($htmlCoord):?>
													<div class="contacts-list__item-coord show_on_map contacts-list__item--hidden-f1300"><?=$htmlCoord?></div>
												<?endif;?>
											<?endif;?>

											<div class="flexbox gap gap--6 mt mt--8 mb mb--16 font_13 secondary-color">
												<?if($arItem['MIDDLE_PROPS']['METRO']['VALUE']):?>
													<div class="contacts-list__item-metro">
														<?foreach((array)$arItem['MIDDLE_PROPS']['METRO']['VALUE'] as $metro):?>
															<div class="contacts-list__item-metro__value"><span><?=$metro?></span></div>
														<?endforeach;?>
													</div>
												<?endif;?>
	
												<?if($arItem['MIDDLE_PROPS']['SCHEDULE']['VALUE']):?>
													<div class="contacts-list__item-schedule">
														<?if ($arItem['MIDDLE_PROPS']['SCHEDULE']['VALUE']['TYPE'] === 'TEXT'):?>
															<span><?=nl2br($arItem['MIDDLE_PROPS']['SCHEDULE']['~VALUE']['TEXT']);?></span>
														<?else:?>
															<span><?=$arItem['MIDDLE_PROPS']['SCHEDULE']['~VALUE']['TEXT'];?></span>
														<?endif;?>
													</div>
												<?endif;?>
											</div>

											<?ob_start();?>
											<div class="flexbox gap gap--8 font_14">
												<?if($arItem['MIDDLE_PROPS']['PHONE']['VALUE']):?>
													<div class="contacts-list__item-phones flexbox gap gap--2">
														<?foreach((array)$arItem['MIDDLE_PROPS']['PHONE']['VALUE'] as $phone):?>
															<div class="contacts-list__item-phone">
																<a class="no-decoration" href="tel:<?=str_replace(array(' ', ',', '-', '(', ')'), '', $phone)?>"><?=$phone?></a>
															</div>
														<?endforeach;?>
													</div>
												<?endif;?>

												<?if($arItem['MIDDLE_PROPS']['EMAIL']['VALUE']):?>
													<div class="contacts-list__item-emails">
														<?foreach((array)$arItem['MIDDLE_PROPS']['EMAIL']['VALUE'] as $email):?>
															<div class="contacts-list__item-email">
																<a class="no-decoration" href="mailto:<?=$email?>"><?=$email?></a>
															</div>
														<?endforeach;?>
													</div>
												<?endif;?>
											</div>
											<?$htmlInfo = trim(ob_get_clean());?>

											<?if($htmlInfo):?>
												<div class="contacts-list__item-info contacts-list__item--hidden-f1300 mb mb--16"><?=$htmlInfo?></div>
											<?endif;?>

											<?if($arItem['MIDDLE_PROPS']['PAY_TYPE']['VALUE']):?>
												<div class="contacts-list__item-pay line-block line-block--col-gap line-block--col-gap-12 line-block--row-gap line-block--row-gap-4 line-block--flex-wrap">
													<?foreach($arItem['MIDDLE_PROPS']['PAY_TYPE']['FORMAT'] as $arPays):?>
														<span class="contacts-list__item-pay__value" title="<?=$arPays['UF_NAME']?>">
															<?if($arPays['UF_ICON_CLASS']):?><i class="fa <?=$arPays['UF_ICON_CLASS']?>"></i>
															<?elseif($arPays['UF_FILE']):?>
																<span><img src="<?=CFile::GetPath($arPays['UF_FILE'])?>" height="20" alt="<?=$arPays['UF_NAME']?>"/></span>
															<?endif;?> 
															<?if(!$arPays['UF_FILE'] && !$arPays['UF_ICON_CLASS']):?>
																<?=$arPays['UF_NAME']?>
															<?endif;?>
														</span>
													<?endforeach;?>
												</div>
											<?endif;?>

											<?if(array_key_exists('FORMAT_PROPS', $arItem) && $arItem['FORMAT_PROPS']):?>
												<?ob_start();?>
												<?foreach($arItem['FORMAT_PROPS'] as $PCODE => $arProperty):?>
													<div class="contacts-list__item-properties-item-wraper">
														<div class="contacts-list__item-properties-item <?=($bFonImg ? 'color_light--opacity' : 'color_222')?>" data-code="<?=strtolower($PCODE)?>">
															<span class="contacts-list__item-properties-item-name"><?=$arProperty['NAME']?>:&nbsp;</span>
															<?if(is_array($arProperty['DISPLAY_VALUE'])):?>
																<?$val = implode('&nbsp;/&nbsp;', $arProperty['DISPLAY_VALUE']);?>
															<?else:?>
																<?$val = $arProperty['DISPLAY_VALUE'];?>
															<?endif;?>
															<span class="contacts-list__item-properties-item-value"><?=$val?></span>
														</div>
													</div>
												<?endforeach;?>
												<?$htmlProperties = trim(ob_get_clean());?>

												<?if($htmlProperties):?>
													<div class="contacts-list__item-properties font_14"><?=$htmlProperties?></div>
												<?endif;?>
											<?endif?>
										</div>

										<div class="contacts-list__item-col contacts-list__item-col--right contacts-list__item--hidden-t1299">
											<?if($htmlInfo):?>
												<div class="contacts-list__item-info"><?=$htmlInfo?></div>
											<?endif;?>

											<?if($htmlCoord):?>
												<div class="contacts-list__item-coord show_on_map"><?=$htmlCoord?></div>
											<?endif;?>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?endforeach;?>
				</div>
			</div>
		<?endforeach;?>

	<?if(!$arParams['IS_AJAX']):?>
		</div> <?// .contacts-list?>
	<?endif;?>
<?endif;?>
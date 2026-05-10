<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

TSolution\Extensions::init(['tabs', 'tabs.history']);

$arProfilesByPersonType = [];
if (is_array($arResult['PROFILES'])) {
	foreach ($arResult['PROFILES'] as $i => $arProfile) {
		$personTypeId = $arProfile['PERSON_TYPE']['ID'];
	
		if (!isset($arProfilesByPersonType[$personTypeId])) {
			$arProfilesByPersonType[$personTypeId] = [];
		}
		
		$arProfilesByPersonType[$personTypeId][] =& $arResult['PROFILES'][$i];
	}
}

$bShowDetailLink = ($arParams['SHOW_DETAIL_LINK'] ?? 'Y') !== 'N';
?>
<div class="personal__block personal__block--profiles">
	<?if ($arResult['ERROR_MESSAGE']):?>
		<div class="alert alert-danger"><?=implode('<br />', (array)$arResult['ERROR_MESSAGE'])?></div><br />
	<?endif;?>

	<?if ($arResult['PROFILES']):?>
		<div class="tabs-block">
			<div class="tabs tabs-history arrow_scroll">
				<ul class="nav nav-tabs">
					<?$iTab = 0;?>
					<?foreach ($arResult['PERSON_TYPES'] as $arPersonType):?>
						<?$tabCode = 'pt'.$arPersonType['ID'];?>
						<li class="<?=(!($iTab++) ? 'active' : '')?>"><a href="#<?=$tabCode?>" data-toggle="tab"><?=$arPersonType['NAME']?></a></li>
					<?endforeach;?>
				</ul>
			</div>
			<div class="tab-content<?=($iTab < 2 ? ' not_tabs' : '')?>">
				<?$iTab = 0;?>
				<?foreach ($arResult['PERSON_TYPES'] as $arPersonType):?>
					<?$tabCode = 'pt'.$arPersonType['ID'];?>
					<div class="tab-pane <?=(!($iTab++) ? 'active' : '')?>" id="<?=$tabCode?>">
						<div class="profiles__items grid-list grid-list--items grid-list--items-1 gap gap--16">
							<?foreach ($arProfilesByPersonType[$arPersonType['ID']] as $arProfile):?>
								<?
								$title = $arProfile['NAME'];
								?>
								<div class="profiles__item p p--24 grid-list__item outer-rounded-x bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered color-theme-parent-all" data-id="<?=$arProfile['ID']?>">
									<?if ($bShowDetailLink):?>
										<a class="item-link-absolute" href="<?=$arProfile['URL_TO_DETAIL']?>" title="<?=$title?>"></a>
									<?endif;?>

									<div class="profiles__item__inner line-block line-block--align-normal line-block--column line-block--gap line-block--gap-0 line-block--justify-between height-100">
										<div class="profiles__item__top line-block line-block--gap line-block--gap-20 line-block--justify-between line-block--align-flex-start">
											<div class="profiles__item__body line-block__item">
												<div class="profiles__item__subtitle secondary-color font_13 mb mb--4">
													<?=Loc::getMessage('SPPL_TPL_PROFILE')?>
												</div>
												<div class="profiles__item__title font_20 fw-500">
													<?if ($bShowDetailLink):?>
														<a href="<?=$arProfile['URL_TO_DETAIL']?>" class="dark_link color-theme-target"><?=$title?></a>
													<?else:?>
														<span><?=$title?></span>
													<?endif;?>
												</div>
											</div>

											<?if ($bShowDetailLink):?>
												<div class="profiles__item__buttons line-block__item no-shrinked">
													<a href="<?=$arProfile['URL_TO_DETAIL']?>" class="btn btn-secondary-black btn-sm js-profile-change animate-load"><?=Loc::getMessage('SPPL_TPL_CHANGE')?></a>
												</div>
											<?endif;?>
										</div>
										<?if ($arResult['ORDER_PROPS']):?>
											<div class="profiles__item__properties mt--20 mt">
												<div class="line-block line-block--align-normal line-block--column line-block--gap line-block--gap-6">
													<?foreach ($arResult['ORDER_PROPS'] as $propertyId => $property):?>
														<?
														if ($property['PERSON_TYPE_ID'] != $arPersonType['ID']) {
															continue;
														}

														if (
															$property['TYPE'] === 'MULTISELECT'
														) {
															$propertyValue = $arProfile['ORDER_PROPS_VALUES'][$propertyId] ?? unserialize($property['~DEFAULT_VALUE']);
														}
														else {
															$propertyValue = $arProfile['ORDER_PROPS_VALUES'][$propertyId] ?? $property['DEFAULT_VALUE'];
														}

														if (
															(
																is_array($propertyValue) &&
																!$propertyValue
															) ||
															(
																!is_array($propertyValue) &&
																!strlen($propertyValue)
															)
														) {
															continue;
														}

														if ($property['TYPE'] === 'CHECKBOX') {
															$propertyValue = $property['NAME'];
														}

														if (
															$property['TYPE'] === 'SELECT' ||
															$property['TYPE'] === 'MULTISELECT' ||
															$property['TYPE'] === 'RADIO'
														) {
															$tmp = [];

															foreach ((array)$propertyValue as $propertyValueId) {
																$value = $propertyValueId;

																foreach ($property['VALUES'] as $variant) {
																	if ($variant['VALUE'] == $propertyValueId) {
																		$value = $variant['NAME'];
																	}
																}

																$tmp[] = $value;
															}

															$propertyValue = $tmp;
														}
														?>
														<div class="line-block__item">
															<div class="profiles__item__property">
																<div class="profiles__item__property-value">
																	<?=(implode(', ', (array)$propertyValue))?>
																</div>
															</div>
														</div>
													<?endforeach;?>
												</div>
											</div>
										<?endif;?>
									</div>
								</div>
							<?endforeach;?>
						</div>
					</div>
				<?endforeach;?>
			</div>
		</div>
	<?else:?>
		<div class="alert alert-success"><?=Loc::getMessage('SPPL_TPL_EMPTY_PROFILE_LIST')?></div>
	<?endif;?>
</div>

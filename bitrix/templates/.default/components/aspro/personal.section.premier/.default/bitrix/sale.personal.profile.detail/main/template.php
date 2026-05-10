<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

CJSCore::Init(['date']);

$arExtensions = ['validate', 'order', 'uniform', 'phone_input', 'phone_mask'];
if (Tsolution::GetFrontParametrValue('USE_INTL_PHONE') === 'Y') {
	$arExtensions[] = 'intl_phone_input';
}
TSolution\Extensions::init($arExtensions);

$svgIconsSprite = $this->__folder.'/images/svg/icons.svg';

$this->SetViewTarget('more_text_title');
	?><span class="profile__person-type element-count color_999"><?=$arResult['PERSON_TYPE']['NAME']?></span><?
$this->EndViewTarget();
?>
<div class="personal__block personal__block--profile">
	<?if ($arResult['ERROR_MESSAGE']):?>
		<div class="alert alert-danger"><?=implode('<br />', (array)$arResult['ERROR_MESSAGE'])?></div>
	<?endif;?>

	<?if ($arResult['ID']):?>
		<?
		$formId = 'sale-profile-detail-form--'.$arResult['ID'];
		?>
		<div class="form">
			<div class="personal__top-form bordered outer-rounded-x p p--32">
				<span class="font_13 secondary-color"><?=Loc::getMessage('SPPD_TPL_TITLE')?></span>
				<h4 class="mt mt--4"><?=$arResult['NAME']?></h4>

				<form id="<?=$formId?>" method="post" class="sale-profile-detail-form mt mt--32" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
					<div class="form-group pb pb--12 color_dark<?=(strlen($arResult['NAME']) ? ' input-filed' : '')?>">
						<?
						$key = 'sale-personal-profile-detail-name--'.$arResult['ID'];
						?>
						<label class="font_13" for="<?=$key?>"><?=Loc::getMessage('SALE_TPL_PNAME')?>&nbsp;<span class="required-star">*</span></label>

						<div class="input">
							<input class="form-control" type="text" name="NAME" maxlength="50" id="<?=$key?>" value="<?=$arResult['NAME']?>" required />
						</div>
					</div>
					<!-- <div class="form-body form-body--grid"> -->
						<?=bitrix_sessid_post()?>
						<input type="hidden" name="ID" value="<?=$arResult['ID']?>">
						<?foreach ($arResult['ORDER_PROPS'] as $block):?>
							<?if (!empty($block['PROPS'])):?>
								<h6 class="mb mb--20 mt mt--12"><?=$block['NAME']?></h6>
								<div class="form-body form-body--grid">
									<?foreach($block['PROPS'] as $property):?>
										<?
										$key = 'sppd-property-'.$arResult['ID'].'_'.$property['ID'];
										$name = 'ORDER_PROP_'.$property['ID'];
										$currentValue = $arResult['ORDER_PROPS_VALUES'][$name];
										$bMultiple = $property['MULTIPLE'] === 'Y';
										$bRequired = $property['REQUIED'] === 'Y';
										?>
										<div class="form-group sale-personal-profile-detail-property-<?=mb_strtolower($property['TYPE'])?><?=($bMultiple ? ' grid-column-start--1' : '')?>">
											<?if ($property['TYPE'] !== 'CHECKBOX'):?>
												<label class="font_13 color_dark" for="<?=$key?>">
													<?=$property['NAME']?>
													<?if ($bRequired):?>
														&nbsp;<span class="required-star">*</span>
													<?endif;?>
												</label>
											<?endif;?>

											<div class="input">
												<?
												if ($property['TYPE'] == 'CHECKBOX') {
													?>
													<?// hidden value for N?>
													<input type="hidden" name="<?=$name?>" value="" />

													<input
														class="sale-personal-profile-detail-form-checkbox form-checkbox__input"
														id="<?=$key?>"
														type="checkbox"
														name="<?=$name?>"
														value="Y"
														<?if ($currentValue == 'Y' || !isset($currentValue) && $property['DEFAULT_VALUE'] == 'Y') echo ' checked';?>/>

													<label for="<?=$key?>" class="form-checkbox__label">
														<?=$property['NAME']?>
														<?if ($bRequired):?>
															&nbsp;<span class="required-star">*</span>
														<?endif;?>
														<span class="form-checkbox__box form-box"></span>
													</label>
													<?
												}
												elseif ($property['TYPE'] == 'TEXT') {
													$bEmail = $property['IS_EMAIL'] !== 'N';
													$bPhone = !$bEmail && ($property['CODE'] === 'PHONE');

													if ($bMultiple) {
														if (empty($currentValue) || !is_array($currentValue)) {
															$currentValue = array('');
														}

														foreach ($currentValue as $elementValue) {
															?>
															<input
																class="form-control<?=($bPhone ? ' phone' : '')?>"
																type="<?=($bEmail ? 'email' : 'text')?>" name="<?=$name?>[]"
																maxlength="50"
																id="<?=$key?>"
																value="<?=$elementValue?>"
																<?=($bRequired ? 'required' : '')?> />
															<?
														}
														?>
														<?if ($bMultiple):?>
															<div class="add_file color-theme input-add-multiple" data-add-type=<?=$property['TYPE']?>
															data-add-name="<?=$name?>[]"><span><?=Loc::getMessage('SPPD_TPL_ADD')?></span></div>
														<?endif;?>
														<?
													}
													else {
														?>
														<input
															class="form-control<?=($bPhone ? ' phone' : '')?>"
															type="<?=($bEmail ? 'email' : 'text')?>" name="<?=$name?>"
															maxlength="50"
															id="<?=$key?>"
															value="<?=$currentValue?>"
															<?=($bRequired ? 'required' : '')?> />
														<?
													}
												}
												elseif ($property['TYPE'] == 'SELECT') {
													?>
													<select
														class="form-control"
														name="<?=$name?>"
														id="<?=$key?>"
														size="<?=(intval($property['SIZE1']) > 0 ? $property['SIZE1'] : 1)?>"
														<?=($bRequired ? 'required' : '')?> >
															<?
															foreach ($property['VALUES'] as $value) {
																$bSelected = $value['VALUE'] == $currentValue || !isset($currentValue) && $value['VALUE']==$property['DEFAULT_VALUE'];
																?>
																<option value="<?= $value['VALUE']?>"<?=($bSelected ? ' selected' : '')?>><?= $value['NAME']?></option>
																<?
															}
															?>
													</select>
													<?
												}
												elseif ($property['TYPE'] == 'MULTISELECT') {
													$arDefVal = unserialize($property['~DEFAULT_VALUE']);
													?>
													<select
														class="form-control"
														id="<?=$key?>"
														multiple name="<?=$name?>[]"
														size="<?=(intval($property['SIZE1']) > 0 ? $property['SIZE1'] : 5)?>"
														<?=($bRequired ? 'required' : '')?> >
															<?
															foreach($property['VALUES'] as $value) {
																$bSelected = in_array($value['VALUE'], $currentValue) || !isset($currentValue) && in_array($value['VALUE'], $arDefVal);
																?>
																<option value="<?=$value['VALUE']?>"<?=($bSelected ? ' selected' : '')?>><?=$value['NAME']?></option>
																<?
															}
															?>
													</select>
													<?
												}
												elseif ($property['TYPE'] == 'TEXTAREA') {
													?>
													<textarea class="form-control" id="<?=$key?>" rows="<?echo ((int)($property['SIZE2'])>0)?$property['SIZE2']:4; ?>" cols="<?echo ((int)($property['SIZE1'])>0)?$property['SIZE1']:40; ?>" name="<?=$name?>" <?=($bRequired ? ' required' : '')?>><?=(isset($currentValue)) ? $currentValue : $property['DEFAULT_VALUE']?></textarea>
													<?
												}
												elseif ($property['TYPE'] == 'LOCATION') {
													$locationTemplate = ($arParams['USE_AJAX_LOCATIONS'] !== 'Y') ? 'popup' : '';
													$locationClassName = 'location-block-wrapper';
													if ($arParams['USE_AJAX_LOCATIONS'] === 'Y') {
														$locationClassName .= ' location-block-wrapper-delimeter';
													}
													if ($bMultiple) {
														if (empty($currentValue) || !is_array($currentValue)) {
															$currentValue = array($property['DEFAULT_VALUE']);
														}

														foreach ($currentValue as $code => $elementValue) {
															$locationValue = intval($elementValue) ? $elementValue : $property['DEFAULT_VALUE'];
															CSaleLocation::proxySaleAjaxLocationsComponent(
																array(
																	'ID' => 'propertyLocation'.$name.'['.$code.']',
																	'AJAX_CALL' => 'N',
																	'CITY_OUT_LOCATION' => 'Y',
																	'COUNTRY_INPUT_NAME' => $name.'_COUNTRY',
																	'CITY_INPUT_NAME' => $name.'['.$code.']',
																	'LOCATION_VALUE' => $locationValue,
																),
																array(
																),
																$locationTemplate,
																true,
																$locationClassName
															);
														}
														?>
														<span class="btn-themes btn-default btn-md btn input-add-multiple"
															data-add-type=<?=$property['TYPE']?>
															data-add-name="<?=$name?>"
															data-add-last-key="<?=$code?>"
															data-add-template="<?=$locationTemplate?>"><?=Loc::getMessage('SPPD_TPL_ADD')?></span>
														<?
													}
													else {
														$locationValue = (int)($currentValue) ? (int)$currentValue : $property['DEFAULT_VALUE'];

														CSaleLocation::proxySaleAjaxLocationsComponent(
															array(
																'AJAX_CALL' => 'N',
																'CITY_OUT_LOCATION' => 'Y',
																'COUNTRY_INPUT_NAME' => $name.'_COUNTRY',
																'CITY_INPUT_NAME' => $name,
																'LOCATION_VALUE' => $locationValue,
															),
															array(
															),
															$locationTemplate,
															true,
															'location-block-wrapper'
														);
													}
												}
												elseif ($property['TYPE'] == 'RADIO') {
													foreach($property['VALUES'] as $value) {
														$bChecked = $value['VALUE'] == $currentValue || !isset($currentValue) && $value['VALUE'] == $property['DEFAULT_VALUE'];
														?>
														<div class="form-radiobox">
															<input id="<?=$key.$value['VALUE']?>" type="radio" class="form-radiobox__input" name="<?=$name?>" value="<?=$value['VALUE']?>" <?=($bChecked ? ' checked' : '')?>>
															<label for="<?=$key.$value['VALUE']?>" class="form-radiobox__label outer-rounded-x">
																<span><?=$value['NAME']?></span>
																<span class="form-radiobox__box"></span>
															</label>
														</div>
														<?
													}
												}
												elseif ($property['TYPE'] == 'FILE') {
													$multiple = $bMultiple ? 'multiple' : '';
													$currentValue = is_array($currentValue) ? $currentValue : array($currentValue);
													$profileFiles = array_diff($currentValue, [false, null, '']);
													if (count($profileFiles) > 0) {
														foreach ($profileFiles as $file) {
															?>
															<input type="file" name="<?=$name?>[]" data-file-id="<?=$file['ID']?>" data-file-name="<?=htmlspecialcharsbx(basename($file['ORIGINAL_NAME'] ?: $file['FILE_NAME']))?>" data-file-href="<?=CFile::GetFileSRC($file)?>" />
															<?
														}
													}
													?>

													<?if ($bMultiple || !$profileFiles):?>
														<input type="file" name="<?=$name?>[]" <?=$multiple.($bRequired ? ' required' : '')?>/>
													<?endif;?>

													<?if ($bMultiple):?>
														<div class="add_file color-theme input-add-multiple" data-add-type=<?=$property['TYPE']?>
															data-add-name="<?=$name?>[]"><span><?=Loc::getMessage('JS_FILE_ADD')?></span>
														</div>
													<?endif;?>
													<?
												}
												elseif ($property['TYPE'] === 'DATE') {
													if ($bMultiple) {
														$name .= '[]';
													}

													$bTime = isset($property['SETTINGS']) && isset($property['SETTINGS']['TIME']) && $property['SETTINGS']['TIME'] === 'Y';

													$currentValue = is_array($currentValue) ? $currentValue : [$currentValue];
													?>
													<div class="sale-personal-profile-detail-form-date">
														<?
														foreach ($currentValue as $dataInputValue) {
															?>
															<input class="form-control <?=($bTime ? 'datetime' : 'date')?>" type="text" name="<?=$name?>" maxlength="50" value="<?=$dataInputValue?>"<?=($bRequired ? ' required' : '')?> />
															<?
														}
														?>
														<?if ($bMultiple):?>
															<div class="add_file color-theme input-add-multiple" data-add-type=<?=$property['TYPE']?>
															data-add-name="<?=$name?>"><span><?=Loc::getMessage('SPPD_TPL_ADD')?></span></div>
														<?endif;?>
													</div>
													<?
												}
												?>
											</div>

											<?if (strlen($property['DESCRIPTION'])):?>
												<div class="text_block font_13">
													<?=$property['DESCRIPTION']?>
												</div>
											<?endif;?>
										</div>
									<?endforeach;?>
								</div>
							<?endif;?>
						<?endforeach;?>

						<script>
						BX.message({
							SPPD_TPL_DELETE: '<?=Loc::getMessage('SPPD_TPL_DELETE')?>',
							SPPD_TPL_NOT_DELETE: '<?=Loc::getMessage('SPPD_TPL_NOT_DELETE')?>',
							SPPD_TPL_DELETE_CONFIRM_TITLE: '<?=Loc::getMessage('SPPD_TPL_DELETE_CONFIRM_TITLE')?>',
							SPPD_TPL_DELETE_CONFIRM_DESC: '<?=Loc::getMessage('SPPD_TPL_DELETE_CONFIRM_DESC')?>',
							SPPD_TPL_FILE_DEFAULT: '<?=Loc::getMessage('SPPD_TPL_FILE_DEFAULT')?>',
							SPPD_TPL_UPLOAD_CLEAR: '<?=Loc::getMessage('SPPD_TPL_UPLOAD_CLEAR')?>',
							SPPD_TPL_FILE_COUNT: '<?=Loc::getMessage('SPPD_TPL_FILE_COUNT')?>',
						});
						
						new BX.Sale.PersonalProfileComponent.PersonalProfileDetail(
							'form#<?=$formId?>',
							<?=CUtil::PhpToJSObject([
								'id' => $arResult['ID'],
								'personTypeId' => $arResult['PERSON_TYPE_ID'],
								'ajaxUrl' => CUtil::JSEscape($this->__component->GetPath().'/ajax.php'),
								'listUrl' => CUtil::JSEscape($arParams['PATH_TO_LIST']),
								'deleteUrl' => CUtil::JSEscape($arResult['URL_TO_DETELE']),
							])?>
						);

						$(document).ready(function() {
							if ($('.back-url').length) {
								let href = $('.back-url').attr('href');
								if (href.indexOf('#pt') == -1) {
									$('.back-url').attr('href',  href + '#pt<?=$arResult['PERSON_TYPE_ID']?>');
								}
							}

							if (
								typeof appAspro === 'object' &&
								appAspro &&
								appAspro.phone
							) {
								appAspro.phone.init($('form#<?=$formId?> input.phone'));
							}
						});
						</script>
					<!-- </div> -->

					<div class="form-footer mt mt--12">
						<div class="form-footer__buttons">
							<button type="submit" class="btn btn-default btn-lg" name="apply" value="apply"><?=Loc::getMessage('SPPD_TPL_SAVE')?></button>
							<button type="button" class="btn btn-default btn-lg btn-transparent" name="cancel" value="cancel"><?=Loc::getMessage('SPPD_TPL_CANCEL')?></button>
							<a href="" class="no-decoration js-profile-delete font_14 stroke-dark-light-block">
								<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#delete-20-20', '', ['WIDTH' => 20, 'HEIGHT' => 20]);?>
								<?=Loc::getMessage('SPPD_TPL_DELETE')?>
							</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	<?endif;?>
</div>
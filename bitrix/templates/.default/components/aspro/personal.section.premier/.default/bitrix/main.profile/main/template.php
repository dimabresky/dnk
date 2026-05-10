<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);
global $arTheme;

$arExtensions = ['tabs.history', 'validate', 'phone_input', 'phone_mask', 'eye.password'];
if (Tsolution::GetFrontParametrValue('USE_INTL_PHONE') === 'Y') {
	$arExtensions[] = 'intl_phone_input';
}
if ($arResult['SHOW_SMS_FIELD']) {
	CJSCore::Init('phone_auth');
	$arExtensions = 'phonecode';
}
TSolution\Extensions::init($arExtensions);
$showLicenses = TSolution::GetFrontParametrValue('SHOW_LICENCE') == "Y";

// get phone auth params
list($bPhoneAuthSupported, $bPhoneAuthShow, $bPhoneAuthRequired, $bPhoneAuthUse) = TSolution\PhoneAuth::getOptions();
if ($bPhoneAuthShow) {
	$userPhoneAuth = \Bitrix\Main\UserPhoneAuthTable::getRowById($arResult['ID']);
}

$bChangePassword = ($_REQUEST['type'] ?? '') === 'pass';
?>
<div class="personal__block personal__block--private">
	<div class="form<?=(($arResult['SHOW_SMS_FIELD'] && !$arResult['strProfileError']) ? ' form--send-sms' : '')?>">
		<?if(
			$arResult['SHOW_SMS_FIELD'] &&
			!$arResult['strProfileError']
		):?>
			<div class="personal__top-form bordered outer-rounded-x p p--32">
				<a href="<?=$arResult['FORM_TARGET']?>" rel="nofollow" class="backlink dark_link color_999"><?=Loc::getMessage('SPS_AUTH_PHONE_BACKLINK')?></a>

				<form id="profile-form" method="post" name="form1" class="main-form" action="<?=$arResult['FORM_TARGET']?>" enctype="multipart/form-data">
					<?=$arResult['BX_SESSION_CHECK']?>
					<input type="hidden" name="lang" value="<?=LANG?>" />
					<input type="hidden" name="ID" value=<?=$arResult['ID']?> />
					<input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult['SIGNED_DATA'])?>" />

					<div class="form-body form-body--grid">
						<div class="form-group fill-animate phone_code">
							<div class="alert alert-success"><?=Loc::getMessage('main_profile_code_sent', ['#PHONE_NUMBER#' => $arResult['arUser']['PHONE_NUMBER']])?></div>

							<label class="font_13 color_dark" for="input_SMS_CODE"><span><?=Loc::getMessage('main_profile_code')?> <span class="required-star">*</span></span></label>
							<div class="input">
								<input id="input_SMS_CODE" class="form-control required" size="30" type="text" name="SMS_CODE" value="<?=htmlspecialcharsbx($arResult['SMS_CODE'])?>" autocomplete="off" />
							</div>
						</div>
					</div>
                    <?if($showLicenses):?>
                        <?
                            TSolution\Functions::showBlockHtml([
                                'FILE' => 'consent/userconsent.php',
                                'PARAMS' => [
                                    'OPTION_CODE' => 'AGREEMENT_SUBSCRIBE',
                                    'SUBMIT_TEXT' => Loc::getMessage('main_profile_send'),
                                    'REPLACE_FIELDS' => [],
                                    'INPUT_NAME' => "licenses_popup",
                                    'INPUT_ID' => "licenses_popup",
                                ]
                            ]);
                        ?>
                    <?endif?>
					<div class="form-footer">
						<button class="btn btn-default btn-lg hidden" type="submit" name="code_submit_button" value="Y"><span><?=Loc::getMessage('main_profile_send')?></span></button>

						<div class="bx_profile_send-sms">
							<div id="bx_profile_error" style="display:none"><?ShowError('error')?></div>
							<div id="bx_profile_resend" class="color_999"></div>
						</div>
					</div>
				</form>

				<script>
				document.form1.SMS_CODE.focus();

				$(document).ready(function(){
					$("#profile-form").validate({
						submitHandler: function(form) {
							var $form = $(form);
							if ($form.valid()) {
								$form.closest('.form').addClass('sending');
								return true;
							}
						}
					});

					$("#profile-form .phone_code input[type=text]").phonecode(
						<?=CUtil::PhpToJSObject(
							[
								'USER_ID' => $arResult['ID'],
								'USER_PHONE_NUMBER' => $arResult['arUser']['PHONE_NUMBER'],
							]
						)?>,
						function(input, data, response) {
							if (
								typeof response !== 'undefined' &&
								response === 'true'
							) {
								let $form = $(input).closest('form');

								if (
									$form.length &&
									!$form.find('button[type=submit].loadings').length
								) {
									$form.find('button[type=submit]').trigger('click');
								}
							}
						}
					);
				});

				new BX.PhoneAuth({
					containerId: 'bx_profile_resend',
					errorContainerId: 'bx_profile_error',
					interval: <?=$arResult['PHONE_CODE_RESEND_INTERVAL']?>,
					data: <?=CUtil::PhpToJSObject(['signedData' => $arResult['SIGNED_DATA']])?>,
					onError: function(response) {
						var errorDiv = BX('bx_profile_error');
						var errorNode = BX.findChildByClassName(errorDiv, 'errortext');
						errorNode.innerHTML = '';

						for (var i = 0; i < response.errors.length; i++) {
							errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
						}

						errorDiv.style.display = '';
					}
				});
				</script>
			</div>
		<?else:?>
			<div class="personal__top-form bordered outer-rounded-x p p--32">
				<h4><?=Loc::getMessage('PROFILE_TITLE');?></h4>

				<?if (!$bChangePassword):?>
					<?if($arResult['strProfileError']):?>
						<div class="alert alert-danger"><?=$arResult['strProfileError']?></div>
					<?endif;?>
				<?endif;?>

				<form id="profile-form" method="post" name="form1" class="main-form mt mt--32" action="<?=$arResult['FORM_TARGET']?>" enctype="multipart/form-data">
					<?=$arResult['BX_SESSION_CHECK']?>
					<input type="hidden" name="LOGIN" maxlength="50" value="<?=$arResult['arUser']['LOGIN']?>" />
					<input type="hidden" name="lang" value="<?=LANG?>" />
					<input type="hidden" name="ID" value=<?=$arResult['ID']?> />

					<div class="form-body form-body--grid">
						<?if($arTheme['CABINET']['DEPENDENT_PARAMS']['LOGIN_EQUAL_EMAIL']['VALUE'] != 'Y'):?>
							<div class="form-group <?=($arResult['arUser']['LOGIN'] ? 'input-filed' : '');?>">
								<label for="LOGIN" class="font_13 color_dark"><span><?=Loc::getMessage('PERSONAL_LOGIN')?>&nbsp;<span class="required-star">*</span></span></label>
								<div class="input">
									<input required type="text" name="LOGIN" id="LOGIN" maxlength="50" class="form-control" value="<?=$arResult['arUser']['LOGIN']?>" />
								</div>
							</div>
						<?endif;?>

						<?if($arTheme['CABINET']['DEPENDENT_PARAMS']['PERSONAL_ONEFIO']['VALUE'] != 'N'):?>
							<?
							$strName = trim(
								implode(
									' ',
									[
										$arResult['arUser']['LAST_NAME'],
										$arResult['arUser']['NAME'],
										$arResult['arUser']['SECOND_NAME']
									]
								)
							);
							?>
							<div class="form-group <?=($strName ? 'input-filed' : '');?>">
								<label for="NAME" class="font_13 color_dark"><span><?=Loc::getMessage('PERSONAL_FIO')?>&nbsp;<span class="required-star">*</span></span></label>
								<div class="input">
									<input required type="text" class="form-control" name="NAME" id="NAME" maxlength="50" value="<?=$strName?>" />
								</div>
							</div>
						<?else:?>
							<div class="form-group <?=($arResult['arUser']['LAST_NAME'] ? 'input-filed' : '');?>">
								<label for="LAST_NAME" class="font_13 color_dark"><span><?=Loc::getMessage('PERSONAL_LASTNAME')?></span></label>
								<div class="input">
									<input type="text" class="form-control" name="LAST_NAME" id="LAST_NAME" maxlength="50" value="<?=$arResult['arUser']['LAST_NAME'];?>" />
								</div>
							</div>

							<div class="form-group <?=($arResult['arUser']['NAME'] ? 'input-filed' : '');?>">
								<label for="NAME" class="font_13 color_dark"><?=Loc::getMessage('PERSONAL_NAME')?>&nbsp;<span class="required-star">*</span></span></label>
								<div class="input">
									<input required type="text" class="form-control" name="NAME" id="NAME" maxlength="50" value="<?=$arResult['arUser']['NAME'];?>" />
								</div>
							</div>

							<div class="form-group <?=($arResult['arUser']['SECOND_NAME'] ? 'input-filed' : '');?>">
								<label for="SECOND_NAME" class="font_13 color_dark"><?=Loc::getMessage('PERSONAL_FATHERNAME')?></span></label>
								<div class="input">
									<input type="text" class="form-control" name="SECOND_NAME" id="SECOND_NAME" maxlength="50" value="<?=$arResult['arUser']['SECOND_NAME'];?>" />
								</div>
							</div>
						<?endif;?>

						<div class="form-group <?=($arResult['arUser']['EMAIL'] ? 'input-filed' : '');?>">
							<label for="EMAIL" class="font_13 color_dark"><span><?=Loc::getMessage('PERSONAL_EMAIL')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="input">
								<input required type="text" name="EMAIL" id="EMAIL" maxlength="50" class="form-control" value="<?=$arResult['arUser']['EMAIL']?>" />
							</div>
							<?if(
								$arTheme['CABINET']['DEPENDENT_PARAMS']['LOGIN_EQUAL_EMAIL']['VALUE'] == "Y" &&
								$arResult['arUser']['EMAIL'] === $arResult['arUser']['LOGIN']
							):?>
								<div class="secondary-color font_13 mt mt--4"><?=Loc::getMessage('PERSONAL_EMAIL_DESCRIPTION');?></div>
							<?else:?>
								<div class="secondary-color font_13 mt mt--4"><?=Loc::getMessage('PERSONAL_EMAIL_SHORT_DESCRIPTION');?></div>
							<?endif;?>
						</div>

						<div class="form-group form-group--phone <?=(strlen($arResult['arUser']['PERSONAL_PHONE']) ? 'input-filed' : '');?>">
							<label for="PERSONAL_PHONE" class="font_13 color_dark"><span><?=Loc::getMessage('PERSONAL_PHONE')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="input">
								<input required type="text" name="PERSONAL_PHONE" id="PERSONAL_PHONE" class="form-control phone" maxlength="255" value="<?=$arResult['arUser']['PERSONAL_PHONE']?>" />
							</div>
							<div class="secondary-color font_13 mt mt--4"><?=Loc::getMessage('PERSONAL_PHONE_DESCRIPTION')?></div>
						</div>

						<?if($arResult['PHONE_REGISTRATION']):?>
							<?$bConfirmed = $userPhoneAuth['CONFIRMED'] == 'Y';?>
							<div class="form-group form-group--phone<?=($bConfirmed && strlen($arResult['arUser']['PHONE_NUMBER']) ? ' form-group--phone-confirmed' : '')?><?=(strlen($arResult['arUser']['PHONE_NUMBER']) ? ' input-filed' : ' form-group--phone-empty')?>">
								<label for="PHONE_NUMBER" class="font_13 color_dark">
									<span><?=Loc::getMessage("main_profile_phone_number")?> <?=($arResult['PHONE_REQUIRED'] ? '<span class="required-star">*</span>' : '')?></span>
									<span class="phone-confirm personal-color--green"><?=Loc::getMessage('SPS_AUTH_PHONE_CONFIRMED')?></span>
									<span class="phone-confirm personal-color--red"><?=Loc::getMessage('SPS_AUTH_PHONE_NOTCONFIRMED')?></span>
								</label>
								<div class="input">
									<input id="PHONE_NUMBER" <?=($arResult['PHONE_REQUIRED'] ? 'required' : '')?> type="tel" name="PHONE_NUMBER" class="form-control phone" maxlength="255" value="<?=$arResult['arUser']['PHONE_NUMBER']?>" />
								</div>
								<div class="secondary-color font_13 mt mt--4"><?=Loc::getMessage('PHONE_NUMBER_DESCRIPTION'.($bPhoneAuthUse ? '_WITH_AUTH' : ''))?></div>
							</div>

							<script>
							$(document).ready(function() {
								BX.message({
									MAIN_SAVE_TITLE: '<?=Loc::getMessage('MAIN_SAVE_TITLE')?>',
									MAIN_SAVE_AND_CONFIRM_TITLE: '<?=Loc::getMessage('MAIN_SAVE_AND_CONFIRM_TITLE')?>',
									MAIN_CONFIRM_TITLE: '<?=Loc::getMessage('MAIN_CONFIRM_TITLE')?>',
								});

								let confirmedPhone = '<?=($bConfirmed ? preg_replace('/[^\d]/', '', $arResult['arUser']['PHONE_NUMBER']) : '')?>';

								$('#PHONE_NUMBER').on('change keyup paste', function() {
									let phone = $(this).val().trim();
									phone = phone.replace(/[^\d]/g, '');

									let buttonText = BX.message('MAIN_SAVE_TITLE');

									if (phone.length) {
										$(this).closest('.form-group').removeClass('form-group--phone-empty')

										if (phone == confirmedPhone) {
											$(this).closest('.form-group').addClass('form-group--phone-confirmed');
										}
										else {
											$(this).closest('.form-group').removeClass('form-group--phone-confirmed');

											if ($(this).valid()) {
												buttonText = BX.message('MAIN_SAVE_AND_CONFIRM_TITLE');
											}
										}
									}
									else {
										$(this).closest('.form-group').addClass('form-group--phone-empty');
									}

									$(this).closest('form').find('.form-footer button[name=save]').text(buttonText);
								});

								if ($('#PHONE_NUMBER').length) {
									if (
										!$('#PHONE_NUMBER').closest('.form-group').hasClass('form-group--phone-empty') &&
										!$('#PHONE_NUMBER').closest('.form-group').hasClass('form-group--phone-confirmed')
									) {
										setTimeout(() => {
											if ($('#PHONE_NUMBER').valid()) {
												buttonText = BX.message('MAIN_CONFIRM_TITLE');
												$('#PHONE_NUMBER').closest('form').find('.form-footer button[name=save]').text(buttonText).prop('disabled', false);
											}
										}, 100);
									}
								}
							});
							</script>
						<?endif;?>
					</div>
                    <?$submitText = $arResult['ID'] > 0 ?
                                Loc::getMessage('MAIN_SAVE_TITLE') : Loc::getMessage('MAIN_ADD_TITLE');?>
                    <?if($showLicenses):?>
                        <?
                            TSolution\Functions::showBlockHtml([
                                'FILE' => 'consent/userconsent.php',
                                'PARAMS' => [
                                    'OPTION_CODE' => 'AGREEMENT_SUBSCRIBE',
                                    'SUBMIT_TEXT' => $submitText,
                                    'REPLACE_FIELDS' => [],
                                    'INPUT_NAME' => "licenses_popup",
                                    'INPUT_ID' => "licenses_popup",
                                ]
                            ]);
                        ?>
                    <?endif?>
					<div class="form-footer form-footer--has-message mt mt--32">
						<button class="btn btn-default btn-lg" type="submit" name="save" value="save" disabled><span><?=$submitText;?></span></button>
						<?if (!$bChangePassword):?>
							<?if($arResult['DATA_SAVED'] == 'Y'):?>
								<div class="form-footer__message font_13 color_999"><?=Loc::getMessage('PROFILE_DATA_SAVED')?></div>
							<?endif;?>
						<?endif;?>
					</div>

					<script>
					$(document).ready(function(){
						$('#profile-form input').on('change keyup paste', function() {
							$(this).closest('form').find('button[type="submit"]').prop('disabled', false);
						});

						$('#profile-form').validate({
							rules:{
								EMAIL: {
									email: true
								}
							},
							submitHandler: function(form) {
								var $form = $(form);
								if ($form.valid()) {
									$form.closest('.form').addClass('sending');
									return true;
								}
							}
						});

						if (
							typeof appAspro === 'object' &&
							appAspro &&
							appAspro.phone
						) {
							appAspro.phone.init($('.personal__block--private input.phone'));
						}
					});
					</script>
				</form>
			</div>

			<?// hide block for prevent default brouser scroll to hash #change-password ?>
			<div class="personal__top-form bordered outer-rounded-x hidden p p--32" id="change-password">
				<h4><?=Loc::getMessage('CHANGE_PASSWORD');?></h4>

				<?if ($bChangePassword):?>
					<?if($arResult['strProfileError']):?>
						<div class="alert alert-danger"><?=$arResult['strProfileError']?></div>
					<?endif;?>
				<?endif;?>

				<form id="pass-form" method="post" name="form1" class="pass-form" action="<?=$arResult['FORM_TARGET']?>#change-password" enctype="multipart/form-data">
					<?=$arResult["BX_SESSION_CHECK"]?>
					<input type="hidden" name="LOGIN" maxlength="50" value="<?=$arResult['arUser']['LOGIN']?>" />
					<input type="hidden" name="EMAIL" maxlength="50" placeholder="name@company.ru" value="<?=$arResult['arUser']['EMAIL']?>" />
					<input type="hidden" name="lang" value="<?=LANG?>" />
					<input type="hidden" name="ID" value="<?=$arResult['ID']?>" />
					<input type="hidden" name="type" value="pass" />

					<div class="form-body form-body--grid">
						<div class="form-group">
							<label for="NEW_PASSWORD" class="font_13 color_dark"><span><?=Loc::getMessage('NEW_PASSWORD')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="input">
								<input type="password" name="NEW_PASSWORD" id="NEW_PASSWORD" maxlength="50" class="form-control password" value="" />
							</div>
							<div class="secondary-color font_13 mt mt--4"><?=Loc::getMessage('PERSONAL_PASWORD_TEXT');?></div>
						</div>

						<div class="form-group"></div>

						<div class="form-group">
							<label for="NEW_PASSWORD_CONFIRM" class="font_13 color_dark"><span><?=Loc::getMessage('NEW_PASSWORD_CONFIRM')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="input">
								<input type="password" name="NEW_PASSWORD_CONFIRM" id="NEW_PASSWORD_CONFIRM" maxlength="50" class="form-control confirm_password" value="" />
							</div>
						</div>

						<div class="form-group"></div>
					</div>

					<div class="form-footer form-footer--has-message mt mt--32">
						<button class="btn btn-default btn-lg" type="submit" name="save" value="<?=(($arResult['ID']>0) ? Loc::getMessage('SAVE') : Loc::getMessage('ADD'))?>" disabled><span><?=(($arResult['ID']>0) ? Loc::getMessage('SAVE') : Loc::getMessage('ADD'))?></span></button>

						<?if ($bChangePassword):?>
							<?if($arResult['DATA_SAVED'] == 'Y'):?>
								<div class="form-footer__message font_13 color_999"><?=Loc::getMessage('PROFILE_DATA_SAVED')?></div>
							<?endif;?>
						<?endif;?>
					</div>

					<script>
					$(document).ready(function() {
						$('#change-password').removeClass('hidden');
						if (location.hash == '#change-password') {
							scrollToBlock(location.hash);
						}

						$('#pass-form input').on('change keyup paste', function() {
							$(this).closest('form').find('button[type="submit"]').prop('disabled', false);
						});

						$('#pass-form').validate({
							rules: {
								NEW_PASSWORD_CONFIRM: {
									equalTo: '#NEW_PASSWORD'
								}
							},
							messages: {
								NEW_PASSWORD_CONFIRM: {
									equalTo: '<?=Loc::getMessage('PASSWORDS_DOES_NOT_MATCH')?>'
								}
							},
							submitHandler: function(form) {
								var $form = $(form);
								if ($form.valid()) {
									$form.closest('.form').addClass('sending');
									return true;
								}
							}
						});

						<?if (($arParams['SHOW_CHANGE_PASSWORD_FORM'] ?? 'N') === 'Y'):?>
							scrollToBlock('#change-password');
						<?endif;?>
					});
					</script>
				</form>
			</div>

			<?if($arResult['SOCSERV_ENABLED']):?>
				<?$APPLICATION->IncludeComponent(
					"bitrix:socserv.auth.split",
					"main",
					array(
						"SUFFIX" => "form",
						"SHOW_PROFILES" => "Y",
						"ALLOW_DELETE" => "Y"
					),
					$component->__parent,
					array("HIDE_ICONS" => "Y")
				);?>
			<?endif;?>
		<?endif;?>
	</div>
</div>

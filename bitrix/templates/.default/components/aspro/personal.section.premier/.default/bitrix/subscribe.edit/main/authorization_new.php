<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<?if (
	$arResult['ALLOW_ANONYMOUS'] == 'Y' &&
	$_REQUEST['authorize'] <> 'YES' &&
	$_REQUEST['register'] <> 'YES'
):?>
	<div class="personal__top-form bordered outer-rounded-x p p--32">
		<h4><?=Loc::getMessage('subscr_title_auth2')?></h4>

		<div><?=Loc::getMessage('adm_auth1')?> <a href="<?=SITE_DIR?>personal/?backurl=<?=SITE_DIR?>personal/subscribe/<?/*=$arResult["FORM_ACTION"]?>?authorize=YES&amp;sf_EMAIL=<?=$arResult['REQUEST']['EMAIL']?><?=$arResult['REQUEST']["RUBRICS_PARAM"]*/?>"><?=Loc::getMessage('adm_auth2')?></a>.</div>
		<?if($arResult['ALLOW_REGISTER'] == 'Y'):?>
			<div><?=Loc::getMessage('adm_reg1')?> <a href="<?=SITE_DIR?>personal/registration/?backurl=<?=SITE_DIR?>personal/subscribe/<?/*=$arResult["FORM_ACTION"]?>?register=YES&amp;sf_EMAIL=<?=$arResult['REQUEST']['EMAIL']?><?=$arResult['REQUEST']["RUBRICS_PARAM"]*/?>"><?=Loc::getMessage('adm_reg2')?></a>.</div>
		<?endif;?>
	</div>
<?elseif (
	$arResult['ALLOW_ANONYMOUS'] == 'N' ||
	$_REQUEST['authorize'] == 'YES' ||
	$_REQUEST['register'] == 'YES'
):?>
	<div class="personal__top-form bordered outer-rounded-x p p--32">
		<form id="subscribe-auth-form" action="<?=$arResult['FORM_ACTION']?>" method="post" class="form">
			<div class="form-header">
				<div class="text">
					<div class="title"><h4><?=Loc::getMessage('adm_auth_exist')?></h4></div>
					<div class="form_desc fornt_16"><?=($arResult['ALLOW_ANONYMOUS'] == 'Y' ? Loc::getMessage('subscr_auth_note') : Loc::getMessage('adm_must_auth'))?></div>
				</div>
			</div>

			<div class="form-body">
				<?=bitrix_sessid_post()?>

				<?$login = $arResult['REQUEST']['LOGIN'];?>
				<div class="form-group fill-animate <?=(strlen($login) ? 'input-filed' : '')?>">
					<label for="LOGIN" class="font_14"><span><?=Loc::getMessage('adm_auth_login')?>&nbsp;<span class="required-star">*</span></span></label>
					<div class="half-block">
						<div class="input">
							<input type="text" name="LOGIN" value="<?=$login?>" size="20" class="form-control" required />
						</div>
						<div></div>
					</div>
				</div>
								
				<?$pass = $arResult['REQUEST']['PASSWORD'];?>
				<div class="form-group fill-animate <?=(strlen($pass) ? 'input-filed' : '')?>">
					<label for="PASSWORD" class="font_14"><span><?=Loc::getMessage('adm_auth_pass')?>&nbsp;<span class="required-star">*</span></span></label>
					<div class="half-block">
						<div class="input eye-password">
							<input type="password" name="PASSWORD" size="20" value="<?=$pass?>" class="form-control password" required />
						</div>
						<div></div>
					</div>
				</div>
			</div>

			<div class="button-block form-footer">
				<div class="form-footer__buttons">
					<input type="submit" class="btn btn-default btn-lg" name="Save" value="<?=Loc::getMessage('adm_auth_butt')?>" />
				</div>
			</div>

			<?foreach ($arResult['RUBRICS'] as $itemID => $itemValue):?>
				<input type="hidden" name="RUB_ID[]" value="<?=$itemValue['ID']?>">
			<?endforeach;?>

			<input type="hidden" name="PostAction" value="<?=($arResult['ID'] > 0 ? 'Update' : 'Add')?>" />
			<input type="hidden" name="ID" value="<?=$arResult['SUBSCRIPTION']['ID']?>" />

			<?if ($_REQUEST['register'] == 'YES'):?>
				<input type="hidden" name="register" value="YES" />
			<?endif;?>

			<?if ($_REQUEST['authorize'] == 'YES'):?>
				<input type="hidden" name="authorize" value="YES" />
			<?endif;?>
		</form>

		<script>
		$(document).ready(function(){
			$('#subscribe-auth-form').validate({
				submitHandler: function(form) {
					var $form = $(form);
					if ($form.valid()) {
						$form.closest('.form').addClass('sending');
						return true;
					}
				}
			});
		});
		</script>
	</div>

	<?if ($arResult['ALLOW_REGISTER'] == 'Y'):
		?>
		<div class="personal__top-form bordered outer-rounded-x p p--32">
			<form id="subscribe-reg-form" action="<?=$arResult['FORM_ACTION']?>" method="post">
				<div class="form-header">
					<div class="text">
						<div class="title"><h4><?=Loc::getMessage('adm_reg_new')?></h4></div>
						<div class="form_desc fornt_16"><?=($arResult['ALLOW_ANONYMOUS'] == 'Y' ? Loc::getMessage('subscr_auth_note') : Loc::getMessage('adm_must_auth'))?></div>
					</div>
				</div>

				<div class="form-body">
					<?=bitrix_sessid_post()?>

					<div class="half-block">
						<?$newLogin = $arResult['REQUEST']['NEW_LOGIN'];?>
						<div class="form-group fill-animate <?=(strlen($newLogin) ? 'input-filed' : '')?>">
							<label for="NEW_LOGIN" class="font_14"><span><?=Loc::getMessage('adm_reg_login')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="input">
								<input type="text" name="NEW_LOGIN" value="<?=$newLogin?>" size="20" class="form-control" required />
							</div>
						</div>

						<?$email = $arResult['SUBSCRIPTION']['EMAIL'] != '' ? $arResult['SUBSCRIPTION']['EMAIL'] : $arResult['REQUEST']['EMAIL'];?>
						<div class="form-group fill-animate <?=(strlen($email) ? 'input-filed' : '')?>">
							<label for="EMAIL" class="font_14"><span><?=Loc::getMessage('subscr_email')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="input">
								<input type="text" name="EMAIL" value="<?=$email?>" size="30" maxlength="255" class="form-control email" required />
							</div>
						</div>
					</div>

					<?$newPass = $arResult['REQUEST']['NEW_PASSWORD'];?>
					<div class="form-group fill-animate <?=(strlen($newPass) ? 'input-filed' : '')?>">
						<label for="NEW_PASSWORD" class="font_14"><span><?=Loc::getMessage('adm_reg_pass')?>&nbsp;<span class="required-star">*</span></span></label>
						<div class="half-block">
							<div class="input eye-password">
								<input type="password" name="NEW_PASSWORD" size="20" value="<?=$newPass?>" class="form-control password" required />
							</div>
							<div></div>
						</div>
					</div>

					<?$confirmPass = $arResult['REQUEST']['CONFIRM_PASSWORD'];?>
					<div class="form-group fill-animate <?=(strlen($confirmPass) ? 'input-filed' : '')?>">
						<label for="CONFIRM_PASSWORD" class="font_14"><span><?=Loc::getMessage('adm_reg_pass_conf')?>&nbsp;<span class="required-star">*</span></span></label>
						<div class="half-block">
							<div class="input eye-password">
								<input type="password" name="CONFIRM_PASSWORD" size="20" value="<?=$confirmPass?>" class="form-control password" required />
							</div>
							<div></div>
						</div>
					</div>

					<?/* CAPTCHA */?>
					<?if (COption::GetOptionString('main', 'captcha_registration', 'N') == 'Y'):?>
						<?$capCode = $GLOBALS['APPLICATION']->CaptchaGetCode();?>

						<div class="captcha-row clearfix fill-animate">
							<label class="font_14"><span><?=Loc::getMessage('subscr_CAPTCHA_REGF_PROMT')?>&nbsp;<span class="required-star">*</span></span></label>
							<div class="captcha_image">
								<img data-src="" src="/bitrix/tools/captcha.php?captcha_sid=<?=htmlspecialcharsbx($capCode) ?>" class="captcha_img" />
								<input type="hidden" name="captcha_sid" class="captcha_sid" value="<?=htmlspecialcharsbx($capCode) ?>" />
								<div class="captcha_reload"></div>
								<span class="refresh"><a href="javascript:;" rel="nofollow"><?=Loc::getMessage('REFRESH')?></a></span>
							</div>
							<div class="captcha_input">
								<input type="text" class="inputtext form-control captcha" name="captcha_word" size="30" maxlength="50" value="" required />
							</div>
						</div>						
					<?endif;?>
				</div>

				<div class="button-block form-footer">
					<div class="form-footer__buttons">
						<input type="submit" class="btn btn-default btn-lg" name="Save" value="<?=Loc::getMessage('adm_reg_butt')?>" />
					</div>
				</div>

				<?foreach ($arResult['RUBRICS'] as $itemID => $itemValue):?>
					<input type="hidden" name="RUB_ID[]" value="<?=$itemValue['ID']?>">
				<?endforeach;?>

				<input type="hidden" name="PostAction" value="<?=($arResult['ID'] > 0 ? 'Update' : 'Add')?>" />
				<input type="hidden" name="ID" value="<?=$arResult['SUBSCRIPTION']['ID'];?>" />

				<?if ($_REQUEST['register'] == 'YES'):?>
					<input type="hidden" name="register" value="YES" />
				<?endif;?>

				<?if ($_REQUEST['authorize'] == 'YES'):?>
					<input type="hidden" name="authorize" value="YES" />
				<?endif;?>
			</form>

			<script>
			$(document).ready(function(){
				$('#subscribe-reg-form').validate({
					submitHandler: function(form) {
						var $form = $(form);
						if ($form.valid()) {
							$form.closest('.form').addClass('sending');
							return true;
						}
					}
				});
			});
			</script>
		</div>
	<?endif;?>
<?endif;?>

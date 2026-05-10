<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<div class="personal__top-form bordered outer-rounded-x p p--32">
	<h4 class="mb mb--32"><?=Loc::getMessage('subscr_title_confirm')?></h4>

	<form id="subscribe-confirm-form" action="<?=$arResult['FORM_ACTION']?>" method="get" class="form">
		<div class="form-body">
			<div class="form-group">
				<label for="CONFIRM_CODE" class="font_13 color_dark"><span><?=Loc::getMessage('subscr_conf_code')?>&nbsp;<span class="required-star">*</span></span></label>
				<div class="half-block">
					<div class="input">
						<input class="form-control" type="text" id="CONFIRM_CODE" name="CONFIRM_CODE" value="<?=$arResult['REQUEST']['CONFIRM_CODE']?>" size="20" required />
					</div>
					<div class="text_block font_13">
						<?=Loc::getMessage('subscr_conf_note1')?> <a title="<?=Loc::getMessage('adm_send_code')?>" href="<?=$arResult['FORM_ACTION']?>?ID=<?=$arResult['ID']?>&amp;action=sendcode&amp;<?=bitrix_sessid_get()?>"><?=Loc::getMessage('subscr_conf_note2')?></a>.
					</div>
				</div>
				<div class="text_block font_13">
					<?=Loc::getMessage('subscr_conf_date')?> <?=$arResult['SUBSCRIPTION']['DATE_CONFIRM']?>
				</div>
			</div>
		</div>

		<div class="form-footer mt mt--12">
			<div class="form-footer__buttons">
				<input type="submit" class="btn btn-default btn-lg btn-confirm" name="confirm" value="<?=Loc::getMessage('subscr_conf_button')?>" />
			</div>
		</div>

		<input type="hidden" name="ID" value="<?=$arResult['ID']?>" />
		<?=bitrix_sessid_post()?>
	</form>

	<script>
	$(document).ready(function(){
		$('#subscribe-confirm-form').validate({
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

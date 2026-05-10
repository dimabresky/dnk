<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<div class="personal__top-form bordered outer-rounded-x personal__top-form--status p p--32">
	<h4><?=Loc::getMessage('subscr_title_status')?></h4>

	<form id="subscribe-status-form" action="<?=$arResult['FORM_ACTION']?>" method="get" class="form mt mt--32">
		<div class="form-body">
			<div class="half-block">
				<div class="personal__top-form--status__items">
					<div class="personal__top-form--status__item flexbox gap gap--2">
						<div class="personal__top-form--status__title font_13 secondary-color"><span><?=Loc::getMessage('subscr_conf')?></span></div>
						<div class="personal__top-form--status__value font_14 color_dark"><span><?=($arResult['SUBSCRIPTION']['CONFIRMED'] == 'Y' ? Loc::getMessage("subscr_yes") : Loc::getMessage('subscr_no'))?></span></div>
					</div>

					<?if ($arResult['SUBSCRIPTION']['DATE_INSERT']):?>
						<div class="personal__top-form--status__item flexbox gap gap--2">
							<div class="personal__top-form--status__title font_13 secondary-color"><span><?=Loc::getMessage('subscr_date_add')?></span></div>
							<div class="personal__top-form--status__value font_14 color_dark"><span><?=FormatDateFromDB($arResult['SUBSCRIPTION']['DATE_INSERT'], 'SHORT')?></span></div>
						</div>
					<?endif;?>

					<div class="personal__top-form--status__item flexbox gap gap--2">
						<div class="personal__top-form--status__title font_13 secondary-color"><span><?=Loc::getMessage('subscr_act')?></span></div>
						<div class="personal__top-form--status__value font_14 color_dark"><span><?=($arResult['SUBSCRIPTION']['ACTIVE'] == 'Y' ? Loc::getMessage('subscr_yes') : Loc::getMessage('subscr_no'))?></span></div>
					</div>

					<?if ($arResult['SUBSCRIPTION']['DATE_UPDATE']):?>
						<div class="personal__top-form--status__item flexbox gap gap--2">
							<div class="personal__top-form--status__title font_13 secondary-color"><span><?=Loc::getMessage('subscr_date_upd')?></span></div>
							<div class="personal__top-form--status__value font_14 color_dark"><span><?=FormatDateFromDB($arResult['SUBSCRIPTION']['DATE_UPDATE'], 'SHORT')?></span></div>
						</div>
					<?endif;?>

					<div class="personal__top-form--status__item flexbox gap gap--2">
						<div class="personal__top-form--status__title font_13 secondary-color"><span><?=Loc::getMessage('adm_id')?></span></div>
						<div class="personal__top-form--status__value font_14 color_dark"><span><?=$arResult['SUBSCRIPTION']['ID']?></span></div>
					</div>
				</div>

				<div class="text_block font_13">
					<?if ($arResult['SUBSCRIPTION']['CONFIRMED'] <> 'Y'):?>
						<?=Loc::getMessage('subscr_title_status_note1')?>
					<?elseif ($arResult['SUBSCRIPTION']['ACTIVE'] == 'Y'):?>
						<?=Loc::getMessage('subscr_title_status_note2')?>
						<br />
						<?=Loc::getMessage('subscr_status_note3')?>
					<?else:?>
						<?=Loc::getMessage('subscr_status_note4')?>
						<br />
						<?=Loc::getMessage('subscr_status_note5')?>
					<?endif;?>
				</div>
			</div>
		</div>
			
		<?if ($arResult['SUBSCRIPTION']['CONFIRMED'] == 'Y'):?>	
			<div class="button-block form-footer">
				<div class="form-footer__buttons">
					<?if ($arResult['SUBSCRIPTION']['ACTIVE'] == 'Y'):?>
						<button type="submit" class="btn btn-default btn-lg" name="unsubscribe" value="unsubscribe" ><?=Loc::getMessage('subscr_unsubscr')?></button>
						<input type="hidden" name="action" value="unsubscribe" />
					<?else:?>
						<button type="submit" class="btn btn-default btn-lg" name="activate" value="activate" ><?=Loc::getMessage('subscr_activate')?></button>
						<input type="hidden" name="action" value="activate" />
					<?endif;?>
				</div>
			</div>
		<?endif;?>

		<input type="hidden" name="ID" value="<?=$arResult['SUBSCRIPTION']['ID']?>" />
		<?=bitrix_sessid_post()?>
	</form>

	<script>
	$(document).ready(function(){
		$('#subscribe-status-form').validate({
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
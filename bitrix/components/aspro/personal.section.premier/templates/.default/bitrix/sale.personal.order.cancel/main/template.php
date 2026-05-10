<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arParams['REASONS'] = $arParams['REASONS'] ?? '[]';
$arParams['~REASONS'] = $arParams['~REASONS'] ?? '[]';
$arParams['REASON_REQUIRED'] = ($arParams['REASON_REQUIRED'] ?? 'N') === 'Y' ? 'Y' : 'N';

$arReasons = CUtil::JsObjectToPhp($arParams['~REASONS'] ?? '[]', true);
if ($arReasons) {
	foreach ($arReasons as $i => $arReason) {
		if (
			!$arReason ||
			!is_array($arReason) ||
			!$arReason['active'] ||
			!$arReason['name']
		) {
			unset($arReasons[$i]);
			continue;
		}
		
		$arReasons[$i] = $arReason['name'];
	}
}
?>
<div id="order-cancel--<?=$arResult['ID']?>" class="personal__block personal__block--order-cancel ">
	<?if ($arResult['ERROR_MESSAGE']):?>
		<div class="alert alert-danger p p--20 rounded-6"><?=$arResult['ERROR_MESSAGE']?></div>
	<?else:?>
		<div class="form p p--32 bordered outer-rounded-x">
			<h5 class="mb mb--24">
				<?=Loc::getMessage(
					'SPOC_CANCEL_TITLE',
					[
						'#ID#' => $arResult['ACCOUNT_NUMBER'],
						'#HREF#' => $arResult['URL_TO_DETAIL'],
					]
				)?>
			</h5>

			<div class="alert alert-danger rounded-6 p p--20 line-block line-block--justify-between line-block--gap line-block--gap-12">
				<?=Loc::getMessage('SPOC_CANCEL_DESC')?>
				<?=TSolution::showSpriteIconSvg('/bitrix/components/aspro/personal.section.premier/templates/.default/bitrix/sale.personal.order.detail/main/images/svg/status.svg#alert-danger', 'status', ['WIDTH' => 16, 'HEIGHT' => 16]);?>
			</div>

			<form id="order-cancel-form_<?=$arResult['ID']?>" name="order-cancel-form" method="post" action="<?=POST_FORM_ACTION_URI?>">
				<input type="hidden" name="ID" value="<?=$arResult['ID']?>" />
				<input type="hidden" name="CANCEL" value="Y" />
				<input type="hidden" name="REASON_CANCELED" value="" />
				<?=bitrix_sessid_post()?>
				
				<div class="form-body">
					<?if ($arReasons):?>
						<div class="form-group form-group--reason mt mt--24 flexbox gap gap--12 flexbox--align-start">
							<?foreach ($arReasons as $i => $reason):?>
								<?$id = 'REASON_'.$arResult['ID'].'_'.$i?>
								<div class=" grid-column-start--1">
									<input class="form-checkbox__input" type="checkbox" name="REASONS[]" id="<?=$id?>" value="<?=htmlspecialcharsbx($reason)?>">
									<label for="<?=$id?>" class="form-checkbox__label">
										<span class="bx_filter_input_checkbox">
											<span><?=$reason?></span>
										</span>
										<span class="form-checkbox__box form-box"></span>
									</label>
								</div>
							<?endforeach;?>
						</div>
					<?endif;?>

					<div class="form-group grid-column-start--1 mt mt--24">
						<label for="ANOTHER_REASON" class="font_14 color_dark"><span><?=Loc::getMessage('SPOC_ANOTHER_REASON_LABEL')?></span></label>
						<div class="input">
							<textarea id="ANOTHER_REASON" name="ANOTHER_REASON" class="form-control" maxlength="200"></textarea>
						</div>
					</div>
				</div>

				<div class="form-footer mt mt--24">
					<button class="btn btn-default btn-elg" type="submit" name="action" value="delete"<?=($arParams['REASON_REQUIRED'] === 'Y' ? ' disabled' : '')?>><span><?=Loc::getMessage('SPOC_CANCEL_BTN')?></span></button>
				</div>
			</form>
		</div>

		<script>
		BX.ready(function(){
			new JOrderCancel(
				'#order-cancel--<?=$arResult['ID']?>',
				<?=CUtil::PhpToJSObject([
					'reasonRequired' => $arParams['REASON_REQUIRED'] === 'Y',
				], false, true)?>
			);
		});
		</script>
	<?endif;?>
</div>
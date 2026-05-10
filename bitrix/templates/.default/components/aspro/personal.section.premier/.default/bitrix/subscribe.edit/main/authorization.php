<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<div class="personal__top-form bordered outer-rounded-x p p--32">
	<h4><?=Loc::getMessage('subscr_title_auth')?></h4>

	<form action="<?=$arResult['FORM_ACTION']?>" method="post" class="form">
		<?=bitrix_sessid_post()?>

		<div><?=Loc::getMessage('adm_auth_user')?>
			<?=htmlspecialcharsbx($USER->GetFormattedName(false))?> (<?=htmlspecialcharsbx($USER->GetLogin())?>).
		</div>

		<div>
			<?if ($arResult['ID'] == 0):?>
				<?=Loc::getMessage('subscr_auth_logout1')?> <a href="<?=$arResult['FORM_ACTION']?>?logout=YES&amp;sf_EMAIL=<?=$arResult['REQUEST']['EMAIL']?><?=$arResult['REQUEST']['RUBRICS_PARAM']?>"><?=Loc::getMessage('adm_auth_logout')?></a><?=Loc::getMessage('subscr_auth_logout2')?>
			<?else:?>
				<?=Loc::getMessage('subscr_auth_logout3')?> <a href="<?=$arResult['FORM_ACTION']?>?logout=YES&amp;sf_EMAIL=<?=$arResult['REQUEST']['EMAIL']?><?=$arResult['REQUEST']['RUBRICS_PARAM']?>"><?=Loc::getMessage('adm_auth_logout')?></a><?=Loc::getMessage('subscr_auth_logout4')?>
			<?endif;?>
		</div>
	</form>
</div>

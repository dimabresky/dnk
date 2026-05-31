<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_CONSENTS'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_CONSENTS'));
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		'dnk:user.consent.manage',
		'.default',
		[
			'SET_TITLE' => 'N',
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);?>
</div>

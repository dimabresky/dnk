<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_CERTIFICATE_REQUESTS'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_CERTIFICATE_REQUESTS'));
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		'dnk:certificate.request.list',
		'.default',
		[
			'SET_TITLE' => 'N',
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);?>
</div>

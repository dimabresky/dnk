<?php

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arExtensions = ['fancybox', 'detail'];

if ($arParams["USE_SHARE"] || $arParams["USE_RSS"]) {
	$arExtensions[] = 'item_action';
	$arExtensions[] = 'share';
}

\TSolution\Banner\Transparency::setHeaderClasses($templateData);
\TSolution\Functions::replaceListParams($arParams, ['PROPERTY_CODE' => 'PROPERTY_CODE']);
\TSolution\Extensions::init($arExtensions);
?>
<div class="partner-epilog flexbox gap gap--48">	
	<?$arBlockOrder = explode(",", $arParams['DETAIL_BLOCKS_ORDER']);?>
	<?$arEpilogBlocks = new \TSolution\Template\Epilog\Blocks([
		'ORDERED' => $arBlockOrder,
	], __DIR__);?>
	
	<?foreach ($arEpilogBlocks->ordered as $key => $block):?>
		<?include $block;?>
	<?endforeach;?>
</div>
<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);
IncludeModuleLangFile(__FILE__);
?>
<?\Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('smartsearch-input--'.$this->__name);?>
<script>
BX.ready(() => {
	if (BX.Aspro.Utils.isFunction(BX.Aspro.SmartSearch?.Input)) {
		new BX.Aspro.SmartSearch.Input(
			<?var_export(htmlspecialchars_decode($arParams['INPUT']))?>,
			<?=CUtil::PhpToJSObject($arParams)?>,
			<?=CUtil::PhpToJSObject($arResult)?>
		);
	}
});
</script>
<?\Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('smartsearch-input--'.$this->__name, '');?>
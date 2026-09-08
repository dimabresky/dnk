<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);
IncludeModuleLangFile(__FILE__);

$inputSelector = htmlspecialchars_decode((string)($arParams['INPUT'] ?? ''));
?>
<?\Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('smartsearch-input--'.$this->__name);?>
<script>
BX.ready(() => {
	const inputSelector = <?=CUtil::PhpToJSObject($inputSelector)?>;
	const inputNode = inputSelector ? document.querySelector(inputSelector) : null;
	// Keep page search aligned with header input max length
	if (inputNode) {
		inputNode.setAttribute('maxlength', '255');
	}

	if (BX.Aspro.Utils.isFunction(BX.Aspro.SmartSearch?.Input)) {
		new BX.Aspro.SmartSearch.Input(
			inputSelector,
			<?=CUtil::PhpToJSObject($arParams)?>,
			<?=CUtil::PhpToJSObject($arResult)?>
		);
	}
});
</script>
<?\Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('smartsearch-input--'.$this->__name, '');?>

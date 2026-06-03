<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(false);
$this->addExternalJS($templateFolder . '/script.js');
?>
<div id="dnk-bonus-background-sync"
    class="dnk-bonus-background-sync"
    data-auto-refresh="<?=htmlspecialcharsbx($arResult['AUTO_REFRESH'])?>"
    data-balance-selector="<?=htmlspecialcharsbx($arResult['BALANCE_SELECTOR'])?>"
    data-balance-formatted="<?=htmlspecialcharsbx($arResult['BALANCE_FORMATTED'])?>"
    data-balance-unit="<?=htmlspecialcharsbx($arResult['BALANCE_UNIT'])?>"
    hidden
    aria-hidden="true"></div>

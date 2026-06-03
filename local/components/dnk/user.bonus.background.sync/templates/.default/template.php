<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(false);
?>
<div id="dnk-bonus-background-sync"
    class="dnk-bonus-background-sync"
    data-auto-refresh="<?=htmlspecialcharsbx($arResult['AUTO_REFRESH'])?>"
    hidden
    aria-hidden="true"></div>

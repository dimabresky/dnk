<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);

if (($arResult['DATETIME'] ?? '') === '') {
    return;
}
?>
<div
    class="dnk-banner-countdown"
    style="color: <?= htmlspecialcharsbx($arResult['COLOR']) ?>"
    data-end="<?= htmlspecialcharsbx($arResult['DATETIME']) ?>"
    role="timer"
>
    <div class="dnk-banner-countdown__box">
        <?php if (($arResult['PICTURE'] ?? '') !== ''): ?>
            <img
                class="dnk-banner-countdown__img"
                alt="<?= htmlspecialcharsbx($arResult['TITLE']) ?>"
                data-src="<?= htmlspecialcharsbx($arResult['PICTURE']) ?>"
                decoding="async"
            >
        <?php endif; ?>
        <?php if (($arResult['TITLE'] ?? '') !== ''): ?>
            <div class="dnk-banner-countdown__title switcher-title fw-500 font_24"><?= htmlspecialcharsbx($arResult['TITLE']) ?></div>
        <?php endif; ?>
        <div class="dnk-banner-countdown__items"></div>
    </div>
</div>

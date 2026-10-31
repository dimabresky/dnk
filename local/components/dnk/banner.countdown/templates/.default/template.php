<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);

if (($arResult['END_TS'] ?? 0) <= 0) {
    return;
}

if (!defined('DNK_BANNER_COUNTDOWN_JS_MESSAGES')) {
    define('DNK_BANNER_COUNTDOWN_JS_MESSAGES', true);
    ?>
    <script>
        BX.message(<?= CUtil::PhpToJSObject([
            'DNK_BANNER_COUNTDOWN_LABELS' => [
                GetMessage('DNK_BANNER_COUNTDOWN_YEAR0'),
                GetMessage('DNK_BANNER_COUNTDOWN_MONTH0'),
                GetMessage('DNK_BANNER_COUNTDOWN_WEEK0'),
                GetMessage('DNK_BANNER_COUNTDOWN_DAY0'),
                GetMessage('DNK_BANNER_COUNTDOWN_HOUR0'),
                GetMessage('DNK_BANNER_COUNTDOWN_MIN0'),
                GetMessage('DNK_BANNER_COUNTDOWN_SEC0'),
            ],
            'DNK_BANNER_COUNTDOWN_LABELS1' => [
                GetMessage('DNK_BANNER_COUNTDOWN_YEAR1'),
                GetMessage('DNK_BANNER_COUNTDOWN_MONTH1'),
                GetMessage('DNK_BANNER_COUNTDOWN_WEEK1'),
                GetMessage('DNK_BANNER_COUNTDOWN_DAY1'),
                GetMessage('DNK_BANNER_COUNTDOWN_HOUR1'),
                GetMessage('DNK_BANNER_COUNTDOWN_MIN1'),
                GetMessage('DNK_BANNER_COUNTDOWN_SEC1'),
            ],
            'DNK_BANNER_COUNTDOWN_LABELS2' => [
                GetMessage('DNK_BANNER_COUNTDOWN_YEAR2'),
                GetMessage('DNK_BANNER_COUNTDOWN_MONTH2'),
                GetMessage('DNK_BANNER_COUNTDOWN_WEEK2'),
                GetMessage('DNK_BANNER_COUNTDOWN_DAY2'),
                GetMessage('DNK_BANNER_COUNTDOWN_HOUR2'),
                GetMessage('DNK_BANNER_COUNTDOWN_MIN2'),
                GetMessage('DNK_BANNER_COUNTDOWN_SEC2'),
            ],
        ]) ?>);
    </script>
    <?php
}
?>
<div
    class="dnk-banner-countdown"
    style="color: <?= htmlspecialcharsbx($arResult['COLOR']) ?>"
    data-end="<?= (int) $arResult['END_TS'] ?>"
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

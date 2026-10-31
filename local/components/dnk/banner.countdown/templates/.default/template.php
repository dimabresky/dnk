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
            'DNK_BANNER_COUNTDOWN_LIB' => $this->GetFolder() . '/flipclock.umd.js',
            'DNK_BANNER_COUNTDOWN_LABELS' => [
                GetMessage('DNK_BANNER_COUNTDOWN_YEAR0'),
                GetMessage('DNK_BANNER_COUNTDOWN_MONTH0'),
                GetMessage('DNK_BANNER_COUNTDOWN_WEEK0'),
                GetMessage('DNK_BANNER_COUNTDOWN_DAY0'),
                GetMessage('DNK_BANNER_COUNTDOWN_HOUR0'),
                GetMessage('DNK_BANNER_COUNTDOWN_MIN0'),
                GetMessage('DNK_BANNER_COUNTDOWN_SEC0'),
            ],
        ]) ?>);
    </script>
    <?php
}
?>
<?php
$countdownStyle = [];
if (($arResult['COLOR'] ?? '') !== '') {
    $countdownStyle[] = 'color:' . $arResult['COLOR'];
}
if (($arResult['TIMER_COLOR'] ?? '') !== '') {
    $countdownStyle[] = '--dnk-timer-color:' . $arResult['TIMER_COLOR'];
}
if (($arResult['TIMER_BG'] ?? '') !== '') {
    $countdownStyle[] = '--dnk-timer-bg:' . $arResult['TIMER_BG'];
}
if (($arResult['TIMER_SHADOW'] ?? '') !== '') {
    $countdownStyle[] = '--dnk-timer-shadow:' . $arResult['TIMER_SHADOW'];
}
?>
<div
    class="dnk-banner-countdown"
    <?php if ($countdownStyle): ?>style="<?= htmlspecialcharsbx(implode(';', $countdownStyle)) ?>"<?php endif; ?>
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
            <h2 class="dnk-banner-countdown__title switcher-title fw-500 font_48"><?= htmlspecialcharsbx($arResult['TITLE']) ?></h2>
        <?php endif; ?>
        <?php if (($arResult['SUBTITLE'] ?? '') !== ''): ?>
            <div class="dnk-banner-countdown__subtitle font_20"><?= htmlspecialcharsbx($arResult['SUBTITLE']) ?></div>
        <?php endif; ?>
        <div class="dnk-banner-countdown__items"></div>
    </div>
</div>

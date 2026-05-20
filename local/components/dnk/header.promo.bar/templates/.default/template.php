<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */
?>
<style>
.dnk-header-promo-bar {
    box-sizing: border-box;
    width: 100%;
    min-height: 45px;
    position: relative;
    z-index: 990;
    font-size: 13px;
    line-height: 1.2;
}

.dnk-header-promo-bar *,
.dnk-header-promo-bar *::before,
.dnk-header-promo-bar *::after {
    box-sizing: border-box;
}

.dnk-header-promo-bar__inner {
    max-width: 100%;
    margin: 0 auto;
    min-height: 45px;
    padding: 4px 40px 4px 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    gap: 10px;
}

.dnk-header-promo-bar__inner--with-media {
    padding: 0;
}

.dnk-header-promo-bar__body {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px 10px;
    min-height: 37px;
    flex: 1 1 auto;
    text-align: center;
    text-decoration: none;
    color: inherit;
}

.dnk-header-promo-bar__body--with-media {
    display: block;
    width: 100%;
    min-height: 45px;
    flex: 1 1 100%;
}

.dnk-header-promo-bar__media {
    position: relative;
    width: 100%;
    min-height: 45px;
    overflow: hidden;
    line-height: 0;
}

.dnk-header-promo-bar__media .dnk-header-promo-bar__picture {
    display: block;
    width: 100%;
}

.dnk-header-promo-bar__media .dnk-header-promo-bar__img {
    display: block;
    width: 100%;
    height: 45px;
    max-height: none;
    object-fit: cover;
    object-position: center;
}

.dnk-header-promo-bar__overlay {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px 10px;
    padding: 4px 40px 4px 12px;
    text-align: center;
    pointer-events: none;
}

.dnk-header-promo-bar__overlay .dnk-header-promo-bar__text,
.dnk-header-promo-bar__overlay .dnk-header-promo-bar__timer-block {
    pointer-events: auto;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
}

.dnk-header-promo-bar__body--link:hover {
    opacity: 0.92;
}

.dnk-header-promo-bar a.dnk-header-promo-bar__body,
.dnk-header-promo-bar a.dnk-header-promo-bar__body:visited,
.dnk-header-promo-bar a.dnk-header-promo-bar__body:hover,
.dnk-header-promo-bar a.dnk-header-promo-bar__body:focus {
    text-decoration: none;
}

.dnk-header-promo-bar__body:not(.dnk-header-promo-bar__body--with-media) .dnk-header-promo-bar__picture,
.dnk-header-promo-bar__body:not(.dnk-header-promo-bar__body--with-media) .dnk-header-promo-bar__img {
    display: block;
    flex-shrink: 0;
}

.dnk-header-promo-bar__body:not(.dnk-header-promo-bar__body--with-media) .dnk-header-promo-bar__img {
    max-height: 45px;
    width: auto;
    height: auto;
    object-fit: contain;
}

.dnk-header-promo-bar__text {
    flex: 0 1 auto;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 767px) {
    .dnk-header-promo-bar__media .dnk-header-promo-bar__img {
        object-fit: cover;
        object-position: center;
    }

    .dnk-header-promo-bar__text {
        white-space: normal;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
}

.dnk-header-promo-bar__timer-block {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
    white-space: nowrap;
}

.dnk-header-promo-bar__timer {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

.dnk-header-promo-bar__close {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    width: 28px;
    height: 28px;
    margin: 0;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    opacity: 0.75;
    cursor: pointer;
    font-size: 22px;
    line-height: 1;
}

.dnk-header-promo-bar__close:hover {
    opacity: 1;
}

.dnk-header-promo-bar.dnk-header-promo-bar--hidden {
    display: none !important;
}

</style><?
$this->addExternalCss($templateFolder . '/style.css');

if (empty($arResult['ITEM'])) {
    return;
}

$item = $arResult['ITEM'];

$bp = (int) ($arParams['MOBILE_BREAKPOINT'] ?? 768);
if ($bp < 320) {
    $bp = 768;
}
$mediaMax = $bp - 1;

$hideOnExpire = ($arParams['HIDE_ON_EXPIRE'] ?? 'Y') === 'Y';

$bg = $item['BG_COLOR'];
$fg = $item['TEXT_COLOR'];
$styleBar = '';
if ($bg !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $bg)) {
    $styleBar .= 'background-color:' . htmlspecialcharsbx($bg) . ';';
}

$fontSize = trim((string) ($item['FONT_SIZE'] ?? ''));
if ($fontSize !== '') {
    $styleBar .= 'font-size:' . htmlspecialcharsbx($fontSize) . ';';
}

$styleFg = '';
if ($fg !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $fg)) {
    $styleFg = 'color:' . htmlspecialcharsbx($fg) . ';';
}

$attrFg = $styleFg !== '' ? ' style="' . htmlspecialcharsbx($styleFg) . '"' : '';

$link = $item['LINK'];
$hasLink = $link !== '';
$linkEsc = htmlspecialcharsbx($link);
$targetAttr = $item['LINK_TARGET'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';

$desk = $item['IMAGE_DESKTOP']['SRC'] ?? '';
$mob = $item['IMAGE_MOBILE']['SRC'] ?? '';
$imgForDesktop = $desk !== '' ? $desk : $mob;
$imgForMobile = $mob !== '' ? $mob : $desk;

$showPicture = ($desk !== '' && $mob !== '' && $desk !== $mob);
$showSingleImg = ($imgForDesktop !== '') && !$showPicture;

$text = $item['TEXT'];
$isHtml = ($item['PREVIEW_TEXT_TYPE'] ?? '') === 'html';

$bodyClass = 'dnk-header-promo-bar__body';
if ($hasLink) {
    $bodyClass .= ' dnk-header-promo-bar__body--link';
}

$hasImage = $showPicture || $showSingleImg;
$showTimerBlock = $item['SHOW_TIMER'] && (int) $item['TIMER_END_TS'] > 0;
$hasOverlayContent = ($text !== '') || $showTimerBlock;
?>
<div
    id="dnk-header-promo-bar"
    class="dnk-header-promo-bar"
    <?php if ($styleBar !== ''): ?>style="<?= htmlspecialcharsbx($styleBar) ?>"<?php endif; ?>
    data-element-id="<?= (int) $item['ID'] ?>"
    data-dismiss-key="<?= htmlspecialcharsbx($item['DISMISS_KEY']) ?>"
    data-timer-end="<?= (int) $item['TIMER_END_TS'] ?>"
    data-show-timer="<?= $item['SHOW_TIMER'] ? '1' : '0' ?>"
    data-hide-on-expire="<?= $hideOnExpire ? '1' : '0' ?>"
    data-allow-dismiss="<?= $item['ALLOW_DISMISS'] ? '1' : '0' ?>"
    data-mobile-max="<?= (int) $mediaMax ?>"
>
    <div class="dnk-header-promo-bar__inner<?= $hasImage ? ' dnk-header-promo-bar__inner--with-media' : '' ?>">
        <?php if ($hasLink): ?>
        <a class="<?= htmlspecialcharsbx($bodyClass) ?><?= $hasImage ? ' dnk-header-promo-bar__body--with-media' : '' ?>" href="<?= $linkEsc ?>"<?= $targetAttr ?><?= $attrFg ?>>
            <?php else: ?>
            <div class="<?= htmlspecialcharsbx($bodyClass) ?><?= $hasImage ? ' dnk-header-promo-bar__body--with-media' : '' ?>"<?= $attrFg ?>>
                <?php endif; ?>

                <?php if ($hasImage): ?>
                    <div class="dnk-header-promo-bar__media">
                        <?php if ($showPicture): ?>
                            <picture class="dnk-header-promo-bar__picture">
                                <source media="(max-width: <?= (int) $mediaMax ?>px)" srcset="<?= htmlspecialcharsbx($mob) ?>">
                                <img
                                    class="dnk-header-promo-bar__img"
                                    src="<?= htmlspecialcharsbx($desk) ?>"
                                    alt="<?= htmlspecialcharsbx($item['IMAGE_ALT']) ?>"
                                    loading="eager"
                                    decoding="async"
                                    height="45"
                                >
                            </picture>
                        <?php else: ?>
                            <img
                                class="dnk-header-promo-bar__img"
                                src="<?= htmlspecialcharsbx($imgForDesktop) ?>"
                                alt="<?= htmlspecialcharsbx($item['IMAGE_ALT']) ?>"
                                loading="eager"
                                decoding="async"
                                height="45"
                            >
                        <?php endif; ?>

                        <?php if ($hasOverlayContent): ?>
                            <div class="dnk-header-promo-bar__overlay">
                                <?php if ($text !== ''): ?>
                                    <span class="dnk-header-promo-bar__text"<?= $attrFg ?>><?php
                                    if ($isHtml) {
                                        echo $text;
                                    } else {
                                        echo nl2br(htmlspecialcharsbx($text));
                                    }
                                    ?></span>
                                <?php endif; ?>

                                <?php if ($showTimerBlock): ?>
                                    <span class="dnk-header-promo-bar__timer-block"<?= $attrFg ?>>
                                        <?php if ($item['TIMER_LABEL'] !== ''): ?>
                                            <span class="dnk-header-promo-bar__timer-label"<?= $attrFg ?>><?= htmlspecialcharsbx($item['TIMER_LABEL']) ?></span>
                                        <?php endif; ?>
                                        <span class="dnk-header-promo-bar__timer" data-role="timer"<?= $attrFg ?>></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if ($text !== ''): ?>
                        <span class="dnk-header-promo-bar__text"<?= $attrFg ?>><?php
                        if ($isHtml) {
                            echo $text;
                        } else {
                            echo nl2br(htmlspecialcharsbx($text));
                        }
                        ?></span>
                    <?php endif; ?>

                    <?php if ($showTimerBlock): ?>
                        <span class="dnk-header-promo-bar__timer-block"<?= $attrFg ?>>
                            <?php if ($item['TIMER_LABEL'] !== ''): ?>
                                <span class="dnk-header-promo-bar__timer-label"<?= $attrFg ?>><?= htmlspecialcharsbx($item['TIMER_LABEL']) ?></span>
                            <?php endif; ?>
                            <span class="dnk-header-promo-bar__timer" data-role="timer"<?= $attrFg ?>></span>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>

            <?php if ($hasLink): ?>
        </a>
        <?php else: ?>
            </div>
        <?php endif; ?>

        <?php if ($item['ALLOW_DISMISS']): ?>
            <button type="button" class="dnk-header-promo-bar__close" aria-label="<?= htmlspecialcharsbx(GetMessage('DNK_HEADER_PROMO_BAR_CLOSE')) ?>"<?= $attrFg ?>>&times;</button>
        <?php endif; ?>
    </div>
</div>
<?php
// Компонент вставляется через OnEndBufferContent (см. HeaderPromoEvents): автоподключение script.js шаблона не попадает в страницу — явный тег обязателен.
$dnkHeaderPromoScriptRel = $templateFolder . '/script.js';
$dnkHeaderPromoScriptAbs = \Bitrix\Main\Application::getDocumentRoot() . $dnkHeaderPromoScriptRel;
$dnkHeaderPromoScriptVer = is_file($dnkHeaderPromoScriptAbs) ? (int) filemtime($dnkHeaderPromoScriptAbs) : 1;
?>
<script src="<?= htmlspecialcharsbx($dnkHeaderPromoScriptRel) ?>?<?= $dnkHeaderPromoScriptVer ?>"></script>

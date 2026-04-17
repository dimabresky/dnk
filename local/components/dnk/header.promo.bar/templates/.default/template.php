<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

if (empty($arResult['ITEM'])) {
    return;
}

$item = $arResult['ITEM'];
$folder = $this->GetFolder();

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
<link rel="stylesheet" href="<?= htmlspecialcharsbx($folder) ?>/style.css"/>
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
<script src="<?= htmlspecialcharsbx($folder) ?>/script.js" defer></script>

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

$stripSrc = $arResult['STRIP_SRC'] ?? null;
$items = $arResult['ITEMS'] ?? [];
$badges = $arResult['BADGES'] ?? [];

if ($stripSrc === null && $items === [] && $badges === []) {
    return;
}
?>
<section class="dnk-payment-logos" aria-label="<?= htmlspecialcharsbx(GetMessage('DNK_PAYMENT_LOGOS_ARIA_LABEL')) ?>">
    <?php if ($stripSrc !== null): ?>
        <div class="dnk-payment-logos__inner dnk-payment-logos__inner--strip">
            <img
                class="dnk-payment-logos__strip"
                src="<?= htmlspecialcharsbx($stripSrc) ?>"
                alt="<?= htmlspecialcharsbx((string) ($arResult['STRIP_ALT'] ?? '')) ?>"
                loading="lazy"
                decoding="async"
            >
        </div>
    <?php else: ?>
        <?php if ($items !== []): ?>
            <div class="dnk-payment-logos__inner dnk-payment-logos__inner--main">
                <?php foreach ($items as $item): ?>
                    <img
                        class="dnk-payment-logos__img"
                        src="<?= htmlspecialcharsbx($item['src']) ?>"
                        alt="<?= htmlspecialcharsbx($item['alt']) ?>"
                        loading="lazy"
                        decoding="async"
                    >
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($badges !== []): ?>
            <div class="dnk-payment-logos__inner dnk-payment-logos__inner--badges">
                <?php foreach ($badges as $item): ?>
                    <img
                        class="dnk-payment-logos__img dnk-payment-logos__img--badge"
                        src="<?= htmlspecialcharsbx($item['src']) ?>"
                        alt="<?= htmlspecialcharsbx($item['alt']) ?>"
                        loading="lazy"
                        decoding="async"
                    >
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

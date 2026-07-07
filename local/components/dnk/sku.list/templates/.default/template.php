<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult['ITEMS']) || empty($arResult['CURRENT_ITEM'])) {
    return;
}

$current = $arResult['CURRENT_ITEM'];
$currentName = htmlspecialcharsbx($current['NAME']);

?>
<div class="dnk-sku-list" data-dnk-sku-list>
    <div
        class="dnk-sku-list__label"
        data-dnk-sku-label
        data-default-name="<?= $currentName ?>"
    ><?= $currentName ?></div>
    <div class="dnk-sku-list__items" role="list">
        <?php foreach ($arResult['ITEMS'] as $item): ?>
            <?php
            $itemName = htmlspecialcharsbx($item['NAME']);
            $isCurrent = !empty($item['IS_CURRENT']);
            ?>
            <a
                href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>"
                class="dnk-sku-list__item<?= $isCurrent ? ' dnk-sku-list__item--current' : '' ?>"
                role="listitem"
                data-sku-name="<?= $itemName ?>"
                title="<?= $itemName ?>"
                <?= $isCurrent ? 'aria-current="page"' : '' ?>
            >
                <span class="dnk-sku-list__image-wrap" aria-hidden="true">
                    <?php if (!empty($item['PICTURE_SRC'])): ?>
                        <img
                            src="<?= htmlspecialcharsbx($item['PICTURE_SRC']) ?>"
                            alt="<?= $itemName ?>"
                            class="dnk-sku-list__image"
                            loading="lazy"
                        >
                    <?php else: ?>
                        <span class="dnk-sku-list__placeholder"></span>
                    <?php endif; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

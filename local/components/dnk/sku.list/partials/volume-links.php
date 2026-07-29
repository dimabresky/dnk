<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arResult
 */

?>
    <div
        class="dnk-sku-list__label"
        data-dnk-sku-label
        data-default-name="<?= $currentName ?>"
    ><?= $currentName ?></div>
    <div class="dnk-sku-list__volume-list" role="list">
        <?php foreach ($arResult['ITEMS'] as $item): ?>
            <?php
            if (!empty($item['IS_CURRENT'])) {
                continue;
            }
            $itemName = htmlspecialcharsbx($item['VARIANT_LABEL'] ?? $item['NAME']);
            ?>
            <a
                href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>"
                class="dnk-sku-list__volume-item"
                role="listitem"
                data-sku-name="<?= $itemName ?>"
                title="<?= $itemName ?>"
            ><?= $itemName ?></a>
        <?php endforeach; ?>
    </div>

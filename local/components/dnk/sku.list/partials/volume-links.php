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
            $itemName = htmlspecialcharsbx($item['VARIANT_LABEL'] ?? $item['NAME']);
            $isCurrent = !empty($item['IS_CURRENT']);
            ?>
            <a
                href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>"
                class="dnk-sku-list__volume-item<?= $isCurrent ? ' dnk-sku-list__volume-item--current' : '' ?>"
                role="listitem"
                data-sku-name="<?= $itemName ?>"
                title="<?= $itemName ?>"
                <?= $isCurrent ? 'aria-current="page"' : '' ?>
            ><?= $itemName ?></a>
        <?php endforeach; ?>
    </div>

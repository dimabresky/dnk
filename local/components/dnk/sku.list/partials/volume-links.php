<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arResult
 */

?>
    <div class="dnk-sku-list__volume-list" role="list">
        <?php foreach ($arResult['ITEMS'] as $item): ?>
            <?php
            $itemName = htmlspecialcharsbx($item['VARIANT_LABEL'] ?? $item['NAME']);
            $isCurrent = !empty($item['IS_CURRENT']);
            ?>
            <?php if ($isCurrent): ?>
                <span
                    class="dnk-sku-list__volume-item dnk-sku-list__volume-item--current"
                    role="listitem"
                    aria-current="page"
                ><?= $itemName ?></span>
            <?php else: ?>
                <a
                    href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>"
                    class="dnk-sku-list__volume-item"
                    role="listitem"
                    data-sku-name="<?= $itemName ?>"
                    title="<?= $itemName ?>"
                ><?= $itemName ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

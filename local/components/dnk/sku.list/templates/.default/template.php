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
<style>
    .dnk-sku-list {
    margin-top: 20px;
    min-width: 0;
    width: 100%;
    max-width: 100%;
}

.dnk-sku-list__label {
    margin-bottom: 0.75rem;
    font-size: 1rem;
    line-height: 1.4;
    font-weight: 400;
}

.dnk-sku-list__items {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    min-width: 0;
    max-width: 100%;
}

.dnk-sku-list__item {
    display: block;
    box-sizing: border-box;
    flex: 0 0 auto;
    flex-shrink: 0;
    width: 52px;
    height: 52px;
    padding: 4px;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    background-color: #fff;
    text-decoration: none;
    color: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.dnk-sku-list__item:not(.dnk-sku-list__item--current):hover {
    border-color: rgba(0, 0, 0, 0.35);
}

.dnk-sku-list__item--current {
    border: 2px solid #000;
    padding: 3px;
}

.dnk-sku-list__item--current:hover {
    border-color: #000;
}

.dnk-sku-list__image-wrap {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 4px;
    overflow: hidden;
    background-color: #f5f5f5;
}

.dnk-sku-list__image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.dnk-sku-list__placeholder {
    display: block;
    width: 100%;
    height: 100%;
    background-color: #e8e8e8;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Crect x='3' y='3' width='18' height='18' rx='2'%3E%3C/rect%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'%3E%3C/circle%3E%3Cpath d='M21 15l-5-5L5 21'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 18px 18px;
}
</style>
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
<script>
    (function () {
    'use strict';

    function bindRoot(root) {
        if (root.getAttribute('data-dnk-sku-list-init') === '1') {
            return;
        }

        var label = root.querySelector('[data-dnk-sku-label]');
        var itemsWrap = root.querySelector('.dnk-sku-list__items');
        if (!label || !itemsWrap) {
            return;
        }

        root.setAttribute('data-dnk-sku-list-init', '1');

        var defaultName = label.getAttribute('data-default-name') || label.textContent;

        function restoreLabel() {
            label.textContent = defaultName;
        }

        itemsWrap.querySelectorAll('.dnk-sku-list__item').forEach(function (item) {
            item.addEventListener('mouseenter', function () {
                var name = item.getAttribute('data-sku-name');
                if (name) {
                    label.textContent = name;
                }
            });
        });

        itemsWrap.addEventListener('mouseleave', restoreLabel);
    }

    function scan() {
        document.querySelectorAll('[data-dnk-sku-list]').forEach(bindRoot);
    }

    function boot() {
        scan();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('load', boot);

    if (typeof BX !== 'undefined' && BX.addCustomEvent) {
        BX.addCustomEvent(window, 'onAjaxSuccess', boot);
    }
})();
</script>
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Web\Json;

if (empty($arResult['ITEMS']) || empty($arResult['CURRENT_ITEM'])) {
    return;
}

$current = $arResult['CURRENT_ITEM'];
$currentName = htmlspecialcharsbx($current['SHADE_NAME'] ?? $current['NAME']);

$swiperOptions = Json::encode([
    'slidesPerView' => 'auto',
    'freeMode' => [
        'enabled' => true,
        'momentum' => true,
    ],
    'spaceBetween' => 6,
    'pagination' => false,
    'watchOverflow' => true,
]);

$rootModifierClass = ' dnk-sku-list--catalog-block';

/** @var string $componentPath */
$skuListPartial = $_SERVER['DOCUMENT_ROOT'] . $componentPath . '/partials/slider.php';

if (!is_file($skuListPartial)) {
    return;
}

if (empty($GLOBALS['DNK_SKU_LIST_CATALOG_BLOCK_STYLES'])) {
    $GLOBALS['DNK_SKU_LIST_CATALOG_BLOCK_STYLES'] = true;
    ?>
<style>
.dnk-sku-list {
    margin-top: 20px;
    min-width: 0;
    width: 100%;
    max-width: 100%;
}

.dnk-sku-list--catalog-block {
    --dnk-sku-catalog-size: 35px;
    --dnk-sku-catalog-gap: 6px;
    margin-top: 12px;
}

.dnk-sku-list--catalog-block .dnk-sku-list__label {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.dnk-sku-list__label {
    margin-bottom: 0.75rem;
    font-size: 1rem;
    line-height: 1.4;
    font-weight: 400;
}

.dnk-sku-list__slider-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    max-width: 100%;
}

.dnk-sku-list__slider {
    flex: 1;
    overflow: hidden;
    min-width: 0;
}

.dnk-sku-list__slider .swiper-slide {
    width: auto;
}

.dnk-sku-list__nav {
    box-sizing: border-box;
    display: flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    padding: 0;
    border: 1px solid rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    background-color: #fff;
    color: var(--theme-base-color, #000);
    cursor: pointer;
    transition: border-color 0.2s ease, color 0.2s ease;
}

.dnk-sku-list__nav:hover {
    border-color: rgba(0, 0, 0, 0.35);
}

.dnk-sku-list__nav[hidden] {
    display: none;
}

.dnk-sku-list__nav-icon {
    display: block;
    width: 8px;
    height: 14px;
}

.dnk-sku-list--catalog-block .dnk-sku-list__nav {
    width: var(--dnk-sku-catalog-size);
    height: var(--dnk-sku-catalog-size);
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

.catalog-block .dnk-sku-list--catalog-block .dnk-sku-list__item,
.dnk-sku-list--catalog-block .dnk-sku-list__item {
    width: var(--dnk-sku-catalog-size);
    height: var(--dnk-sku-catalog-size);
    max-width: var(--dnk-sku-catalog-size);
}

.catalog-block .dnk-sku-list--catalog-block .dnk-sku-list__slider .swiper-slide,
.dnk-sku-list--catalog-block .dnk-sku-list__slider .swiper-slide {
    width: var(--dnk-sku-catalog-size) !important;
    max-width: var(--dnk-sku-catalog-size);
    flex-shrink: 0;
}

.dnk-sku-list--catalog-block .dnk-sku-list__slider:not(.swiper-initialized) .swiper-wrapper {
    display: flex;
    flex-wrap: nowrap;
    gap: var(--dnk-sku-catalog-gap);
}

.dnk-sku-list--catalog-block .dnk-sku-list__slider:not(.swiper-initialized) .swiper-slide {
    width: var(--dnk-sku-catalog-size) !important;
    max-width: var(--dnk-sku-catalog-size);
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

.catalog-block .dnk-sku-list--catalog-block img.dnk-sku-list__image,
.dnk-sku-list--catalog-block .dnk-sku-list__image {
    width: 100% !important;
    height: 100% !important;
    max-width: 100%;
    max-height: 100%;
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

.dnk-sku-list--catalog-block .dnk-sku-list__placeholder {
    background-size: 12px 12px;
}
</style>
    <?php
}

?>
<div class="dnk-sku-list<?= $rootModifierClass ?>" data-dnk-sku-list>
<?php
include $skuListPartial;
?>
</div>
<?php

if (empty($GLOBALS['DNK_SKU_LIST_CATALOG_BLOCK_SCRIPT'])) {
    $GLOBALS['DNK_SKU_LIST_CATALOG_BLOCK_SCRIPT'] = true;
    ?>
<script>
(function () {
    'use strict';

    var navEvents = ['resize', 'fromEdge', 'toEdge', 'slideChange', 'update', 'setTranslate'];

    function waitForSwiper(sliderEl, callback) {
        if (sliderEl.swiper) {
            callback(sliderEl.swiper);
            return;
        }

        var observer = new MutationObserver(function () {
            if (sliderEl.classList.contains('swiper-initialized') && sliderEl.swiper) {
                observer.disconnect();
                callback(sliderEl.swiper);
            }
        });

        observer.observe(sliderEl, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    function bindNavButtons(sliderEl, prevBtn, nextBtn) {
        if (sliderEl.getAttribute('data-dnk-sku-nav-init') === '1') {
            return;
        }

        waitForSwiper(sliderEl, function (swiper) {
            if (sliderEl.getAttribute('data-dnk-sku-nav-init') === '1') {
                return;
            }

            sliderEl.setAttribute('data-dnk-sku-nav-init', '1');

            function updateNavVisibility() {
                prevBtn.hidden = swiper.isLocked || swiper.isBeginning;
                nextBtn.hidden = swiper.isLocked || swiper.isEnd;
            }

            prevBtn.addEventListener('click', function () {
                swiper.slidePrev();
            });

            nextBtn.addEventListener('click', function () {
                swiper.slideNext();
            });

            updateNavVisibility();

            navEvents.forEach(function (eventName) {
                swiper.on(eventName, updateNavVisibility);
            });

            window.addEventListener('resize', updateNavVisibility);
        });
    }

    function bindRoot(root) {
        if (root.getAttribute('data-dnk-sku-list-init') === '1') {
            return;
        }

        var label = root.querySelector('[data-dnk-sku-label]');
        var itemsWrap = root.querySelector('.dnk-sku-list__slider');
        var prevBtn = root.querySelector('[data-dnk-sku-prev]');
        var nextBtn = root.querySelector('[data-dnk-sku-next]');
        if (!label || !itemsWrap || !prevBtn || !nextBtn) {
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
        bindNavButtons(itemsWrap, prevBtn, nextBtn);
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
    <?php
}

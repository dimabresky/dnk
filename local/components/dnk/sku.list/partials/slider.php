<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arResult
 * @var string $swiperOptions
 * @var string $currentName
 */

?>
    <div
        class="dnk-sku-list__label"
        data-dnk-sku-label
        data-default-name="<?= $currentName ?>"
    ><?= $currentName ?></div>
    <div class="dnk-sku-list__slider-wrap">
        <button
            type="button"
            class="dnk-sku-list__nav dnk-sku-list__prev"
            data-dnk-sku-prev
            aria-label="Предыдущие оттенки"
            hidden
        >
            <svg class="dnk-sku-list__nav-icon" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7 1L1 7L7 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div
            class="dnk-sku-list__slider swiper slider-solution swipeignore"
            data-plugin-options='<?= $swiperOptions ?>'
            role="list"
        >
            <div class="swiper-wrapper">
                <?php foreach ($arResult['ITEMS'] as $item): ?>
                    <?php
                    $itemName = htmlspecialcharsbx($item['SHADE_NAME'] ?? $item['NAME']);
                    $pictureSrc = (string) ($item['SHADE_PICTURE_SRC'] ?? $item['PICTURE_SRC'] ?? '');
                    $isCurrent = !empty($item['IS_CURRENT']);
                    ?>
                    <a
                        href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>"
                        class="dnk-sku-list__item swiper-slide<?= $isCurrent ? ' dnk-sku-list__item--current' : '' ?>"
                        role="listitem"
                        data-sku-name="<?= $itemName ?>"
                        title="<?= $itemName ?>"
                        <?= $isCurrent ? 'aria-current="page"' : '' ?>
                    >
                        <span class="dnk-sku-list__image-wrap" aria-hidden="true">
                            <?php if ($pictureSrc !== ''): ?>
                                <img
                                    src="<?= htmlspecialcharsbx($pictureSrc) ?>"
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
        <button
            type="button"
            class="dnk-sku-list__nav dnk-sku-list__next"
            data-dnk-sku-next
            aria-label="Следующие оттенки"
            hidden
        >
            <svg class="dnk-sku-list__nav-icon" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M1 1L7 7L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

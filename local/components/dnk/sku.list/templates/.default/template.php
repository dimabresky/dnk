<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult['ITEMS']) || empty($arResult['CURRENT_ITEM'])) {
    return;
}

$current = $arResult['CURRENT_ITEM'];
$uid = htmlspecialcharsbx($this->randString(8));

?>
<div class="dnk-sku-list" data-dnk-sku-list>
    <div class="dnk-sku-list__title">Вариант товара</div>
    <div class="dnk-sku-list__dropdown">
        <button
            type="button"
            class="dnk-sku-list__trigger"
            id="dnk-sku-list-trigger-<?= $uid ?>"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="dnk-sku-list-menu-<?= $uid ?>"
        >
            <span class="dnk-sku-list__row">
                <span class="dnk-sku-list__image-wrap" aria-hidden="true">
                    <?php if (!empty($current['PICTURE_SRC'])): ?>
                        <img src="<?= htmlspecialcharsbx($current['PICTURE_SRC']) ?>" alt="" class="dnk-sku-list__image" loading="lazy">
                    <?php else: ?>
                        <span class="dnk-sku-list__placeholder"></span>
                    <?php endif; ?>
                </span>
                <span class="dnk-sku-list__name"><?= htmlspecialcharsbx($current['NAME']) ?></span>
            </span>
            <span class="dnk-sku-list__caret" aria-hidden="true"></span>
        </button>
        <ul
            class="dnk-sku-list__menu"
            id="dnk-sku-list-menu-<?= $uid ?>"
            role="listbox"
            aria-hidden="true"
        >
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <li class="dnk-sku-list__menu-item" role="none">
                    <a
                        href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>"
                        class="dnk-sku-list__option<?= !empty($item['IS_CURRENT']) ? ' dnk-sku-list__option--current' : '' ?>"
                        role="option"
                        <?= !empty($item['IS_CURRENT']) ? 'aria-current="page"' : '' ?>
                        title="<?= htmlspecialcharsbx($item['NAME']) ?>"
                    >
                        <span class="dnk-sku-list__image-wrap">
                            <?php if (!empty($item['PICTURE_SRC'])): ?>
                                <img src="<?= htmlspecialcharsbx($item['PICTURE_SRC']) ?>" alt="" class="dnk-sku-list__image" loading="lazy">
                            <?php else: ?>
                                <span class="dnk-sku-list__placeholder"></span>
                            <?php endif; ?>
                        </span>
                        <span class="dnk-sku-list__name"><?= htmlspecialcharsbx($item['NAME']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php
\Bitrix\Main\Page\Asset::getInstance()->addJs($templateFolder . '/script.js');
?>

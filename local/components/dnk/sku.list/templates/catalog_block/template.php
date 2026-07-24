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
    'spaceBetween' => 8,
    'pagination' => false,
    'watchOverflow' => true,
]);

$rootModifierClass = ' dnk-sku-list--catalog-block';

/** @var string $componentPath */
$skuListPartial = $_SERVER['DOCUMENT_ROOT'] . $componentPath . '/partials/slider.php';

if (!is_file($skuListPartial)) {
    return;
}

?>
<div class="dnk-sku-list<?= $rootModifierClass ?>" data-dnk-sku-list>
<?php
include $skuListPartial;
?>
</div>

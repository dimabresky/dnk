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

?>
<div class="dnk-sku-list<?= $rootModifierClass ?>" data-dnk-sku-list>
<?php
include $componentPath . '/partials/slider.php';
?>
</div>

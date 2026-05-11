<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (isset($arParams['BACKURL']) && $arParams['BACKURL']) {
    $arResult['BACKURL'] = $arParams['BACKURL'];
    $backURL = 'backurl='.urlencode($arParams['BACKURL']);

    $arResult['AUTH_REGISTER_URL'] = $arParams['REGISTER_URL'].(strpos($arParams['REGISTER_URL'], '?') !== false ? '&' : '?').$backURL;
}

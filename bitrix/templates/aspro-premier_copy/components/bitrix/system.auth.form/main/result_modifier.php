<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (isset($_POST['USER_LOGIN']) && $_POST['USER_LOGIN']) {
    $arResult['USER_LOGIN'] = htmlspecialcharsbx($_POST['USER_LOGIN']);
}

if (isset($arParams['BACKURL']) && $arParams['BACKURL']) {
    $arResult['BACKURL'] = $arParams['BACKURL'];
    $backURL = 'backurl='.urlencode($arParams['BACKURL']);

    $arResult['AUTH_FORGOT_PASSWORD_URL'] = $arParams['FORGOT_PASSWORD_URL'].(strpos($arParams['FORGOT_PASSWORD_URL'], '?') !== false ? '&' : '?').$backURL;
    $arResult['AUTH_REGISTER_URL'] = $arParams['REGISTER_URL'].(strpos($arParams['REGISTER_URL'], '?') !== false ? '&' : '?').$backURL;
}

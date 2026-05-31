<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$path = $templateFolder;
CJSCore::RegisterExt('main_user_consent', [
    'js' => $path.'/user_consent.js',
    'css' => $path.'/user_consent.css',
    'lang' => $path.'/user_consent.php',
    'rel' => ['ui.design-tokens'],
]);
CUtil::InitJSCore(['popup', 'ajax', 'main_user_consent']);

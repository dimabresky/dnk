<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $this */
$ajaxMode = isset($templateData['BLOG']['BLOG_FROM_AJAX']) && $templateData['BLOG']['BLOG_FROM_AJAX'];
if (!$ajaxMode) {
    CJSCore::Init(['window', 'ajax']);
}

if (!class_exists('TSolution')) {
    include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php';
}

$arExtensions = ['catalog_comments', 'vote', 'chip'];
if ($arParams['NO_USE_IMAGE'] == 'N') {
    $arExtensions[] = 'drop';
}
TSolution\Extensions::init($arExtensions);

global $BLOG_DATA;
$BLOG_DATA = $arResult;

$application = Bitrix\Main\Application::getInstance();
$context = $application->getContext();
$request = $context->getRequest();
$session = $application->getSession();

if (isset($templateData['BLOG_USE']) && $templateData['BLOG_USE'] == 'Y') {
    if ($ajaxMode) {
        $arBlogCommentParams = [
            'AJAX_MODE' => 'Y',
            'AJAX_OPTION_HISTORY' => 'N',
            'AJAX_POST' => $arParams['AJAX_POST'],
            'BLOG_URL' => $arResult['BLOG_DATA']['BLOG_URL'],
            'CACHE_TIME' => $arParams['CACHE_TIME'],
            'CACHE_TYPE' => $arParams['CACHE_TYPE'],
            'COMMENTS_COUNT' => $arParams['COMMENTS_COUNT'],
            'DATE_TIME_FORMAT' => $DB->DateFormatToPhp(FORMAT_DATETIME),
            'ELEMENT_ID' => $templateData['BLOG']['AJAX_PARAMS']['ELEMENT_ID'] ?? $request['ELEMENT_ID'],
            'IBLOCK_ID' => $templateData['BLOG']['AJAX_PARAMS']['IBLOCK_ID'] ?? '',
            'ID' => $arResult['BLOG_DATA']['BLOG_POST_ID'],
            'MAX_IMAGE_COUNT' => $arParams['MAX_IMAGE_COUNT'],
            'NO_URL_IN_COMMENTS' => '',
            'NO_USE_IMAGE' => $arParams['NO_USE_IMAGE'],
            'NOT_USE_COMMENT_TITLE' => 'Y',
            'OFFER_ID' => $templateData['BLOG']['AJAX_PARAMS']['OFFER_ID'] ?? $request['OFFER_ID'],
            'PATH_TO_POST' => $arResult['URL_TO_COMMENT'],
            'PATH_TO_SMILE' => $arParams['PATH_TO_SMILE'],
            'RATING_TYPE' => $arParams['RATING_TYPE'],
            'REAL_CUSTOMER_TEXT' => $arParams['REAL_CUSTOMER_TEXT'],
            'REVIEW_COMMENT_REQUIRED' => $arParams['REVIEW_COMMENT_REQUIRED'],
            'REVIEW_FILTER_BUTTONS' => $arParams['REVIEW_FILTER_BUTTONS'],
            'SEO_USER' => 'N',
            'SHOW_RATING' => $arParams['SHOW_RATING'],
            'SHOW_SPAM' => $arParams['SHOW_SPAM'],
            'SIMPLE_COMMENT' => 'Y',
            'USE_FILTER' => $arParams['USE_FILTER'],
            'XML_ID' => $templateData['BLOG']['AJAX_PARAMS']['XML_ID'] ?? $request['XML_ID'],
        ];

        $APPLICATION->IncludeComponent(
            'aspro:blog.post.comment.premier',
            'adapt',
            $arBlogCommentParams,
            $this,
            ['HIDE_ICONS' => 'Y']
        );

        return;
    } else {
        $iblockID = $templateData['BLOG']['AJAX_PARAMS']['IBLOCK_ID'];
        $elementID = $templateData['BLOG']['AJAX_PARAMS']['ELEMENT_ID'];
        $session->set("IBLOCK_CATALOG_COMMENTS_PARAMS_{$iblockID}_{$elementID}", $templateData['BLOG']['AJAX_PARAMS']);

        if ($templateData['BLOG']['AJAX_PARAMS']['SHOW_RATING'] === 'Y') {
            ob_start();
            $APPLICATION->IncludeComponent(
                'bitrix:rating.vote',
                'standart_text',
                ['COMPONENT_TEMPLATE' => 'standart_text'],
                false
            );
            ob_end_clean();
        }
    }
}

if (!$ajaxMode) {
    if (isset($templateData['FB_USE']) && $templateData['FB_USE'] == 'Y') {
        if (isset($arParams['FB_USER_ADMIN_ID']) && strlen($arParams['FB_USER_ADMIN_ID']) > 0) {
            $APPLICATION->AddHeadString('<meta property="fb:admins" content="'.$arParams['FB_USER_ADMIN_ID'].'"/>');
        }

        if (isset($arParams['FB_APP_ID']) && $arParams['FB_APP_ID'] != '') {
            $APPLICATION->AddHeadString('<meta property="fb:app_id" content="'.$arParams['FB_APP_ID'].'"/>');
        }
    }

    if (isset($templateData['TEMPLATE_THEME'])) {
        $APPLICATION->SetAdditionalCSS($templateData['TEMPLATE_THEME']);
    }
}

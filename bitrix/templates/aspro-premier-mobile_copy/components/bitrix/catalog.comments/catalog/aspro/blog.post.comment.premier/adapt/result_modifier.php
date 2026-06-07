<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

global $USER_FIELD_MANAGER;

require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php';

include 'functions.php';

$application = Bitrix\Main\Application::getInstance();
$request = $application->getContext()->getRequest();
$session = $application->getSession();

if (!empty($arParams['UPLOAD_FILE_PARAMS'])) {
    __MPF_ImageResizeHandler(null, $arParams['UPLOAD_FILE_PARAMS']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($request['mfi_mode']) && ($request['mfi_mode'] == 'upload')) {
    AddEventHandler('main', 'main.file.input.upload', '__MPF_ImageResizeHandler');
}

createField('BLOG_COMMENT', 'UF_ASPRO_COM_LIKE', 'integer');
createField('BLOG_COMMENT', 'UF_ASPRO_COM_RATING', 'integer');
createField('BLOG_COMMENT', 'UF_ASPRO_COM_DISLIKE', 'integer');
createField('BLOG_COMMENT', 'UF_ASPRO_COM_APPROVE', 'boolean');
createField('BLOG_COMMENT', 'UF_ASPRO_COM_OFFER_ID', 'integer');

$arParams['SORT_PROP'] = $session['REVIEW_SORT_PROP'] ?: 'UF_ASPRO_COM_RATING';
$arParams['SORT_ORDER'] = $session['REVIEW_SORT_ORDER'] ?: 'SORT_DESC';

if (
    isset($request['approve_comment_id']) && $request['approve_comment_id']
    || isset($request['unapprove_comment_id']) && $request['unapprove_comment_id']
) {
    $commentID = $request['approve_comment_id'] ?? $request['unapprove_comment_id'];
    $status = isset($request['approve_comment_id']);

    CBlogComment::Update($commentID, ['UF_ASPRO_COM_APPROVE' => $status]);
    $key = array_search($commentID, array_column($arResult['CommentsResult'][0], 'ID'));
    $arResult['is_ajax_post'] = 'Y';
    $arResult['CommentsResult'] = [[$commentID => $arResult['CommentsResult'][0][$key]]];
}

$arOrder = [];
$arFilter = [
    'POST_ID' => $arParams['ID'],
    'BLOG_ID' => $arResult['Blog']['ID'],
    'PUBLISH_STATUS' => 'P',
    'PARENT_ID' => '',
];
$arSelectedFields = [
    'ID',
    'PARENT_ID',
    'UF_ASPRO_COM_RATING',
    'UF_ASPRO_COM_LIKE',
    'UF_ASPRO_COM_DISLIKE',
    'UF_ASPRO_COM_APPROVE',
    'UF_ASPRO_COM_OFFER_ID',
];

$dbComment = CBlogComment::GetList($arOrder, $arFilter, false, false, $arSelectedFields);
$arResult['IMAGES'] = $arDBComments = [];

while ($comment = $dbComment->Fetch()) {
    if ($comment['UF_ASPRO_COM_RATING']) {
        $arDBComments[$comment['ID']]['UF_ASPRO_COM_RATING'] = $comment['UF_ASPRO_COM_RATING'];
    }

    if ($comment['UF_ASPRO_COM_LIKE']) {
        $arDBComments[$comment['ID']]['UF_ASPRO_COM_LIKE'] = $comment['UF_ASPRO_COM_LIKE'];
    }

    if ($comment['UF_ASPRO_COM_DISLIKE']) {
        $arDBComments[$comment['ID']]['UF_ASPRO_COM_DISLIKE'] = $comment['UF_ASPRO_COM_DISLIKE'];
    }

    if ($comment['UF_ASPRO_COM_OFFER_ID']) {
        $arDBComments[$comment['ID']]['UF_ASPRO_COM_OFFER_ID'] = $comment['UF_ASPRO_COM_OFFER_ID'];
    }

    $arDBComments[$comment['ID']]['UF_ASPRO_COM_APPROVE'] = $comment['UF_ASPRO_COM_APPROVE'];

    ++$arResult['REVIEWS_COUNT'];
}

if ($arResult['PagesComment']) {
    $pagesCount = count($arResult['PagesComment']);
    $countOnPage = $arParams['COMMENTS_COUNT'];

    $arSortedComments = TSolution\Comment\Review::processComments($arResult, $arParams, $arResult['PagesComment'], $arDBComments);
    if ($arSortedComments) {
        $arResult['REVIEWS_COUNT_TEXT'] = TSolution\Functions::declOfNum($arResult['REVIEWS_COUNT'], [
            GetMessage('ONE_REVIEW'),
            GetMessage('TWO_REVIEWS'),
            GetMessage('FIVE_REVIEWS'),
        ]);

        if ($arParams['SORT_PROP'] == 'DateFormated') {
            $result = $arParams['SORT_ORDER'] !== 'SORT_DESC'
                ? usort($arSortedComments, fn ($a, $b) => strtotime($a['DateFormated']) - strtotime($b['DateFormated']))
                : usort($arSortedComments, fn ($a, $b) => strtotime($b['DateFormated']) - strtotime($a['DateFormated']));
        } else {
            $arParams['SORT_ORDER'] = $arParams['SORT_ORDER'] == 'SORT_DESC' ? SORT_DESC : SORT_ASC;
            Bitrix\Main\Type\Collection::sortByColumn($arSortedComments, [$arParams['SORT_PROP'] => [$arParams['SORT_ORDER']], 'HAS_COMMENT' => [SORT_DESC], 'DATE_CREATE' => [SORT_DESC]]);
        }

        $newPagesCount = floor(count($arSortedComments) / $countOnPage);
        $pagesCount = $pagesCount !== $newPagesCount ? $newPagesCount : $pagesCount;

        if (count($arResult['CommentsResult'][0]) >= count($arSortedComments)) {
            $pagesCount = floor(count($arSortedComments) / $countOnPage);
            $pagesCount = $pagesCount ?: 1;
        }

        for ($i = 0; $i < $pagesCount; ++$i) {
            $arResult['PagesComment'][$i + 1] = array_slice($arSortedComments, $countOnPage * $i, $countOnPage);
        }

        $arResult['PAGE_COUNT'] = $pagesCount;
        if ($pagesCount <= 1) {
            $arResult['NEED_NAV'] = 'N';
        }

        $arResult['CommentsResult'][0] = $arResult['PagesComment'][$arParams['PAGEN']];
    } elseif ($arParams['USE_FILTER'] === 'Y' && isset($session['REVIEW_FILTER'])) {
        $arResult['CommentsResult'] = $arResult['PagesComment'] = [];

        if (count($arResult['Comments'])) {
            $arResult['COMMENT_ERROR'] = GetMessage('FILTER_NO_RESULT_FOUND');
            $arResult['COMMENT_ERROR_TYPE'] = 'FILTER';
        }
        $arResult['NEED_NAV'] = 'N';
    }
} else {
    $arSortedComments = TSolution\Comment\Review::processComments($arResult, $arParams, $arResult['CommentsResult'], $arDBComments);
    if ($arSortedComments) {
        $arResult['REVIEWS_COUNT_TEXT'] = TSolution\Functions::declOfNum($arResult['REVIEWS_COUNT'], [
            GetMessage('ONE_REVIEW'),
            GetMessage('TWO_REVIEWS'),
            GetMessage('FIVE_REVIEWS'),
        ]);

        if ($arParams['SORT_PROP'] == 'DateFormated') {
            $result = $arParams['SORT_ORDER'] !== 'SORT_DESC'
                ? usort($arSortedComments, fn ($a, $b) => strtotime($a['DateFormated']) - strtotime($b['DateFormated']))
                : usort($arSortedComments, fn ($a, $b) => strtotime($b['DateFormated']) - strtotime($a['DateFormated']));
        } else {
            $arParams['SORT_ORDER'] = $arParams['SORT_ORDER'] == 'SORT_DESC' ? SORT_DESC : SORT_ASC;
            Bitrix\Main\Type\Collection::sortByColumn($arSortedComments, [$arParams['SORT_PROP'] => [$arParams['SORT_ORDER']], 'HAS_COMMENT' => [SORT_DESC], 'DATE_CREATE' => [SORT_DESC]]);
        }

        $arResult['CommentsResult'][0] = $arSortedComments;
    } elseif ($arParams['USE_FILTER'] === 'Y' && isset($session['REVIEW_FILTER'])) {
        $arResult['CommentsResult'] = [];

        if (count($arResult['Comments'])) {
            $arResult['COMMENT_ERROR'] = GetMessage('FILTER_NO_RESULT_FOUND');
            $arResult['COMMENT_ERROR_TYPE'] = 'FILTER';
        }
    }
}

<?php

/** @global CMain $APPLICATION */
define('NO_KEEP_STATISTIC', true);
define('PUBLIC_AJAX_MODE', true);
define('NOT_CHECK_PERMISSIONS', true);

if (isset($_REQUEST['SITE_ID']) && !empty($_REQUEST['SITE_ID'])) {
    $site_id = htmlspecialchars($_REQUEST['SITE_ID'], ENT_COMPAT, defined('BX_UTF') ? 'UTF-8' : 'ISO-8859-1');

    if (!is_string($site_id)) {
        exit;
    }

    if (preg_match('/^[a-z0-9_]{2}$/i', $site_id) === 1) {
        define('SITE_ID', $site_id);
    }
} else {
    exit;
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

$application = Bitrix\Main\Application::getInstance();
$context = $application->getContext();
$request = $context->getRequest();
$session = $application->getSession();

if (check_bitrix_sessid()) {
    $commParams = [];

    if (isset($request['IBLOCK_ID']) && isset($request['ELEMENT_ID'])) {
        $iblockID = (int) $request['IBLOCK_ID'];
        $elementID = (int) $request['ELEMENT_ID'];

        if ($iblockID > 0 && $elementID > 0) {
            $paramsId = 'IBLOCK_CATALOG_COMMENTS_PARAMS_'.$iblockID.'_'.$elementID;

            if (!empty($session[$paramsId]) && is_array($session[$paramsId])) {
                $commParams = $session[$paramsId];
                $commParams['ELEMENT_ID'] = $request['ELEMENT_ID'];
                $commParams['USE_FILTER'] = $request['act'] ? 'N' : 'Y';
                if ($request['OFFER_ID']) {
                    $commParams['OFFER_ID'] = $request['OFFER_ID'];
                }
            }
        }
    }

    if (!empty($commParams)) {
        $APPLICATION->IncludeComponent(
            'bitrix:catalog.comments',
            'catalog',
            $commParams,
            null,
            ['HIDE_ICONS' => 'Y']
        );
    }
}
exit;

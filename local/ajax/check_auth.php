<?php

define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $USER;

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'success' => false,
    'authorized' => false,
    'error' => '',
];

if (!check_bitrix_sessid()) {
    $response['error'] = 'Invalid sessid';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$userId = 0;

if (
    is_object($USER)
    && $USER->IsAuthorized()
) {
    $userId = (int) $USER->GetID();
}

$rememberAuth = (($_POST['USER_REMEMBER'] ?? '') === 'Y');

if (
    $userId > 0
    && $rememberAuth
) {
    $USER->Authorize($userId, true);
}

$response = [
    'success' => true,
    'authorized' => $userId > 0,
    'remembered' => $userId > 0 && $rememberAuth,
];

echo json_encode($response);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

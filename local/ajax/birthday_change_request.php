<?php

define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Engine\CurrentUser;
use Dnk\PhpInterface\Utils;

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'success' => false,
    'error' => '',
];

if (!check_bitrix_sessid()) {
    $response['error'] = 'Invalid sessid';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$userId = (int)CurrentUser::get()->getId();

if ($userId <= 0) {
    $response['error'] = 'Unauthorized';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

if (!Utils::sendBirthdayChangeRequestMail($userId)) {
    $response['error'] = 'Send failed';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$response = [
    'success' => true,
    'error' => '',
];

echo json_encode($response);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

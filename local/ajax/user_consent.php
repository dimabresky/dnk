<?php

define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Dnk\PhpInterface\UserConsentService;

header('Content-Type: application/json; charset=UTF-8');

$response = ['success' => false, 'error' => ''];

if (!check_bitrix_sessid()) {
    $response['error'] = 'Invalid sessid';
    echo json_encode($response);
    return;
}

$userId = (int)CurrentUser::get()->getId();

$request = Context::getCurrent()->getRequest();
$action = (string)$request->getPost('action');
$agreementId = (int)$request->getPost('agreement_id');

if ($agreementId <= 0) {
    $response['error'] = 'Invalid agreement_id';
    echo json_encode($response);
    return;
}

if ($userId <= 0 && $action === 'restore') {
    $response['success'] = true;
    echo json_encode($response);
    return;
}

if ($userId <= 0) {
    $response['error'] = 'Unauthorized';
    echo json_encode($response);
    return;
}

switch ($action) {
    case 'revoke':
        $response['success'] = UserConsentService::revoke($userId, $agreementId);
        if (!$response['success']) {
            $response['error'] = 'Revoke failed';
        }
        break;

    case 'restore':
        $source = (string)$request->getPost('source');
        $response['success'] = UserConsentService::restoreAfterAccept($userId, $agreementId, $source);
        if (!$response['success']) {
            $response['error'] = 'Restore failed';
        }
        break;

    default:
        $response['error'] = 'Unknown action';
        break;
}

echo json_encode($response);

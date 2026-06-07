<?php

define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use CUser;

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'success' => false,
    'activated' => false,
    'error' => '',
];

if (!check_bitrix_sessid()) {
    $response['error'] = 'Invalid sessid';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$phoneNumber = UserPhoneAuthTable::normalizePhoneNumber((string) ($_POST['USER_PHONE_NUMBER'] ?? ''));
$smsCode = trim((string) ($_POST['SMS_CODE'] ?? ''));

if ($phoneNumber === '' || $smsCode === '') {
    $response['error'] = 'Invalid params';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$phoneAuthRow = UserPhoneAuthTable::getList([
    'select' => ['USER_ID'],
    'filter' => ['=PHONE_NUMBER' => $phoneNumber],
    'limit' => 1,
])->fetch();

if (!is_array($phoneAuthRow) || (int) ($phoneAuthRow['USER_ID'] ?? 0) <= 0) {
    $response['error'] = 'User not found';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$userIdFromPhone = (int) $phoneAuthRow['USER_ID'];
$userIdVerified = (int) CUser::VerifyPhoneCode($phoneNumber, $smsCode);

if ($userIdVerified <= 0 || $userIdVerified !== $userIdFromPhone) {
    $response['error'] = 'Invalid code';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$userRow = UserTable::getRow([
    'select' => ['ID', 'ACTIVE'],
    'filter' => ['=ID' => $userIdVerified],
]);

if (!is_array($userRow)) {
    $response['error'] = 'User not found';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$activated = false;
$isActive = (string) ($userRow['ACTIVE'] ?? 'N') === 'Y';

if (!$isActive) {
    $cUser = new CUser();
    if ($cUser->Update($userIdVerified, ['ACTIVE' => 'Y'])) {
        $activated = true;
    } else {
        $response['error'] = trim(strip_tags((string) $cUser->LAST_ERROR)) ?: 'Activation failed';
        echo json_encode($response);
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
        return;
    }
}

$response = [
    'success' => true,
    'activated' => $activated,
    'error' => '',
];

echo json_encode($response);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

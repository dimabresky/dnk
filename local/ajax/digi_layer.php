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
use Bitrix\Main\SystemException;
use Dnk\PhpInterface\DigiLayerService;

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'success' => false,
    'result' => false,
    'error' => '',
];

if (!check_bitrix_sessid()) {
    $response['error'] = 'Invalid sessid';
    echo json_encode($response);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    return;
}

$request = Context::getCurrent()->getRequest();
$action = (string) $request->getPost('action');
$offerId = (int) $request->getPost('offer_id');
$amountRaw = $request->getPost('amount');
$amount = ($amountRaw === null || $amountRaw === '') ? 1.0 : (float) $amountRaw;

$stateOnlyActions = [
    'cartState',
    'favoritesState',
    'comparesState',
    'compareState',
];

$mutationActions = [
    'addToCart',
    'removeFromCart',
    'addToFavorites',
    'removeFromFavorites',
    'addToCompare',
    'removeFromCompare',
];

try {
    if (in_array($action, $stateOnlyActions, true)) {
        $snapshot = DigiLayerService::snapshot();
        $response['success'] = true;

        if ($action === 'cartState') {
            $response['result'] = $snapshot['cart'];
        } elseif ($action === 'favoritesState') {
            $response['result'] = $snapshot['favorites'];
        } else {
            $response['result'] = $snapshot['compares'];
        }

        $response['cart'] = $snapshot['cart'];
        $response['favorites'] = $snapshot['favorites'];
        $response['compares'] = $snapshot['compares'];
    } elseif (in_array($action, $mutationActions, true)) {
        switch ($action) {
            case 'addToCart':
                DigiLayerService::addToCart($offerId, $amount);
                break;
            case 'removeFromCart':
                DigiLayerService::removeFromCart($offerId, $amount);
                break;
            case 'addToFavorites':
                DigiLayerService::addToFavorites($offerId);
                break;
            case 'removeFromFavorites':
                DigiLayerService::removeFromFavorites($offerId);
                break;
            case 'addToCompare':
                DigiLayerService::addToCompare($offerId);
                break;
            case 'removeFromCompare':
                DigiLayerService::removeFromCompare($offerId);
                break;
        }

        $snapshot = DigiLayerService::snapshot();
        $response['success'] = true;
        $response['result'] = true;
        $response['cart'] = $snapshot['cart'];
        $response['favorites'] = $snapshot['favorites'];
        $response['compares'] = $snapshot['compares'];
    } else {
        $response['error'] = 'Unknown action';
    }
} catch (SystemException $e) {
    $response['error'] = $e->getMessage();
} catch (\Throwable $e) {
    $response['error'] = 'Internal error';
    error_log('digi_layer.php: ' . $e->getMessage());
}

echo json_encode($response);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';

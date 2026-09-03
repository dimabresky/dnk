<?php

define('STOP_STATISTICS', true);
define('NOT_CHECK_PERMISSIONS', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\SystemException;
use Dnk\PhpInterface\DigiLayerService;

/**
 * Sends D7 AjaxJson response and terminates the request.
 *
 * @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/httpresponse/ajaxjson.php
 */
function digiLayerSendResponse(AjaxJson $response): void
{
    $application = Application::getInstance();
    $application->getContext()->setResponse($response);
    $application->end();
}

/**
 * Builds AjaxJson error response with a single message.
 */
function digiLayerErrorResponse(string $message, string $code = ''): AjaxJson
{
    $errors = new ErrorCollection();
    $errors->setError(new Error($message, $code));

    return AjaxJson::createError($errors);
}

if (!check_bitrix_sessid()) {
    $sessidErrors = new ErrorCollection();
    $sessidErrors->setError(new Error('Invalid sessid', 'invalid_sessid'));
    digiLayerSendResponse(AjaxJson::createDenied($sessidErrors));
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

        if ($action === 'cartState') {
            $result = $snapshot['cart'];
        } elseif ($action === 'favoritesState') {
            $result = $snapshot['favorites'];
        } else {
            $result = $snapshot['compares'];
        }

        digiLayerSendResponse(AjaxJson::createSuccess([
            'result' => $result,
            'cart' => $snapshot['cart'],
            'favorites' => $snapshot['favorites'],
            'compares' => $snapshot['compares'],
        ]));
    }

    if (in_array($action, $mutationActions, true)) {
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

        digiLayerSendResponse(AjaxJson::createSuccess([
            'result' => true,
            'cart' => $snapshot['cart'],
            'favorites' => $snapshot['favorites'],
            'compares' => $snapshot['compares'],
        ]));
    }

    digiLayerSendResponse(digiLayerErrorResponse('Unknown action', 'unknown_action'));
} catch (SystemException $e) {
    digiLayerSendResponse(digiLayerErrorResponse($e->getMessage(), 'system'));
} catch (\Throwable $e) {
    error_log('digi_layer.php: ' . $e->getMessage());
    digiLayerSendResponse(digiLayerErrorResponse('Internal error', 'internal'));
}

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Dnk\PhpInterface\UserConsentService;

$arResult['CONSENT_ALREADY_GIVEN'] = false;

global $USER;

if (!is_object($USER) || !$USER->IsAuthorized()) {
    return;
}

$agreementId = (int)($arParams['ID'] ?? 0);
if ($agreementId <= 0) {
    return;
}

$userId = (int)$USER->GetID();
$arResult['CONSENT_ALREADY_GIVEN'] = UserConsentService::hasActiveConsent($userId, $agreementId);

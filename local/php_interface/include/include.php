<?php

use Bitrix\Main\Loader;
use Dnk\PhpInterface\BonusAccrualEvents;
use Dnk\PhpInterface\CertificateBuyPhoneAuth;
use Dnk\PhpInterface\CertificateRequestStatus;
use Dnk\PhpInterface\BonusBalanceQueueAgent;
use Dnk\PhpInterface\HeaderPromoEvents;
use Dnk\PhpInterface\IblockProductBrandEvents;
use Dnk\PhpInterface\IblockProductMarkerHitEvents;
use Dnk\PhpInterface\IblockProductMarkerIsNewEvents;
use Dnk\PhpInterface\BonusBalanceQueueTable;
use Dnk\PhpInterface\BonusFetchAgent;
use Dnk\PhpInterface\OrderExportEvents;
use Dnk\PhpInterface\OrderExportQueueAgent;
use Dnk\PhpInterface\OrderExportQueueTable;
use Dnk\PhpInterface\UserAddEvents;
use Dnk\PhpInterface\UserRegisterExportQueueAgent;
use Dnk\PhpInterface\UserConsentEvents;
use Dnk\PhpInterface\UserConsentRevokeTable;
use Dnk\PhpInterface\UserConsentService;
use Dnk\PhpInterface\UserRegisterExportQueueTable;
use Dnk\PhpInterface\UserReauthorizeQueueTable;
use Dnk\PhpInterface\Utils;

$includeDir = __DIR__;
$classesPath = '/local/php_interface/include/classes';

if (is_file($includeDir . '/constants.php')) {
    require_once $includeDir . '/constants.php';
}

Loader::registerAutoLoadClasses(null, [
    Utils::class => $classesPath . '/Utils.php',
    CertificateBuyPhoneAuth::class => $classesPath . '/CertificateBuyPhoneAuth.php',
    CertificateRequestStatus::class => $classesPath . '/CertificateRequestStatus.php',
    BonusAccrualEvents::class => $classesPath . '/BonusAccrualEvents.php',
    HeaderPromoEvents::class => $classesPath . '/HeaderPromoEvents.php',
    BonusFetchAgent::class => $classesPath . '/BonusFetchAgent.php',
    BonusBalanceQueueTable::class => $classesPath . '/BonusBalanceQueueTable.php',
    BonusBalanceQueueAgent::class => $classesPath . '/BonusBalanceQueueAgent.php',
    OrderExportQueueTable::class => $classesPath . '/OrderExportQueueTable.php',
    OrderExportQueueAgent::class => $classesPath . '/OrderExportQueueAgent.php',
    OrderExportEvents::class => $classesPath . '/OrderExportEvents.php',
    UserAddEvents::class => $classesPath . '/UserAddEvents.php',
    IblockProductBrandEvents::class => $classesPath . '/IblockProductBrandEvents.php',
    IblockProductMarkerHitEvents::class => $classesPath . '/IblockProductMarkerHitEvents.php',
    IblockProductMarkerIsNewEvents::class => $classesPath . '/IblockProductMarkerIsNewEvents.php',
    UserRegisterExportQueueTable::class => $classesPath . '/UserRegisterExportQueueTable.php',
    UserRegisterExportQueueAgent::class => $classesPath . '/UserRegisterExportQueueAgent.php',
    UserReauthorizeQueueTable::class => $classesPath . '/UserReauthorizeQueueTable.php',
    UserConsentRevokeTable::class => $classesPath . '/UserConsentRevokeTable.php',
    UserConsentService::class => $classesPath . '/UserConsentService.php',
    UserConsentEvents::class => $classesPath . '/UserConsentEvents.php',
]);


if (is_file($includeDir . '/events.php')) {
    require_once $includeDir . '/events.php';
}

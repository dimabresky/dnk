<?php

use Bitrix\Main\Loader;
use Dnk\PhpInterface\BasketBonusEvents;
use Dnk\PhpInterface\BasketBonusService;
use Dnk\PhpInterface\BonusAccrualEvents;
use Dnk\PhpInterface\BonusDisplayEvents;
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
use Dnk\PhpInterface\ProductExtendedReviewsAgent;
use Dnk\PhpInterface\ProductFeedAgent;
use Dnk\PhpInterface\ProfileBirthdayEvents;
use Dnk\PhpInterface\UserAddEvents;
use Dnk\PhpInterface\UserRegisterExportQueueAgent;
use Dnk\PhpInterface\BlogCommentConsentEvents;
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
    BasketBonusService::class => $classesPath . '/BasketBonusService.php',
    BasketBonusEvents::class => $classesPath . '/BasketBonusEvents.php',
    CertificateBuyPhoneAuth::class => $classesPath . '/CertificateBuyPhoneAuth.php',
    CertificateRequestStatus::class => $classesPath . '/CertificateRequestStatus.php',
    BonusAccrualEvents::class => $classesPath . '/BonusAccrualEvents.php',
    BonusDisplayEvents::class => $classesPath . '/BonusDisplayEvents.php',
    HeaderPromoEvents::class => $classesPath . '/HeaderPromoEvents.php',
    BonusFetchAgent::class => $classesPath . '/BonusFetchAgent.php',
    BonusBalanceQueueTable::class => $classesPath . '/BonusBalanceQueueTable.php',
    BonusBalanceQueueAgent::class => $classesPath . '/BonusBalanceQueueAgent.php',
    OrderExportQueueTable::class => $classesPath . '/OrderExportQueueTable.php',
    OrderExportQueueAgent::class => $classesPath . '/OrderExportQueueAgent.php',
    OrderExportEvents::class => $classesPath . '/OrderExportEvents.php',
    ProductExtendedReviewsAgent::class => $classesPath . '/ProductExtendedReviewsAgent.php',
    ProductFeedAgent::class => $classesPath . '/ProductFeedAgent.php',
    UserAddEvents::class => $classesPath . '/UserAddEvents.php',
    ProfileBirthdayEvents::class => $classesPath . '/ProfileBirthdayEvents.php',
    IblockProductBrandEvents::class => $classesPath . '/IblockProductBrandEvents.php',
    IblockProductMarkerHitEvents::class => $classesPath . '/IblockProductMarkerHitEvents.php',
    IblockProductMarkerIsNewEvents::class => $classesPath . '/IblockProductMarkerIsNewEvents.php',
    UserRegisterExportQueueTable::class => $classesPath . '/UserRegisterExportQueueTable.php',
    UserRegisterExportQueueAgent::class => $classesPath . '/UserRegisterExportQueueAgent.php',
    UserReauthorizeQueueTable::class => $classesPath . '/UserReauthorizeQueueTable.php',
    UserConsentRevokeTable::class => $classesPath . '/UserConsentRevokeTable.php',
    UserConsentService::class => $classesPath . '/UserConsentService.php',
    UserConsentEvents::class => $classesPath . '/UserConsentEvents.php',
    BlogCommentConsentEvents::class => $classesPath . '/BlogCommentConsentEvents.php',
]);


if (is_file($includeDir . '/events.php')) {
    require_once $includeDir . '/events.php';
}

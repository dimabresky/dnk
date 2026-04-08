<?php

use Bitrix\Main\Loader;
use Dnk\PhpInterface\BonusBalanceQueueAgent;
use Dnk\PhpInterface\BonusBalanceQueueTable;
use Dnk\PhpInterface\BonusFetchAgent;
use Dnk\PhpInterface\OrderExportEvents;
use Dnk\PhpInterface\OrderExportQueueAgent;
use Dnk\PhpInterface\OrderExportQueueTable;
use Dnk\PhpInterface\UserAddEvents;
use Dnk\PhpInterface\UserRegisterExportQueueAgent;
use Dnk\PhpInterface\UserRegisterExportQueueTable;
use Dnk\PhpInterface\Utils;

$includeDir = __DIR__;
$classesPath = '/local/php_interface/include/classes';

if (is_file($includeDir . '/constants.php')) {
    require_once $includeDir . '/constants.php';
}

Loader::registerAutoLoadClasses(null, [
    Utils::class => $classesPath . '/Utils.php',
    BonusFetchAgent::class => $classesPath . '/BonusFetchAgent.php',
    BonusBalanceQueueTable::class => $classesPath . '/BonusBalanceQueueTable.php',
    BonusBalanceQueueAgent::class => $classesPath . '/BonusBalanceQueueAgent.php',
    OrderExportQueueTable::class => $classesPath . '/OrderExportQueueTable.php',
    OrderExportQueueAgent::class => $classesPath . '/OrderExportQueueAgent.php',
    OrderExportEvents::class => $classesPath . '/OrderExportEvents.php',
    UserAddEvents::class => $classesPath . '/UserAddEvents.php',
    UserRegisterExportQueueTable::class => $classesPath . '/UserRegisterExportQueueTable.php',
    UserRegisterExportQueueAgent::class => $classesPath . '/UserRegisterExportQueueAgent.php',
]);


if (is_file($includeDir . '/events.php')) {
    require_once $includeDir . '/events.php';
}

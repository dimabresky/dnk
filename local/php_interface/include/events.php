<?php

use Bitrix\Main\EventManager;
use Dnk\PhpInterface\BasketBonusEvents;
use Dnk\PhpInterface\BonusAccrualEvents;
use Dnk\PhpInterface\BonusDisplayEvents;
use Dnk\PhpInterface\HeaderPromoEvents;
use Dnk\PhpInterface\IblockProductBrandEvents;
use Dnk\PhpInterface\IblockProductMarkerHitEvents;
use Dnk\PhpInterface\IblockProductMarkerIsNewEvents;
use Dnk\PhpInterface\OrderExportEvents;
use Dnk\PhpInterface\UserAddEvents;
use Dnk\PhpInterface\UserConsentEvents;
use Bitrix\Main\UserConsent\Internals\ConsentTable;

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [OrderExportEvents::class, 'onSaleOrderSaved']
);

BasketBonusEvents::register();

EventManager::getInstance()->addEventHandler(
    'aspro.bonus',
    'beforeCreateAddByOrder',
    [BonusAccrualEvents::class, 'onBeforeCreateAddByOrder']
);

BonusDisplayEvents::register();

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnBeforeUserAdd',
    [UserAddEvents::class, 'onBeforeUserSave']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnBeforeUserUpdate',
    [UserAddEvents::class, 'onBeforeUserSave']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnAfterUserAdd',
    [UserAddEvents::class, 'onAfterUserAdd']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnAfterUserUpdate',
    [UserAddEvents::class, 'onAfterUserUpdate']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnAfterUserAuthorize',
    [UserAddEvents::class, 'onAfterUserAuthorize']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnAfterUserLogin',
    [UserAddEvents::class, 'onAfterUserAuthorize']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnAfterUserRegister',
    [UserAddEvents::class, 'onAfterUserRegister']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'main',
    'OnEndBufferContent',
    [HeaderPromoEvents::class, 'onEndBufferContent']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'iblock',
    'OnAfterIBlockElementAdd',
    [IblockProductBrandEvents::class, 'onAfterIBlockElementAdd']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'iblock',
    'OnAfterIBlockElementUpdate',
    [IblockProductBrandEvents::class, 'onAfterIBlockElementUpdate']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'iblock',
    'OnAfterIBlockElementAdd',
    [IblockProductMarkerHitEvents::class, 'onAfterIBlockElementAdd']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'iblock',
    'OnAfterIBlockElementUpdate',
    [IblockProductMarkerHitEvents::class, 'onAfterIBlockElementUpdate']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'iblock',
    'OnAfterIBlockElementAdd',
    [IblockProductMarkerIsNewEvents::class, 'onAfterIBlockElementAdd']
);

EventManager::getInstance()->addEventHandlerCompatible(
    'iblock',
    'OnAfterIBlockElementUpdate',
    [IblockProductMarkerIsNewEvents::class, 'onAfterIBlockElementUpdate']
);

EventManager::getInstance()->addEventHandler(
    'main',
    'OnUserConsentProviderList',
    [UserConsentEvents::class, 'onUserConsentProviderList']
);

EventManager::getInstance()->addEventHandler(
    '',
    ConsentTable::class,
    'OnAfterAdd',
    [UserConsentEvents::class, 'onConsentAfterAdd']
);

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [UserConsentEvents::class, 'onSaleOrderSaved']
);

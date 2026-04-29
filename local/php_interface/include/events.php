<?php

use Bitrix\Main\EventManager;
use Dnk\PhpInterface\HeaderPromoEvents;
use Dnk\PhpInterface\IblockProductBrandEvents;
use Dnk\PhpInterface\IblockProductMarkerHitEvents;
use Dnk\PhpInterface\OrderExportEvents;
use Dnk\PhpInterface\UserAddEvents;

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [OrderExportEvents::class, 'onSaleOrderSaved']
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

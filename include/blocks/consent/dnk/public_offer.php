<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (TSolution::GetFrontParametrValue('OFFER_LICENCE_TYPE') === 'BITRIX') {
    include __DIR__ . '/public_offer_bxconsent.php';
} else {
    include __DIR__ . '/../public_offer_solution.php';
}

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (TSolution::GetFrontParametrValue('LICENCE_TYPE') === 'BITRIX') {
    include __DIR__ . '/bxconsent.php';
} else {
    include __DIR__ . '/solution.php';
}

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (TSolution::GetFrontParametrValue('THIRD_PARTIES_LICENCE_TYPE') === 'BITRIX') {
    include __DIR__ . '/third_parties_bxconsent.php';
} else {
    include __DIR__ . '/../third_parties_solution.php';
}

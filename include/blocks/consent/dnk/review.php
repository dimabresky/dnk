<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (TSolution::GetFrontParametrValue('REVIEW_LICENCE_TYPE') === 'BITRIX') {
    include __DIR__ . '/review_bxconsent.php';
} else {
    include __DIR__ . '/../licence_as_text.php';
}

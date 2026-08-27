<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Reuse stock Aspro Premier social markup after result_modifier filters
 * the list down to Telegram and Viber.
 */
$defaultTemplate = $_SERVER['DOCUMENT_ROOT']
    . '/bitrix/components/aspro/social.info.premier/templates/.default/template.php';

if (is_file($defaultTemplate)) {
    include $defaultTemplate;
}

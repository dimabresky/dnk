<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

global $searchQuery;
$searchQuery = $arResult['REQUEST']['QUERY']; // to use this variable in catalog.search`s template
?>

<?$GLOBALS['APPLICATION']->IncludeComponent(
    'aspro:smartsearch.image',
    '',
    [],
    false,
    ['HIDE_ICONS' => 'Y']
); ?>

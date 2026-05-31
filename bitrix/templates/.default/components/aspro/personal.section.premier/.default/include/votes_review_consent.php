<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (TSolution::GetFrontParametrValue('SHOW_LICENCE') !== 'Y') {
    return;
}

TSolution\Functions::showBlockHtml([
    'FILE' => 'consent/dnk/review.php',
    'PARAMS' => [
        'OPTION_CODE' => 'AGREEMENT_REVIEW',
        'SUBMIT_TEXT' => GetMessage('SPS_MAIN_BLOCK_TITLE_VOTES'),
        'REPLACE_FIELDS' => [],
        'INPUT_NAME' => 'licenses_review',
        'INPUT_ID' => 'licenses_review',
    ],
]);

<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

global $arTheme, $APPLICATION;
$APPLICATION->AddViewContent('right_block_class', 'catalog_page ');

$bShowLeftBlock = ($arTheme['LEFT_BLOCK_CATALOG_ROOT']['VALUE'] === 'Y' && !defined('ERROR_404'));
$APPLICATION->SetPageProperty('MENU', 'N');
include 'section.php';
?>

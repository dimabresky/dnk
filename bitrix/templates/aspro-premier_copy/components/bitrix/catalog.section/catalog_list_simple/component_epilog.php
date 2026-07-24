<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $templateData */
/** @var @global CMain $APPLICATION */
use Bitrix\Main\Loader;

$arExtensions = ['catalog', 'notice', 'prices', 'stickers', 'hint'];

TSolution\Extensions::init($arExtensions);
?>
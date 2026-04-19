<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arScripts = ['dropdown_select', 'collaps'];
if(TSolution::getFrontParametrValue('USE_BIG_MENU') === 'Y'){
    $arScripts[] = 'menu_aim';
    $arScripts[] = 'menu_many_items';
}
if (TSolution::getFrontParametrValue('SHOW_RIGHT_SIDE') === 'Y') {
    $arScripts[] = 'brands';
    $arScripts[] = 'ui-card';
    $arScripts[] = 'ui-card.ratio';
}

TSolution\Extensions::init($arScripts);

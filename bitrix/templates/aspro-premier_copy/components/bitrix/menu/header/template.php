<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    exit;
}

$this->setFrameMode(true);

if (empty($arResult)) {
    return;
}

$topLevelItemsCount = count($arResult);

// solution params
$countElementMenu = TSolution::getFrontParametrValue('COUNT_ITEMS_IN_LINE_MENU')
    ? ' count_'.TSolution::getFrontParametrValue('COUNT_ITEMS_IN_LINE_MENU')
    : '';
$iVisibleItemsMenu = TSolution::getFrontParametrValue('MAX_VISIBLE_ITEMS_MENU') ?: 10;
$bRightPart = TSolution::getFrontParametrValue('SHOW_RIGHT_SIDE') === 'Y';
$bBigMenu = TSolution::getFrontParametrValue('USE_BIG_MENU') === 'Y';

// catalog button modifiers
$bOnlyCatalog = $arParams["ONLY_CATALOG"] === "Y";
$bTransparentCatalogButton = $arParams['TRANSPARENT_CATALOG_BUTTON'] === 'Y';
$bLargeCatalogButton = $arParams["LARGE_CATALOG_BUTTON"] === "Y";

// nlo
$bNloMenu = $arParams["USE_NLO_MENU"] === "Y" && $bOnlyCatalog;
$nloMenuCode = $arParams["NLO_MENU_CODE"] ?? "menu-fixed";
?>
<div class="catalog_icons_<?=TSolution::getFrontParametrValue('SHOW_CATALOG_SECTIONS_ICONS');?>">
    <div class="header-menu__wrapper">
        <?for ($topLevelItemIndex = 0; $topLevelItemIndex < $topLevelItemsCount; $topLevelItemIndex++):?>
            <?php
            $arItem = $arResult[$topLevelItemIndex];
            // item config
            $bShowChilds = $arItem["CHILD"] && $arParams["MAX_LEVEL"] > 1;
            $bCurrentItemMenuIcon = !intval($arItem["PARAMS"]["ICON"]) && strlen($arItem["PARAMS"]["ICON"]);
            $bCurrentItemWideMenu = $arItem["PARAMS"]["WIDE_MENU"] === "Y" || $arParams["CATALOG_WIDE"] === "Y";
            $bCurrentItemBigMenu = false;
            if (
                $arItem["PARAMS"]["WIDE_MENU"] === "Y"
                && $arParams["CATALOG_WIDE"] !== "Y"
                && $bOnlyCatalog
            ) {
                $bCurrentItemBigMenu = $bBigMenu && $arItem["PARAMS"]["MENU_NOT_BIG"] !== "Y";
            }

            // menu top level wrapper styles
            $topLevelWrapperClassList = ['header-menu__item unvisible'];
            if ($topLevelItemIndex === 0) {
                $topLevelWrapperClassList[] = 'header-menu__item--first';
            }
            if ($topLevelItemIndex === $topLevelItemsCount-1) {
                $topLevelWrapperClassList[] = 'header-menu__item--last';
            }
            if ($bShowChilds) {
                $topLevelWrapperClassList[] = 'header-menu__item--dropdown';
            }
            if ($bCurrentItemWideMenu) {
                $topLevelWrapperClassList[] = 'header-menu__item--wide';
            }
            if ($arItem['SELECTED']) {
                $topLevelWrapperClassList[] = 'active';
            }

            $topLevelWrapperClassList = TSolution\Utils::implodeClasses($topLevelWrapperClassList);

            $topLevelLinkTextClassList = ['header-menu__title-wrapper icon-block flex-1'];
            $topLevelLinkTextClassList[] = 'font_'.($bCurrentItemWideMenu ? 15 : 13);
            if ($bCurrentItemMenuIcon) {
                $topLevelLinkTextClassList[] = 'icon-block__text';
            }
            $topLevelLinkTextClassList = TSolution\Utils::implodeClasses($topLevelLinkTextClassList);
            ?>
            <div class="<?=$topLevelWrapperClassList;?> color-dark-parent fill-dark-parent-all">
                <?if ($bOnlyCatalog):?>
                    <?php
                    // menu top level link styles
                    $topLevelLinkClassList = ['header-menu__link--only-catalog'];
                    if ($bTransparentCatalogButton) {
                        $topLevelLinkClassList[] = 'link-opacity-color light-opacity-hover btn--no-btn-appearance fill-dark-light';
                    } else {
                        $topLevelLinkClassList[] = 'fill-use-button-color btn btn-default btn--no-rippple';

                        if ($bLargeCatalogButton) {
                            $topLevelLinkClassList[] = 'btn-lg';
                        } else {
                            $topLevelLinkClassList[] = 'btn-sm';
                        }
                    }
                    ?>
                    <button type="button" class="<?=TSolution\Utils::implodeClasses($topLevelLinkClassList);?>">
                        <span class="icon-block line-block line-block--gap line-block--gap-12" title="<?=$arItem["TEXT"];?>">
                            <span class="icon-block__icon icon-block__icon--no-offset">
                                <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/header_icons.svg#burger', 'fill-button-color-target', [
                                    'WIDTH' => 14,
                                    'HEIGHT' => 13
                                ]);?>
                            </span>
                            <?if($arParams['SHOW_BUTTON_TEXT'] !== 'N'):?>
                                <span class="<?=$topLevelLinkTextClassList;?>">
                                    <span class="header-menu__title flex-1">
                                        <?=$arItem["TEXT"];?>
                                    </span>
                                </span>
                            <?endif;?>
                        </span>
                    </button>
                <?else:?>
                    <?php
                    $topLevelLinkClassList = ['color-dark-target rounded-x light-opacity-hover dark_link fill-dark-light-block'];
                    $topLevelLinkClassList[] = 'link-button-color-target';
                    $topLevelLinkClassList = TSolution\Utils::implodeClasses($topLevelLinkClassList);
                    ?>
                    <a class="header-menu__link header-menu__link--top-level <?=$topLevelLinkClassList;?>" href="<?=$arItem["LINK"];?>" title="<?=$arItem["TEXT"];?>">
                        <?// extended menu settings icon?>
                        <?if ($bCurrentItemMenuIcon):?>
                            <?=TSolution::showIconSvg('', SITE_TEMPLATE_PATH.'/images/svg/header_icons/'.$arItem["PARAMS"]["ICON"].'.svg', '', 'icon-block__icon banner-light-icon-fill');?>
                        <?endif;?>

                        <?// menu item text and dropdown arrow?>
                        <span class="<?=$topLevelLinkTextClassList;?>">
                            <span class="header-menu__title flex-1">
                                <?=$arItem["TEXT"];?>
                            </span>
                            <?if ($bShowChilds):?>
                                <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', 'icon-block__icon icon-block__icon--dropdown header-menu__wide-submenu-right-arrow banner-light-icon-fill fill-dark-target fill-button-color-target', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
                            <?endif;?>
                        </span>
                    </a>
                <?endif;?>
                <?if ($bNloMenu) $bShowChilds = TSolution::nlo($nloMenuCode) && $bShowChilds;?>
                <?if ($bShowChilds):?>
                    <?php
                    $dropdownMenuInnerClassList = ['dropdown-menu-inner'];
                    if ($bCurrentItemBigMenu) {
                        $dropdownMenuInnerClassList[] = 'long-menu-items';
                    }
                    if ($bOnlyCatalog && !$bCurrentItemBigMenu) {
                        $dropdownMenuInnerClassList[] = 'header-menu__wide-limiter scrollbar overscroll-behavior-contain';
                    }

                    $dropdownMenuInnerClassList = TSolution\Utils::implodeClasses($dropdownMenuInnerClassList);
                    ?>
                    <div class="header-menu__dropdown-menu dropdown-menu-wrapper dropdown-menu-wrapper--visible theme-root<?=!$bCurrentItemWideMenu ? ' dropdown-menu-wrapper--woffset' : '';?>">
                        <div class="<?=$dropdownMenuInnerClassList;?> rounded-x">
                            <?if ($bCurrentItemWideMenu):?>
                            <?php
                            $maxwidthClassList = ['maxwidth-theme'];
                            if (!$arParams['MAXWIDTH_THEME']) {
                                $maxwidthClassList[] = 'maxwidth-theme--no-maxwidth';
                            }

                            $maxwidthClassList = TSolution\Utils::implodeClasses($maxwidthClassList);
                            ?>
                            <div class="<?=$maxwidthClassList;?> pt pt--16 pb pb--8">
                            <?endif;?>

                            <?if ($bCurrentItemBigMenu):?>
                                <div class="menu-navigation line-block line-block--gap line-block--gap-40 line-block--align-flex-start">
                                    <div class="menu-navigation__sections-wrapper">
                                        <div class="menu-navigation__scroll scrollbar pr pr--8">
                                            <div class="menu-navigation__sections">
                                                <?foreach ($arItem["CHILD"] as $arChild):?>
                                                    <?php
                                                    $bShowImg = (
                                                            strlen($arChild['PARAMS']['PICTURE'] ?? '')
                                                            || isset($arChild['PARAMS']['SECTION_ICON'])
                                                        )
                                                        && TSolution::getFrontParametrValue('LEFT_BLOCK_CATALOG_ICONS') === 'Y';
                                                    ?>
                                                    <div class="menu-navigation__sections-item<?=$arChild['SELECTED'] ? ' active' : '';?>">
                                                        <?php
                                                        $childLinkClassList = ['menu-navigation__sections-item-link'];
                                                        if ($arChild["SELECTED"]) {
                                                            $childLinkClassList[] = 'menu-navigation__sections-item-link--active';
                                                        }
                                                        if ($arChild['CHILD']) {
                                                            $childLinkClassList[] = 'menu-navigation__sections-item-dropdown';
                                                        }

                                                        $childLinkClassList = TSolution\Utils::implodeClasses($childLinkClassList);
                                                        ?>
                                                        <a href="<?=$arChild['LINK'];?>" class="<?=$childLinkClassList;?> no-decoration font_15 color_dark rounded-x line-block line-block--gap line-block--gap-16 line-block--align-center">
                                                            <?if ($bShowImg):?>
                                                                <span class="menu-navigation__sections-item-image image colored_theme_svg">
                                                                    <?php
                                                                    $imageID = $arChild['PARAMS']['ICON'] ?? $arChild['PARAMS']['PICTURE'];
                                                                    $image = CFile::GetPath($imageID);
                                                                    ?>
                                                                    <?if (strpos($image, ".svg") !== false && TSolution::getFrontParametrValue('COLORED_CATALOG_ICON') === 'Y'):?>
                                                                        <?TSolution\Functions::showSVG([
                                                                            'PATH' => $image
                                                                        ]);?>
                                                                    <?else:?>
                                                                        <img class="lazyload" data-src="<?=$image;?>" src="<?=TSolution\Functions::showBlankImg($image);?>" alt="<?=$arChild['NAME'];?>" title="<?=$arChild['NAME']?>" />
                                                                    <?endif;?>
                                                                </span>
                                                            <?endif;?>
                                                            <span class="name flex-1"><?=$arChild['TEXT'];?></span>
                                                            <?if ($arChild['CHILD']):?>
                                                                <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right', 'fill-dark-light-block', ['WIDTH' => 4,'HEIGHT' => 5]);?>
                                                            <?endif;?>
                                                        </a>
                                                    </div>
                                                <?endforeach;?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="menu-navigation__content flex-grow-1 rounded-x scrollbar">
                            <?endif;?>

                            <?if ($bCurrentItemWideMenu):?>
                                <div class="header-menu__wide-wrapper line-block line-block--row-reverse line-block--gap line-block--gap-32 line-block--align-flex-start pt pt--8 pb pb--8">
                            <?endif;?>

                            <?// menu banner for top levels?>
                            <?if (
                                $bRightPart && $bCurrentItemWideMenu &&
                                (
                                    ($bCurrentItemBigMenu && !$bOnlyCatalog)
                                    || !$bCurrentItemBigMenu
                                )
                            ):?>
                                <?php
                                ob_start();
                                    $GLOBALS['arTopMenuBannersFilter'] = array('PROPERTY_SHOW_MENU' => $arItem["LINK"]);
                                    include 'side_banners.php';
                                $bannersHTML = trim(ob_get_clean());

                                if (!$brandsHTML) {
                                    ob_start();
                                        include 'side_brands.php';
                                    $brandsHTML = trim(ob_get_clean());
                                }
                                ?>
                                <?if ($bannersHTML || $brandsHTML):?>
                                    <div class="header-menu__wide-right-part no-shrinked pt pb<?=!$brandsHTML ? ' sticky' : '';?>">
                                        <div class="line-block__item line-block line-block--column line-block--align-normal line-block--gap line-block--gap-20">
                                            <?if ($bannersHTML):?>
                                                <div class="line-block__item"><?=$bannersHTML;?></div>
                                            <?endif;?>

                                            <?if ($brandsHTML):?>
                                                <div class="line-block__item"><?=$brandsHTML;?></div>
                                            <?endif;?>
                                        </div>
                                    </div>
                                <?endif;?>
                            <?endif;?>

                            <?php
                            $headerDropdownMenuInnerClassList = ['header-menu__dropdown-menu-inner'];
                            if ($bCurrentItemWideMenu && !$bCurrentItemBigMenu) {
                                $headerDropdownMenuInnerClassList[] = 'header-menu__dropdown-menu--grids';
                            }

                            $headerDropdownMenuInnerClassList = TSolution\Utils::implodeClasses($headerDropdownMenuInnerClassList);
                            ?>
                            <ul class="<?=$headerDropdownMenuInnerClassList;?>">
                                <?foreach ($arItem["CHILD"] as $arSubItem):?>
                                    <?$bShowChilds = $arSubItem["CHILD"] && $arParams["MAX_LEVEL"] > 2;?>
                                    <?if ($bCurrentItemBigMenu):?>
                                        <li class="parent-items m m--0 line-block line-block--gap line-block--gap-0 line-block--align-flex-start<?=$arSubItem['SELECTED'] ? ' parent-items--active' : '';?>">
                                            <div class="parent-items__info line-block__item line-block line-block--gap line-block--gap-32 line-block--column line-block--align-flex-start flex-1">
                                                <div class="parent-items__item-title line-block__item">
                                                    <?if(strlen($arSubItem['URL'])):?>
                                                        <a href="<?=$arSubItem['LINK'];?>" class="no-decoration line-block line-block--gap line-block--gap-12 line-block--align-baseline fill-dark-parent">
                                                            <span class="parent-items__item-name fw-500 font_20"><?=$arSubItem['TEXT'];?></span>
                                                            <span class="parent-items__item-arrow rounded-x line-block line-block--gap fill-dark-target"><?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', '', ['WIDTH' => 6,'HEIGHT' => 12]);?></span>
                                                        </a>
                                                    <?else:?>
                                                        <span class="parent-items__item-name fw-500 font_20 color_dark"><?=$arSubItem['TEXT'];?></span>
                                                    <?endif;?>
                                                </div>

                                                <div class="header-menu__many-items line-block__item">
                                                    <ul class="header-menu__dropdown-menu-inner header-menu__dropdown-menu--grids p">
                                                        <?php
                                                        $tmpSubItem = $arSubItem;
                                                        foreach ($arSubItem["CHILD"] as $arSubItem2) {
                                                            $arSubItem = $arSubItem2;
                                                            $bShowChilds = $arSubItem["CHILD"] && $arParams["MAX_LEVEL"] > 3;
                                                            $bIcon = TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'ICONS' && $arSubItem['PARAMS']['ICON'];
                                                            $bTransparentPicture = false;
                                                            if (
                                                                array_key_exists('TRANSPARENT_PICTURE', $arSubItem['PARAMS'])
                                                                && $arSubItem['PARAMS']['TRANSPARENT_PICTURE']
                                                                && (
                                                                    TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'TRANSPARENT_PICTURES'
                                                                    || (
                                                                        TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'ICONS'
                                                                        && !$bIcon
                                                                    )
                                                                )
                                                            ) {
                                                                $bTransparentPicture = $arSubItem['PARAMS']['TRANSPARENT_PICTURE'];
                                                            }
                                                            $bPicture = false;
                                                            if (
                                                                array_key_exists('PICTURE', $arSubItem['PARAMS'])
                                                                && $arSubItem['PARAMS']['PICTURE']
                                                                && (
                                                                    TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'PICTURES'
                                                                    || (
                                                                        TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'TRANSPARENT_PICTURES'
                                                                        && !$bTransparentPicture
                                                                    )
                                                                    || (
                                                                        TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'ICONS'
                                                                        && !$bIcon
                                                                        && !$bTransparentPicture
                                                                    )
                                                                )
                                                            ) {
                                                                $bPicture = $arSubItem['PARAMS']['PICTURE'];
                                                            }
                                                            $bHasPicture = $bIcon || $bTransparentPicture || $bPicture;

                                                            include 'wide_menu.php';
                                                        }
                                                        $arSubItem = $tmpSubItem;
                                                        unset($tmpSubItem);
                                                        ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <?if ($bRightPart && $bCurrentItemWideMenu && $bOnlyCatalog):?>
                                                <?php
                                                ob_start();
                                                    $GLOBALS['arTopMenuBannersFilter'] = array('PROPERTY_SHOW_MENU' => $arSubItem["LINK"]);
                                                    include 'side_banners.php';
                                                $bannersHTML = trim(ob_get_clean());

                                                if (!$brandsHTML) {
                                                    ob_start();
                                                        include 'side_brands.php';
                                                    $brandsHTML = trim(ob_get_clean());
                                                }
                                                ?>
                                                <?if ($bannersHTML || $brandsHTML):?>
                                                    <?php
                                                    $rightPartClassList = ['header-menu__wide-right-part'];
                                                    if ($bCurrentItemBigMenu) {
                                                        $rightPartClassList[] = 'pt--8 pb--8';
                                                    } else {
                                                        $rightPartClassList[] = 'pt--16 pb--16';
                                                    }
                                                    if (!$brandsHTML) {
                                                        $rightPartClassList[] = 'sticky';
                                                    }

                                                    $rightPartClassList = TSolution\Utils::implodeClasses($rightPartClassList);
                                                    ?>
                                                    <div class="<?=$rightPartClassList;?> no-shrinked pt pb">
                                                        <div class="line-block__item line-block line-block--column line-block--align-normal line-block--gap line-block--gap-20">
                                                            <?if ($bannersHTML):?>
                                                                <div class="line-block__item"><?=$bannersHTML;?></div>
                                                            <?endif;?>

                                                            <?if ($brandsHTML):?>
                                                                <div class="line-block__item"><?=$brandsHTML;?></div>
                                                            <?endif;?>
                                                        </div>
                                                    </div>
                                                <?endif;?>
                                            <?endif;?>
                                        </li>
                                    <?elseif ($bCurrentItemWideMenu):?>
                                        <?php
                                        $bIcon = TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'ICONS' && $arSubItem['PARAMS']['ICON'];
                                        $bTransparentPicture = $bPicture = false;
                                        if (
                                            array_key_exists('TRANSPARENT_PICTURE', $arSubItem['PARAMS'])
                                            && $arSubItem['PARAMS']['TRANSPARENT_PICTURE']
                                            && (
                                                TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'TRANSPARENT_PICTURES'
                                                || (
                                                    TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'ICONS'
                                                    && !$bIcon
                                                )
                                            )
                                        ) {
                                            $bTransparentPicture = $arSubItem['PARAMS']['TRANSPARENT_PICTURE'];
                                        }
                                        if (
                                            array_key_exists('PICTURE', $arSubItem['PARAMS'])
                                            && $arSubItem['PARAMS']['PICTURE']
                                            && (
                                                TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'PICTURES'
                                                || (
                                                    TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'TRANSPARENT_PICTURES'
                                                    && !$bTransparentPicture
                                                )
                                                || (
                                                    TSolution::getFrontParametrValue('IMAGES_WIDE_MENU') == 'ICONS'
                                                    && !$bIcon
                                                    && !$bTransparentPicture
                                                )
                                            )
                                        ) {
                                            $bPicture = $arSubItem['PARAMS']['PICTURE'];
                                        }

                                        $bHasPicture = $bIcon || $bTransparentPicture || $bPicture;
                                        include 'wide_menu.php';
                                        ?>
                                    <?else:?>
                                        <?php
                                        $subItemClassList = ['header-menu__dropdown-item'];
                                        if ($countElementMenu) {
                                            $subItemClassList[] = $countElementMenu;
                                        }
                                        if ($bShowChilds) {
                                            $subItemClassList[] = 'header-menu__dropdown-item--with-dropdown';
                                        }
                                        if ($arSubItem['SELECTED']) {
                                            $subItemClassList[] = 'active';
                                        }

                                        $subItemClassList = TSolution\Utils::implodeClasses($subItemClassList);
                                        ?>
                                        <li class="<?=$subItemClassList;?>">
                                            <?php
                                            $subItemLinkClassList = ['dropdown-menu-item'];
                                            if ($arSubItem['SELECTED']) {
                                                $subItemLinkClassList[] = 'dropdown-menu-item--current fw-500';
                                            }

                                            $subItemLinkClassList = TSolution\Utils::implodeClasses($subItemLinkClassList);
                                            ?>
                                            <a class="<?=$subItemLinkClassList;?> no-decoration font_15 button-rounded-x fill-dark-light line-block line-block--gap line-block--gap-16" href="<?=$arSubItem["LINK"];?>">
                                                <?=$arSubItem["TEXT"];?>
                                                <?if ($arSubItem["CHILD"] && count($arSubItem["CHILD"]) && $bShowChilds):?>
                                                    <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', ' header-menu__dropdown-right-arrow', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
                                                <?endif;?>
                                            </a>

                                            <?if ($bShowChilds):?>
                                                <?$iCountChilds = count($arSubItem["CHILD"]);?>
                                                <div class="header-menu__dropdown-menu header-menu__dropdown-menu--submenu dropdown-menu-wrapper dropdown-menu-wrapper--visible dropdown-menu-wrapper--woffset">
                                                    <ul class="dropdown-menu-inner rounded-x">
                                                        <?foreach ($arSubItem["CHILD"] as $key => $arSubItem2):?>
                                                            <?php
                                                            $bShowChilds = $arSubItem2["CHILD"] && $arParams["MAX_LEVEL"] > 3;

                                                            $subItem2ClassList = ['header-menu__dropdown-item'];
                                                            if (($key + 1) > $iVisibleItemsMenu) {
                                                                $subItem2ClassList[] = 'collapsed';
                                                            }
                                                            if ($bShowChilds) {
                                                                $subItem2ClassList[] = 'header-menu__dropdown-item--with-dropdown';
                                                            }
                                                            if ($arSubItem2["SELECTED"]) {
                                                                $subItem2ClassList[] = 'active';
                                                            }

                                                            $subItem2ClassList = TSolution\Utils::implodeClasses($subItem2ClassList);
                                                            ?>
                                                            <li class="<?=$subItem2ClassList;?>">
                                                                <?php
                                                                $subItem2LinkClassList = ['dropdown-menu-item'];
                                                                if ($arSubItem2['SELECTED']) {
                                                                    $subItem2LinkClassList[] = 'dropdown-menu-item--current fw-500';
                                                                }

                                                                $subItem2LinkClassList = TSolution\Utils::implodeClasses($subItem2LinkClassList);
                                                                ?>
                                                                <a class="<?=$subItem2LinkClassList;?> no-decoration font_15 button-rounded-x fill-dark-light" href="<?=$arSubItem2["LINK"];?>">
                                                                    <?=$arSubItem2["TEXT"];?>
                                                                    <?if (count($arSubItem["CHILD"]) && $bShowChilds):?>
                                                                        <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', ' header-menu__dropdown-right-arrow', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
                                                                    <?endif;?>
                                                                </a>

                                                                <?if ($bShowChilds):?>
                                                                    <div class="header-menu__dropdown-menu header-menu__dropdown-menu--submenu  dropdown-menu-wrapper dropdown-menu-wrapper--visible dropdown-menu-wrapper--woffset">
                                                                    <ul class="dropdown-menu-inner rounded-x">
                                                                        <?foreach ($arSubItem2["CHILD"] as $arSubItem3):?>
                                                                            <li class="header-menu__dropdown-item<?=$arSubItem3['SELECTED'] ? ' active' : '';?>">
                                                                                <?php
                                                                                $subItem3LinkClassList = ['dropdown-menu-item'];
                                                                                if ($arSubItem3['SELECTED']) {
                                                                                    $subItem3LinkClassList[] =  'dropdown-menu-item--current fw-500';
                                                                                }

                                                                                $subItem3LinkClassList = TSolution\Utils::implodeClasses($subItem3LinkClassList);
                                                                                ?>
                                                                                <a class="<?=$subItem3LinkClassList?> no-decoration font_15 button-rounded-x" href="<?=$arSubItem3["LINK"];?>"><?=$arSubItem3["TEXT"];?></a>
                                                                            </li>
                                                                        <?endforeach;?>
                                                                    </ul>
                                                                    </div>
                                                                <?endif;?>
                                                            </li>
                                                        <?endforeach;?>

                                                        <?if ($iCountChilds > $iVisibleItemsMenu && $bCurrentItemWideMenu):?>
                                                            <li>
                                                                <span class="colored more_items with_dropdown">
                                                                    <?=\Bitrix\Main\Localization\Loc::getMessage("S_MORE_ITEMS");?>
                                                                    <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', '', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
                                                                </span>
                                                            </li>
                                                        <?endif;?>
                                                    </ul>
                                                </div>
                                            <?endif;?>
                                        </li>
                                    <?endif;?>
                                <?endforeach;?>
                            </ul>

                            <?if ($bCurrentItemWideMenu):?>
                                </div>
                            <?endif;?>

                            <?if ($bCurrentItemBigMenu):?>
                                    </div>
                                </div>
                            <?endif;?>

                            <?if ($bCurrentItemWideMenu):?>
                            </div>
                            <?endif;?>
                        </div>
                    </div>
                <?endif;?>
                <?if ($bNloMenu) TSolution::nlo($nloMenuCode);?>
            </div>
            <?if ($bOnlyCatalog) break;?>
        <?endfor;?>

        <?if (!$bOnlyCatalog):?>
            <div class="header-menu__item header-menu__item--more-items unvisible">
                <div class="header-menu__link banner-light-icon-fill fill-dark-light light-opacity-hover">
                    <span class="font_22">
                        <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/header_icons.svg#dots-15-3', 'fill-dark-target fill-button-color-target', ['WIDTH' => 15,'HEIGHT' => 3]);?>
                    </span>
                </div>

                <div class="header-menu__dropdown-menu dropdown-menu-wrapper dropdown-menu-wrapper--visible dropdown-menu-wrapper--woffset theme-root">
                    <ul class="header-menu__more-items-list dropdown-menu-inner rounded-x"></ul>
                </div>
            </div>
        <?endif;?>
    </div>
</div>
<script data-skip-moving="true">
    if (typeof topMenuAction !== 'function'){
        function topMenuAction() {
            if (typeof CheckTopMenuDotted !== 'function'){
                let timerID = setInterval(function(){
                    if (typeof CheckTopMenuDotted === 'function'){
                        CheckTopMenuDotted();
                        clearInterval(timerID);
                    }
                }, 100);
            } else {
                CheckTopMenuDotted();
            }
        }
    }
</script>

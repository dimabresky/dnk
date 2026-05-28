<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$this->setFrameMode(true);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('iblock');

global $arTheme, $NextSectionID;

$bHideLeftBlock = 'Y' === $APPLICATION->GetProperty('SHOW_LAYOUT_ASIDE');

$arPageParams = $arSection = $section = [];

if (!$arParams['SECTION_DISPLAY_PROPERTY']) {
    $arParams['SECTION_DISPLAY_PROPERTY'] = 'UF_VIEWTYPE';
}
$_SESSION['SMART_FILTER_VAR'] = $arParams['FILTER_NAME'];

// set params from module
TSolution\Functions::replaceListParams($arParams);

$arRegion = TSolution\Regionality::getCurrentRegion();

$bOrderViewBasket = ('Y' === trim($arTheme['ORDER_VIEW']['VALUE']));
$bShowLeftBlock = ('Y' == $arTheme['LEFT_BLOCK_CATALOG_SECTIONS']['VALUE'] && !defined('ERROR_404') && !$bHideLeftBlock);

$APPLICATION->SetPageProperty('MENU', 'N');
$APPLICATION->AddViewContent('right_block_class', 'catalog_page ');
?>

<?if (TSolution::checkAjaxRequest()) { ?>
    <div>
<?}?>

<div class="top-content-block">
    <?$APPLICATION->ShowViewContent('top_content'); ?>
    <?$APPLICATION->ShowViewContent('top_content2'); ?>
</div>

<?if (TSolution::checkAjaxRequest()) { ?>
    </div>
<?}?>

<?php
// get current section ID
$arSectionFilter = [];
if ($arResult['VARIABLES']['SECTION_ID'] > 0) {
    $arSectionFilter = ['GLOBAL_ACTIVE' => 'Y', 'ID' => $arResult['VARIABLES']['SECTION_ID'], 'IBLOCK_ID' => $arParams['IBLOCK_ID']];
} elseif (strlen(trim($arResult['VARIABLES']['SECTION_CODE'])) > 0) {
    $arSectionFilter = ['GLOBAL_ACTIVE' => 'Y', '=CODE' => $arResult['VARIABLES']['SECTION_CODE'], 'IBLOCK_ID' => $arParams['IBLOCK_ID']];
}
if ($arSectionFilter) {
    $section = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], TSolution::makeSectionFilterInRegion($arSectionFilter), false, ['ID', 'IBLOCK_ID', 'NAME', "PICTURE", "DETAIL_PICTURE", 'DESCRIPTION', 'UF_SECTION_DESCR', 'UF_FILTER_VIEW', 'UF_OFFERS_TYPE', 'UF_TABLE_PROPS', 'UF_INCLUDE_SUBSECTION', 'UF_LINKED_BANNERS', $arParams['SECTION_DISPLAY_PROPERTY'], 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN',  "SectionValues", "UF_CATALOG_ICON"]);
}

$typeSKU = '';
$bSetElementsLineRow = false;

if ($section) {
    $NextSectionID = $arSection['ID'] = $section['ID'];
    $arSection['NAME'] = $section['NAME'];
    $arSection['IBLOCK_SECTION_ID'] = $section['IBLOCK_SECTION_ID'];
    $arSection['DEPTH_LEVEL'] = $section['DEPTH_LEVEL'];
    if ($section[$arParams['SECTION_DISPLAY_PROPERTY']]) {
        $arDisplayRes = CUserFieldEnum::GetList([], ['ID' => $section[$arParams['SECTION_DISPLAY_PROPERTY']]]);
        if ($arDisplay = $arDisplayRes->GetNext()) {
            $arSection['DISPLAY'] = $arDisplay['XML_ID'];
        }
    }

    if (strlen($section['DESCRIPTION'])) {
        $arSection['DESCRIPTION'] = $section['DESCRIPTION'];
    }
    if (strlen($section['UF_SECTION_DESCR'])) {
        $arSection['UF_SECTION_DESCR'] = $section['UF_SECTION_DESCR'];
    }
    if ($section['UF_LINKED_BANNERS']) {
        $arSection['UF_LINKED_BANNERS'] = $section['UF_LINKED_BANNERS'];
    }
    // $posSectionDescr = COption::GetOptionString(VENDOR_MODULE_ID, "SHOW_SECTION_DESCRIPTION", "BOTH", SITE_ID);
    $posSectionDescr = 'BOTH';

    global $arSubSectionFilter;
    $arSubSectionFilter = [
        'SECTION_ID' => $arSection['ID'],
        'IBLOCK_ID' => $arParams['IBLOCK_ID'],
        'ACTIVE' => 'Y',
        'GLOBAL_ACTIVE' => 'Y',
    ];
    $iSectionsCount = count(TSolution\Cache::CIblockSection_GetList(['CACHE' => ['TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y']], TSolution::makeSectionFilterInRegion($arSubSectionFilter)));

    if ('N' === $arParams['SHOW_MORE_SUBSECTIONS']) {
        $iSectionsCount = 0;
    }

    // set smartfilter view
    $viewTmpFilter = 0;
    if ($section['UF_FILTER_VIEW']) {
        $viewTmpFilter = $section['UF_FILTER_VIEW'];
    }

    $viewTableProps = 0;
    if ($section['UF_TABLE_PROPS']) {
        $viewTableProps = $section['UF_TABLE_PROPS'];
    }

    if ($section['UF_OFFERS_TYPE']) {
        $typeSKU = $section['UF_OFFERS_TYPE'];
    }

    $includeSubsection = '';
    if ($section['UF_INCLUDE_SUBSECTION']) {
        $includeSubsection = $section['UF_INCLUDE_SUBSECTION'];
    }

    if (!$viewTmpFilter || !$arSection['DISPLAY'] || !$viewTableProps || !$includeSubsection || !$typeSKU || !$arSection['UF_LINKED_BANNERS']) {
        if ($section['DEPTH_LEVEL'] > 1) {
            $sectionParent = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', 'ID' => $section['IBLOCK_SECTION_ID'], 'IBLOCK_ID' => $arParams['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'NAME', 'UF_FILTER_VIEW', 'UF_OFFERS_TYPE', 'UF_TABLE_PROPS', 'UF_LINKED_BANNERS', $arParams['SECTION_DISPLAY_PROPERTY']]);
            if ($sectionParent['UF_FILTER_VIEW'] && !$viewTmpFilter) {
                $viewTmpFilter = $sectionParent['UF_FILTER_VIEW'];
            }
            if ($sectionParent['UF_TABLE_PROPS'] && !$viewTableProps) {
                $viewTableProps = $sectionParent['UF_TABLE_PROPS'];
            }
            if ($sectionParent['UF_INCLUDE_SUBSECTION'] && !$includeSubsection) {
                $includeSubsection = $sectionParent['UF_INCLUDE_SUBSECTION'];
            }
            if ($sectionParent['UF_OFFERS_TYPE'] && !$typeSKU) {
                $typeSKU = $sectionParent['UF_OFFERS_TYPE'];
            }
            if ($sectionParent['UF_LINKED_BANNERS'] && !$arSection['UF_LINKED_BANNERS']) {
                $arSection['UF_LINKED_BANNERS'] = $sectionParent['UF_LINKED_BANNERS'];
            }
            if ($sectionParent[$arParams['SECTION_DISPLAY_PROPERTY']] && !$arSection['DISPLAY']) {
                $arDisplayRes = CUserFieldEnum::GetList([], ['ID' => $sectionParent[$arParams['SECTION_DISPLAY_PROPERTY']]]);
                if ($arDisplay = $arDisplayRes->GetNext()) {
                    $arSection['DISPLAY'] = $arDisplay['XML_ID'];
                }
            }

            if ($section['DEPTH_LEVEL'] > 2) {
                if (!$viewTmpFilter || !$arSection['DISPLAY'] || !$viewTableProps || !$includeSubsection || !$typeSKU || !$arSection['UF_LINKED_BANNERS']) {
                    $sectionRoot = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', '<=LEFT_BORDER' => $section['LEFT_MARGIN'], '>=RIGHT_BORDER' => $section['RIGHT_MARGIN'], 'DEPTH_LEVEL' => 1, 'IBLOCK_ID' => $arParams['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'NAME', 'UF_FILTER_VIEW', 'UF_OFFERS_TYPE', 'UF_TABLE_PROPS', 'UF_LINKED_BANNERS', $arParams['SECTION_DISPLAY_PROPERTY']]);
                    if ($sectionRoot['UF_FILTER_VIEW'] && !$viewTmpFilter) {
                        $viewTmpFilter = $sectionRoot['UF_FILTER_VIEW'];
                    }
                    if ($sectionRoot['UF_TABLE_PROPS'] && !$viewTableProps) {
                        $viewTableProps = $sectionRoot['UF_TABLE_PROPS'];
                    }
                    if ($sectionRoot['UF_INCLUDE_SUBSECTION'] && !$includeSubsection) {
                        $includeSubsection = $sectionRoot['UF_INCLUDE_SUBSECTION'];
                    }
                    if ($sectionRoot['UF_OFFERS_TYPE'] && !$typeSKU) {
                        $typeSKU = $sectionRoot['UF_OFFERS_TYPE'];
                    }
                    if ($sectionRoot['UF_LINKED_BANNERS'] && !$arSection['UF_LINKED_BANNERS']) {
                        $arSection['UF_LINKED_BANNERS'] = $sectionRoot['UF_LINKED_BANNERS'];
                    }
                    if ($sectionRoot[$arParams['SECTION_DISPLAY_PROPERTY']] && !$arSection['DISPLAY']) {
                        $arDisplayRes = CUserFieldEnum::GetList([], ['ID' => $sectionRoot[$arParams['SECTION_DISPLAY_PROPERTY']]]);
                        if ($arDisplay = $arDisplayRes->GetNext()) {
                            $arSection['DISPLAY'] = $arDisplay['XML_ID'];
                        }
                    }
                }
            }
        }
    }
    if ($viewTmpFilter) {
        $rsViews = CUserFieldEnum::GetList([], ['ID' => $viewTmpFilter]);
        if ($arView = $rsViews->Fetch()) {
            if (empty($_SESSION['THEME'][SITE_ID]['FILTER_VIEW'])) {
                $viewFilter = $arView['XML_ID'];
                $arParams['FILTER_VIEW'] = $GLOBALS['arMergeOptions']['FILTER_VIEW'] = strtoupper($viewFilter);
            }
        }
    }
    if ($viewTableProps) {
        $rsViews = CUserFieldEnum::GetList([], ['ID' => $viewTableProps]);
        if ($arView = $rsViews->Fetch()) {
            $typeTableProps = strtolower($arView['XML_ID']);
        }
    }
    if ($includeSubsection) {
        $rsViews = CUserFieldEnum::GetList([], ['ID' => $includeSubsection]);
        if ($arView = $rsViews->Fetch()) {
            $arParams['INCLUDE_SUBSECTIONS'] = $arView['XML_ID'];
        }
    }
    if ($typeSKU) {
        $rsViews = CUserFieldEnum::GetList([], ['ID' => $typeSKU]);
        if ($arView = $rsViews->Fetch()) {
            $typeSKU = $arView['XML_ID'];
            $arTheme['CATALOG_PAGE_DETAIL_SKU']['VALUE'] = $typeSKU;
            $arParams['TYPE_SKU'] = $GLOBALS['arMergeOptions']['CATALOG_PAGE_DETAIL_SKU'] = $typeSKU;
        }
    }

    $arElementFilter = ['SECTION_ID' => $arSection['ID'], 'ACTIVE' => 'Y', 'INCLUDE_SUBSECTIONS' => $arParams['INCLUDE_SUBSECTIONS'], 'IBLOCK_ID' => $arParams['IBLOCK_ID']];
    if ('A' == $arParams['INCLUDE_SUBSECTIONS']) {
        $arElementFilter['INCLUDE_SUBSECTIONS'] = 'Y';
        $arElementFilter['SECTION_GLOBAL_ACTIVE'] = 'Y';
        $arElementFilter['SECTION_ACTIVE '] = 'Y';
    }

    $itemsCnt = TSolution\Cache::CIBlockElement_GetList(['CACHE' => ['TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], TSolution::makeElementFilterInRegion($arElementFilter), []);
}

$bHideSideSectionBlock = ('Y' == $arParams['SHOW_SIDE_BLOCK_LAST_LEVEL'] && $iSectionsCount && 'N' == $arParams['INCLUDE_SUBSECTIONS']);
if ($bHideSideSectionBlock) {
    $APPLICATION->SetPageProperty('MENU', 'N');
}

if (!$arParams['FILTER_VIEW']) {
    $arParams['FILTER_VIEW'] = 'VERTICAL';
    if ('N' !== $arTheme['SHOW_SMARTFILTER']['VALUE'] && $itemsCnt) {
        if (
            'COMPACT' == $arTheme['SHOW_SMARTFILTER']['DEPENDENT_PARAMS']['FILTER_VIEW']['VALUE'] || !$bShowLeftBlock
        ) {
            $arParams['FILTER_VIEW'] = 'COMPACT';
        }
    }
}
?>
<div class="main-wrapper flexbox flexbox--direction-row" itemscope itemtype="https://schema.org/ProductCollection">
    <?(new TSolution\Scheme\CatalogSection($section))->show();?>
    <div class="section-content-wrapper <?= $bShowLeftBlock ? 'with-leftblock' : ''; ?> flex-1">
        <?if (!$section) { ?>
            <?Bitrix\Iblock\Component\Tools::process404(
                '', 'Y' === $arParams['SET_STATUS_404'], 'Y' === $arParams['SET_STATUS_404'], 'Y' === $arParams['SHOW_404'], $arParams['FILE_404']
            ); ?>
        <?}?>

        <?if ($section) { ?>
            <?php
            // seo
            $catalogInfoIblockId = $arParams['LANDING_IBLOCK_ID'];
            if ($catalogInfoIblockId && !$bSimpleSectionTemplate) {
                $arSeoItems = TSolution\Cache::CIBLockElement_GetList(
                    ['SORT' => 'ASC', 'CACHE' => ['MULTI' => 'Y', 'TAG' => TSolution\Cache::GetIBlockCacheTag($catalogInfoIblockId)]],
                    ['IBLOCK_ID' => $catalogInfoIblockId, 'ACTIVE' => 'Y'],
                    false,
                    false,
                    ['ID', 'IBLOCK_ID', 'PROPERTY_FILTER_URL', 'PROPERTY_LINK_REGION']
                );
                $arSeoItem = $arTmpRegionsLanding = [];
                if ($arSeoItems) {
                    $iLandingItemID = 0;
                    $current_url = $APPLICATION->GetCurDir();
                    $url = urldecode(str_replace(' ', '+', $current_url));
                    foreach ($arSeoItems as $arItem) {
                        if (!is_array($arItem['PROPERTY_LINK_REGION_VALUE'])) {
                            $arItem['PROPERTY_LINK_REGION_VALUE'] = (array) $arItem['PROPERTY_LINK_REGION_VALUE'];
                        }

                        if (!$arSeoItem) {
                            $urldecoded = urldecode($arItem['PROPERTY_FILTER_URL_VALUE']);
                            $urldecodedCP = iconv('utf-8', 'windows-1251//IGNORE', $urldecoded);
                            if ($urldecoded == $url || $urldecoded == $current_url || $urldecodedCP == $current_url) {
                                if ($arRegion && $arItem['PROPERTY_LINK_REGION_VALUE']) {
                                    if (in_array($arRegion['ID'], $arItem['PROPERTY_LINK_REGION_VALUE'])) {
                                        $arSeoItem = $arItem;
                                    }
                                } else {
                                    $arSeoItem = $arItem;
                                }

                                if ($arSeoItem) {
                                    $iLandingItemID = $arSeoItem['ID'];
                                    $arSeoItem = TSolution\Cache::CIBLockElement_GetList(['SORT' => 'ASC', 'CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($catalogInfoIblockId)]], ['IBLOCK_ID' => $catalogInfoIblockId, 'ID' => $iLandingItemID], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'PROPERTY_FILTER_URL', 'PROPERTY_LINK_REGION', 'PROPERTY_FORM_QUESTION', 'PROPERTY_SECTION_SERVICES', 'PROPERTY_TIZERS', 'PROPERTY_SECTION', 'DETAIL_TEXT', 'PROPERTY_I_ELEMENT_PAGE_TITLE', 'PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_ALT', 'PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_TITLE', 'PROPERTY_I_SKU_PAGE_TITLE', 'PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_ALT', 'PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_TITLE', 'ElementValues']);

                                    $arIBInheritTemplates = [
                                        'ELEMENT_PAGE_TITLE' => $arSeoItem['PROPERTY_I_ELEMENT_PAGE_TITLE_VALUE'],
                                        'ELEMENT_PREVIEW_PICTURE_FILE_ALT' => $arSeoItem['PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_ALT_VALUE'],
                                        'ELEMENT_PREVIEW_PICTURE_FILE_TITLE' => $arSeoItem['PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_TITLE_VALUE'],
                                        'SKU_PAGE_TITLE' => $arSeoItem['PROPERTY_I_SKU_PAGE_TITLE_VALUE'],
                                        'SKU_PREVIEW_PICTURE_FILE_ALT' => $arSeoItem['PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_ALT_VALUE'],
                                        'SKU_PREVIEW_PICTURE_FILE_TITLE' => $arSeoItem['PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_TITLE_VALUE'],
                                    ];
                                    if (TSolution::isSmartSeoInstalled()) {
                                        Aspro\Smartseo\General\Smartseo::disallowNoindexRule(true);
                                    }
                                }
                            }
                        }

                        if($arRegion) {
                            if($arItem['PROPERTY_LINK_REGION_VALUE'] && !in_array($arRegion['ID'], $arItem['PROPERTY_LINK_REGION_VALUE'])){
                                $arTmpRegionsLanding[] = $arItem['ID'];
                            }
                        }
                    }
                }

                if ($arSeoItems && $bHideSideSectionBlock) {
                    $arSeoItems = [];
                }
            }

            if ($arRegion) {
                $GLOBALS[$arParams['FILTER_NAME']]['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
                TSolution::makeElementFilterInRegion($GLOBALS[$arParams['FILTER_NAME']]);
            }

            $bContolAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) && isset($_GET['control_ajax']) && 'Y' == $_GET['control_ajax']);
            $sViewElementTemplate = ('FROM_MODULE' == $arParams['SECTION_ELEMENTS_TYPE_VIEW'] ? $arTheme['ELEMENTS_CATALOG_PAGE']['VALUE'] : $arParams['SECTION_ELEMENTS_TYPE_VIEW']);
            ?>
            <?// section elements?>
            <div class="js_wrapper_items<?= 'Y' == $arTheme['LAZYLOAD_BLOCK_CATALOG']['VALUE'] ? ' with-load-block' : ''; ?>" >
                <div class="js-load-wrapper <?= $APPLICATION->ShowViewContent('section_additional_class'); ?>">

                    <?if ($bContolAjax) { ?>
                        <?$APPLICATION->RestartBuffer(); ?>
                    <?}?>
                    <?@include_once 'page_blocks/'.$sViewElementTemplate.'.php'; ?>

                    <?if ($bContolAjax) { ?>
                        <?exit; ?>
                    <?}?>
                </div>
            </div>
            <?TSolution::checkBreadcrumbsChain($arParams, $arSection);?>
            <?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.history.js'); ?>
        <?} else { ?>
            <div class="alert alert-danger">
                <?= $arParams['MESSAGE_404'] ?: Loc::getMessage('NOT_FOUNDED_SECTION'); ?>
            </div>
        <?}?>
    </div>
    <?if ($bShowLeftBlock) { ?>
        <?TSolution::ShowPageType('left_block'); ?>
    <?}?>
</div>

<?php
TSolution::setCatalogSectionDescription(
    [
        'FILTER_NAME' => $arParams['FILTER_NAME'],
        'CACHE_TYPE' => $arParams['CACHE_TYPE'],
        'CACHE_TIME' => $arParams['CACHE_TIME'],
        'SECTION_ID' => $arSection['ID'],
        'SHOW_SECTION_DESC' => $arParams['SHOW_SECTION_DESC'],
        'SEO_ITEM' => $arSeoItem,
    ]
);
?>
<?TSolution\Extensions::init(['filter_panel', 'dropdown_select', 'smart_filter']); ?>

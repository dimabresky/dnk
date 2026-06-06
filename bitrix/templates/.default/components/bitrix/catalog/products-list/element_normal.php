<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\ModuleManager;

global $arTheme, $APPLICATION;

$APPLICATION->AddViewContent('right_block_class', 'catalog_page ');
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/animation/animate.min.css');
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.history.js');

// cart
$bOrderViewBasket = (trim($arTheme['ORDER_VIEW']['VALUE']) === 'Y');

if($arSection){
	$arInherite = TSolution::getSectionInheritedUF(array(
		'sectionId' => $arSection['ID'],
		'iblockId' => $arSection['IBLOCK_ID'],
		'select' => array(
			'UF_ELEMENT_DETAIL',
			'UF_OFFERS_TYPE',
			'UF_GALLERY_SIZE',
			'UF_GALLERY_TYPE',
			'UF_GALLERY_VIEW',
		),
		'filter' => array(
			'GLOBAL_ACTIVE' => 'Y',
		),
		'enums' => array(
			'UF_ELEMENT_DETAIL',
			'UF_OFFERS_TYPE',
			'UF_GALLERY_SIZE',
			'UF_GALLERY_TYPE',
			'UF_GALLERY_VIEW',
		),
	));
}

TSolution::CheckComponentTemplatePageBlocksParams($arParams, __DIR__);

$sViewElementTemplate = TSolution\Functions::getValueWithSection([
	'CODE' => 'CATALOG_PAGE_DETAIL',
	'SECTION_VALUE' => $arInherite['UF_ELEMENT_DETAIL'],
	'CUSTOM_VALUE' => ($arParams['ELEMENT_TYPE_VIEW'] === 'FROM_MODULE' ? $arTheme['CATALOG_PAGE_DETAIL']['VALUE'] : $arParams['ELEMENT_TYPE_VIEW']),
]);

$typeSKU = TSolution\Functions::getValueWithSection([
	'CODE' => 'CATALOG_PAGE_DETAIL_SKU',
	'SECTION_VALUE' => $arInherite['UF_OFFERS_TYPE']
]);
if ($arElement['TYPE'] != \Bitrix\Catalog\ProductTable::TYPE_SKU) {
	$typeSKU = TSolution::GetBackParametrsValues(SITE_ID)['TYPE_SKU'];
}

$gallerySize = TSolution\Functions::getValueWithSection([
	'CODE' => 'CATALOG_PAGE_DETAIL_GALLERY_SIZE',
	'SECTION_VALUE' => $arInherite['UF_GALLERY_SIZE']
]);
$galleryType = TSolution\Functions::getValueWithSection([
	'CODE' => 'CATALOG_PAGE_DETAIL_GALLERY_TYPE',
	'SECTION_VALUE' => $arInherite['UF_GALLERY_TYPE']
]);
$galleryView = TSolution\Functions::getValueWithSection([
	'CODE' => 'CATALOG_PAGE_DETAIL_GALLERY_VIEW',
	'SECTION_VALUE' => $arInherite['UF_GALLERY_VIEW']
]);

$arParams['OID'] = 0;
if ($arElement['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_SKU && $typeSKU == 'TYPE_1') {
	$context=\Bitrix\Main\Context::getCurrent();
	$request=$context->getRequest();
    if ($oidParam = TSolution::GetFrontParametrValue('CATALOG_OID')) {
        if ($oid = $request->getQuery($oidParam)) {
            if(array_key_exists($oid, current(CCatalogSku::getOffersList($arElement['ID'], $arElement['IBLOCK_ID'], null, ['ID', 'NAME'])))) {
                $arParams['OID'] = $oid;
            }
        }
	}
}

// is need left block or sticky panel?
$APPLICATION->SetPageProperty('MENU', 'N');
$bWithStickyBlock = false;
if(strpos($sViewElementTemplate, 'element_1') !== false){
	$bShowLeftBlock = false;
	$bWithStickyBlock = true;
} else {
	$bShowLeftBlock = $arTheme['LEFT_BLOCK_CATALOG_DETAIL']['VALUE'] === 'Y';
}
$bShowLeftBlock &= !defined('ERROR_404');
?>
<div class="main-wrapper flexbox flexbox--direction-row <?= $bShowLeftBlock || $bWithStickyBlock ? '' : 'catalog-maxwidth'?>">
	<div class="section-content-wrapper flex-1 <?=($bShowLeftBlock ? 'with-leftblock' : '')?>">
		<?TSolution::AddMeta(
			array(
				'og:description' => $arElement['PREVIEW_TEXT'],
				'og:image' => (($arElement['PREVIEW_PICTURE'] || $arElement['DETAIL_PICTURE']) ? CFile::GetPath(($arElement['PREVIEW_PICTURE'] ? $arElement['PREVIEW_PICTURE'] : $arElement['DETAIL_PICTURE'])) : false),
			)
		);?>

		<?if($arParams['AJAX_MODE'] == 'Y' && strpos($_SERVER['REQUEST_URI'], 'bxajaxid') !== false):?>
			<script type="text/javascript">
				setStatusButton();
			</script>
		<?endif;?>

		<?
		$galleryClasses = TSolution\Utils::implodeClasses([
			'gallery-size-'.$gallerySize,
			'gallery-type-'.$galleryType,
		]);
		?>

		<div class="product-container detail <?=$sViewElementTemplate;?> <?=$galleryClasses;?> clearfix">
			<?
			// cross sales for product
			global $arCrossItems;
			$oCrossSales = new \Aspro\Premier\CrossSales($arElement['ID'], $arParams);
			$arRules = $oCrossSales->getRules();
			$arCrossItems = [];
			$bUseAssociated = $bUseExpandables = false;

			// similar goods from cross sales
			if($arRules['ASSOCIATED'])
			{
				$arCrossItems['ASSOCIATED'] = $oCrossSales->getItems('ASSOCIATED');
				if(!empty($arCrossItems['ASSOCIATED'])){
					$bUseAssociated = true;
				}
			}

			// accessories goods from cross sales
			if($arRules['EXPANDABLES'])
			{
				$arCrossItems['EXPANDABLES'] = $oCrossSales->getItems('EXPANDABLES');
				if(!empty($arCrossItems['EXPANDABLES'])){
					$bUseExpandables = true;
				}
			}

			?>

			<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>
		</div>

        <?TSolution\Product\MetaInfo::getInstance()->set();?>
        <?TSolution::checkBreadcrumbsChain($arParams, $arSection, $arElement);?>

		<div class="bottom-links-block">
			<?// back url?>
			<?TSolution\Functions::showBackUrl(
				array(
					'URL' => ((isset($arSection) && $arSection) ? $arSection['SECTION_PAGE_URL'] : $arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']),
					'TEXT' => ($arParams['T_PREV_LINK'] ? $arParams['T_PREV_LINK'] : GetMessage('BACK_LINK')),
				)
			);?>
		</div>
	</div>
	<?if($bShowLeftBlock):?>
		<?TSolution::ShowPageType('left_block');?>
	<?endif;?>
</div>

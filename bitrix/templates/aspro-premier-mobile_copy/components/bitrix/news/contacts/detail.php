<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true) die();
$this->setFrameMode(true);

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

global $arTheme, $APPLICATION;
\TSolution\Extensions::init(['contacts', 'map']);

$bUseMap = TSolution::GetFrontParametrValue('CONTACTS_USE_MAP', SITE_ID) != 'N';
$typeMap = TSolution::GetFrontParametrValue('CONTACTS_TYPE_MAP');
$bUseFeedback = TSolution::GetFrontParametrValue('CONTACTS_USE_FEEDBACK', SITE_ID) != 'N';
$bUseTabs = $bUseMap && TSolution::GetFrontParametrValue('CONTACTS_USE_TABS', SITE_ID) != 'N';

// get element
$arItemFilter = TSolution::GetCurrentElementFilter($arResult["VARIABLES"], $arParams);
$arElement = TSolution\Cache::CIblockElement_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "N")), $arItemFilter, false, false, array('ID', 'NAME', 'PREVIEW_TEXT', 'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PROPERTY_ADDRESS'));
?>
<?if(!$arElement && $arParams['SET_STATUS_404'] !== 'Y'):?>
	<div class="alert alert-warning"><?=GetMessage("ELEMENT_NOTFOUND")?></div>
<?elseif(!$arElement && $arParams['SET_STATUS_404'] === 'Y'):?>
	<?TSolution::goto404Page();?>
<?else:?>
	<?
	TSolution::AddMeta(
		array(
			'og:description' => $arElement['PREVIEW_TEXT'],
			'og:image' => (($arElement['PREVIEW_PICTURE'] || $arElement['DETAIL_PICTURE']) ? CFile::GetPath(($arElement['PREVIEW_PICTURE'] ? $arElement['PREVIEW_PICTURE'] : $arElement['DETAIL_PICTURE'])) : false),
		)
	);
		
	TSolution::CheckComponentTemplatePageBlocksParams($arParams, __DIR__);

	$APPLICATION->SetPageProperty('HIDE_LEFT_BLOCK', 'Y');
	//$APPLICATION->SetPageProperty('TITLE_CLASS', ' hidden ');
	?>

	<?$this->SetViewTarget('more_text_title');?>
		<?if($arParams['USE_SHARE'] === 'Y' && $arElement):?>
			<?TSolution\Functions::showHeadingIcons([
				'CONTENT' => TSolution\Functions::showShareBlock(
					array(
						'INNER_CLASS' => 'item-action__inner item-action__inner--sm',
						'CLASS' => 'top',
						'RETURN' => true,
					)
				)]);?>
		<?endif;?>
		<?if($arParams['USE_RSS'] !== 'N'):?>
			<?TSolution\Functions::showHeadingIcons([
				'CONTENT' => TSolution\Functions::ShowRSSIcon(
					array(
						'INNER_CLASS' => 'item-action__inner item-action__inner--sm',
						'URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss'],
						'RETURN' => true,
					)
				)])?>
		<?endif;?>
	<?$this->endViewTarget()?>

	<?
	// element
	@include_once('page_blocks/'.$arParams["ELEMENT_TYPE_VIEW"].'.php');

	if(is_array($arElement["IBLOCK_SECTION_ID"]) && count($arElement["IBLOCK_SECTION_ID"]) > 1){
		TSolution::CheckAdditionalChainInMultiLevel($arResult, $arParams, $arElement);
	}
	?>
<?endif;?>
<div class="bottom-links-block">
    <?// back url?>
    <?TSolution\Functions::showBackUrl(
        array(
            'URL' => ((isset($arSection) && $arSection) ? $arSection['SECTION_PAGE_URL'] : $arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']),
            'TEXT' => ($arParams['T_PREV_LINK'] ? $arParams['T_PREV_LINK'] : GetMessage('BACK_LINK')),
        )
    );?>

    <?// share bottom?>
    <?if($arParams['USE_SHARE'] === 'Y' && $arElement):?>
        <?TSolution\Functions::showShareBlock(
            array(
                'CLASS' => 'top',
            )
        );?>
    <?endif;?>
</div>
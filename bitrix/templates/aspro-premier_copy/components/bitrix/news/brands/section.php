<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

global $APPLICATION;

// geting section items count and section
$arItemFilter = TSolution::GetCurrentSectionElementFilter($arResult["VARIABLES"], $arParams);
$arSectionFilter = TSolution::GetCurrentSectionFilter($arResult["VARIABLES"], $arParams);
$itemsCnt = TSolution\Cache::CIblockElement_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());
$arSection = TSolution\Cache::CIblockSection_GetList(array("CACHE" => array("TAG" => CCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "N")), $arSectionFilter, false, array('ID', 'NAME', 'DESCRIPTION', 'PICTURE', 'DETAIL_PICTURE'), true);
?>
<?if(!$arSection && $arParams['SET_STATUS_404'] !== 'Y'):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_NOTFOUND")?></div>
<?elseif(!$arSection && $arParams['SET_STATUS_404'] === 'Y'):?>
	<?TSolution::goto404Page();?>
<?else:?>
	<?
	TSolution::AddMeta(
		array(
			'og:description' => $arSection['DESCRIPTION'],
			'og:image' => (($arSection['PICTURE'] || $arSection['DETAIL_PICTURE']) ? CFile::GetPath(($arSection['PICTURE'] ? $arSection['PICTURE'] : $arSection['DETAIL_PICTURE'])) : false),
		)
	);

	TSolution::CheckComponentTemplatePageBlocksParams($arParams, __DIR__);
	?>

	<?$this->SetViewTarget('more_text_title');?>
		<?// rss?>
		<?if($arParams['USE_RSS'] !== 'N'):?>
			<?TSolution\Functions::showHeadingIcons([
				'CONTENT' => TSolution\Functions::ShowRSSIcon(
					array(
						'INNER_CLASS' => 'item-action__inner item-action__inner--sm',
						'URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss'],
						'RETURN' => true,
					)
				)])?>
				<?$arExtensions[] = 'item_action';?>
				<?\TSolution\Extensions::init($arExtensions);?>
		<?endif;?>
	<?$this->endViewTarget()?>
	
	<?if(!$itemsCnt):?>
		<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
	<?endif;?>

	<?
	// edit/add/delete buttons for edit mode
	$arSectionButtons = CIBlock::GetPanelButtons($arSection['IBLOCK_ID'], 0, $arSection['ID'], array('SESSID' => false, 'CATALOG' => true));
	$this->AddEditAction($arSection['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_EDIT'));
	$this->AddDeleteAction($arSection['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	?>
	<div class="main-section-wrapper" id="<?=$this->GetEditAreaId($arSection['ID'])?>">
		<?// section elements?>
		<?$sViewElementsTemplate = ($arParams["SECTION_ELEMENTS_TYPE_VIEW"] == "FROM_MODULE" ? TSolution::getFrontParametrValue('BRANDS_PAGE') : $arParams["SECTION_ELEMENTS_TYPE_VIEW"]); ?>
		<?@include_once('page_blocks/'.$sViewElementsTemplate.'.php');?>
	</div>
<?endif;?>
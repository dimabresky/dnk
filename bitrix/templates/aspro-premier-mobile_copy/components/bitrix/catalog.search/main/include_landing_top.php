<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>
<?if ($arLanding):?>
	<?
	if (
		$arLanding["DETAIL_PICTURE"] || 
		strlen($arLanding["PREVIEW_TEXT"]) ||
		$arLanding["PROPERTY_FORM_QUESTION_VALUE"] ||
		(
			$arLanding["PROPERTY_TIZERS_VALUE"] &&
			$arParams["IBLOCK_TIZERS_ID"]
		)
	):?>
		<?// top banner?>
		<?if ($arLanding['DETAIL_PICTURE']):?>
			<?
			// single detail image
			$templateData = [
				'BANNER_TOP_ON_HEAD' => isset($arLanding['PROPERTY_PHOTOPOS_ENUM_ID']) && $arLanding['PROPERTY_PHOTOPOS_VALUE'] == $arPhotoPosTypeEnums['TOP_ON_HEAD'],
				'BANNER_TOP_CONTENT' => isset($arLanding['PROPERTY_PHOTOPOS_ENUM_ID']) && $arLanding['PROPERTY_PHOTOPOS_VALUE'] == $arPhotoPosTypeEnums['TOP_CONTENT'],
			];
			$bTopImg = $templateData['BANNER_TOP_ON_HEAD'] || $templateData['BANNER_TOP_CONTENT'];

			$arDetailPicture = CFile::GetByID($arLanding['DETAIL_PICTURE'])->Fetch();

			$atrTitle = (strlen($arDetailPicture['DESCRIPTION']) ? $arDetailPicture['DESCRIPTION'] : (strlen($arDetailPicture['TITLE']) ? $arDetailPicture['TITLE'] : ''));
			$atrAlt = (strlen($arDetailPicture['DESCRIPTION']) ? $arDetailPicture['DESCRIPTION'] : (strlen($arDetailPicture['ALT']) ? $arDetailPicture['ALT'] : ''));

			\TSolution\Extensions::init(['detail', 'fancybox']);
			\TSolution\Banner\Transparency::setHeaderClasses($templateData);
			?>
			<?if ($bTopImg):?>
				<?if ($templateData['BANNER_TOP_ON_HEAD']):?>
					<?$this->SetViewTarget('side-over-title');?>
				<?else:?>
					<?$this->SetViewTarget('top_section_filter_content');?>
				<?endif;?>
			<?endif;?>
			
			<?TSolution\Functions::showBlockHtml([
				'FILE' => '/images/detail_single.php',
				'PARAMS' => [
					'TYPE' => $templateData['BANNER_TOP_ON_HEAD'] ? 'TOP_ON_HEAD' : ($templateData['BANNER_TOP_CONTENT'] ? 'TOP_CONTENT' : 'WIDE'),
					'URL' => $arDetailPicture['SRC'],
					'ALT' => $atrAlt,
					'TITLE' => $atrTitle,
					'TOP_IMG' => $bTopImg,
				],
			]);?>

			<?if ($bTopImg):?>
				<?$this->EndViewTarget();?>
			<?endif;?>
		<?endif;?>

		<?if (strlen($arLanding["PREVIEW_TEXT"])):?>
			<div class="group_description_block top font_15">
				<?=$arLanding["PREVIEW_TEXT"]?>
			</div>
		<?endif;?>

		<div class="seo_block">
			<?$APPLICATION->ShowViewContent('sotbit_seometa_top_desc');?>

			<?if ($arLanding["PROPERTY_FORM_QUESTION_VALUE"]):?>
				<div class="outer-rounded-x bordered p p--32 mb mb--32">
					<div class="order-info-block">
						<div class="line-block line-block--align-normal line-block--40">
							<div class="line-block__item flex-1">
								<div class="text color_222">
									<?$APPLICATION->IncludeComponent(
										'bitrix:main.include',
										'',
										array(
											'AREA_FILE_SHOW' => 'page',
											'AREA_FILE_SUFFIX' => 'landing',
											'EDIT_TEMPLATE' => ''
										)
									);?>
								</div>
							</div>
							<div class="line-block__item order-info-btns flexbox flexbox--align-end flexbox--align-start-to-991">
								<div class="line-block line-block--align-normal line-block--12">
									<div class="line-block__item">
										<span 
											class="btn btn-default btn-lg animate-load" 
											data-event="jqm" 
											data-param-id="<?=TSolution::getFormID('aspro_premier_question');?>" 
											data-name="question"
										>
											<span><?=htmlspecialcharsbx(TSolution::GetFrontParametrValue('EXPRESSION_FOR_ASK_QUESTION'))?></span>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?endif;?>

			<?if (
				$arLanding["PROPERTY_TIZERS_VALUE"] && 
				$arParams["IBLOCK_TIZERS_ID"]
			):?>
				<?$GLOBALS["arLandingTizers"] = array("ID" => $arLanding["PROPERTY_TIZERS_VALUE"]);?>
				<?$APPLICATION->IncludeComponent(
					"bitrix:news.list",
					"tizers-list",
					array(
						"IBLOCK_TYPE" => "aspro_premier_content",
						"IBLOCK_ID" => $arParams["IBLOCK_TIZERS_ID"],
						"NEWS_COUNT" => "4",
						"SORT_BY1" => "SORT",
						"SORT_ORDER1" => "ASC",
						"SORT_BY2" => "ID",
						"SORT_ORDER2" => "DESC",
						// "SMALL_BLOCK" => "Y",
						"FILTER_NAME" => "arLandingTizers",
						"FIELD_CODE" => array(
							0 => "PREVIEW_PICTURE",
							1 => "PREVIEW_TEXT",
							2 => "DETAIL_PICTURE",
							3 => "DETAIL_TEXT",
						),
						"PROPERTY_CODE" => array(
							0 => "TIZER_ICON",
							1 => "URL",
						),
						"CHECK_DATES" => "Y",
						"DETAIL_URL" => "",
						"AJAX_MODE" => "N",
						"AJAX_OPTION_JUMP" => "N",
						"AJAX_OPTION_STYLE" => "Y",
						"AJAX_OPTION_HISTORY" => "N",
						"CACHE_TYPE" => $arParams['CACHE_TYPE'],
						"CACHE_TIME" => "36000000",
						"CACHE_FILTER" => "Y",
						"CACHE_GROUPS" => "N",
						"PREVIEW_TRUNCATE_LEN" => "250",
						"ACTIVE_DATE_FORMAT" => "d F Y",
						"SET_TITLE" => "N",
						"SHOW_DETAIL_LINK" => "N",
						"SET_STATUS_404" => "N",
						"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
						"ADD_SECTIONS_CHAIN" => "N",
						"HIDE_LINK_WHEN_NO_DETAIL" => "N",
						"PARENT_SECTION" => "",
						"PARENT_SECTION_CODE" => "",
						"DISPLAY_TOP_PAGER" => "N",
						"DISPLAY_BOTTOM_PAGER" => "Y",
						"PAGER_TITLE" => "",
						"PAGER_SHOW_ALWAYS" => "N",
						"PAGER_TEMPLATE" => "ajax",
						"PAGER_DESC_NUMBERING" => "N",
						"PAGER_DESC_NUMBERING_CACHE_TIME" => "3600",
						"PAGER_SHOW_ALL" => "N",
						"DISPLAY_DATE" => "Y",
						"DISPLAY_NAME" => "Y",
						"DISPLAY_PICTURE" => "N",
						"DISPLAY_PREVIEW_TEXT" => "N",
						"AJAX_OPTION_ADDITIONAL" => "",
						"COMPONENT_TEMPLATE" => "tizers-list",
						"SET_BROWSER_TITLE" => "N",
						"SET_META_KEYWORDS" => "N",
						"SET_META_DESCRIPTION" => "N",
						"SET_LAST_MODIFIED" => "N",
						"INCLUDE_SUBSECTIONS" => "Y",
						"STRICT_SECTION_CHECK" => "N",
						"TYPE_IMG" => "left",
						"CENTERED" => "Y",
						"SIZE_IN_ROW" => "4",
						"PAGER_BASE_LINK_ENABLE" => "N",
						"SHOW_404" => "N",
						'ITEMS_OFFSET' => true,
						'IMAGES' => 'ICONS',
						'IMAGE_POSITION' => 'TOP',
						"MOBILE_SCROLLED" => true,
						"MAXWIDTH_WRAP" => "N",
						'ELEMENTS_COUNT' => 4,
						"MESSAGE_404" => ""
					),
					false, array("HIDE_ICONS" => "Y")
				);?>
			<?endif;?>

			<?$APPLICATION->ShowViewContent('sotbit_seometa_add_desc');?>
		</div>
	<?endif;?>

	<?if (strlen($arLanding['PROPERTY_H3_GOODS_VALUE'])):?>
		<h3 class="search-title"><?=$arLanding['PROPERTY_H3_GOODS_VALUE']?></h3>
	<?endif;?>
<?endif;?>
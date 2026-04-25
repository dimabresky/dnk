<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

use \Bitrix\Main\Localization\Loc;

$templateData = array_filter([
	'ELEMENT_CODE' => $arResult['CODE'],
]);

TSolution\Template\Page::setLinkedBlocks($arResult, $arParams, $templateData);

$bDetailPicutre = $arResult['DETAIL_PICTURE']['SRC'] ?? false;
?>
<?ob_start();?>
	<?if (
		$arResult['FIELDS']['PREVIEW_TEXT'] 
		|| $arResult['FIELDS']['DETAIL_TEXT'] 
		|| (
			isset($arResult['CONTACT_PROPERTIES']) 
			&& $arResult['CONTACT_PROPERTIES']
		)
	):?>
		<div class="partner-detail__card-info">
			<div class="partner-detail__content">
				<?if ($arResult['FIELDS']['PREVIEW_TEXT'] || $arResult['FIELDS']['DETAIL_TEXT']):?>
					<div class="partner-detail__text-wrapper">
						<div class="partner-detail__text overflow-block relative lineclamp-4">
							<?if($arResult['FIELDS']['PREVIEW_TEXT']):?>
								<div class="partner-detail__text-preview mb mb--16">
									<?if($arResult['PREVIEW_TEXT_TYPE'] == 'text'):?>
										<p><?=$arResult['FIELDS']['PREVIEW_TEXT']?></p>
									<?else:?>
										<?=$arResult['FIELDS']['PREVIEW_TEXT']?>
									<?endif;?>
								</div>
							<?endif?>

							<?if($arResult['DETAIL_TEXT_TYPE'] == 'text'):?>
								<p><?=$arResult['FIELDS']['DETAIL_TEXT']?></p>
							<?else:?>
								<?=$arResult['FIELDS']['DETAIL_TEXT']?>
							<?endif;?>
						</div>
					</div>
				<?endif;?>
			</div>

			<div class="partner-detail__spoiler active hide">
				<button type="button" class="partner-detail__spoiler-btn btn--no-btn-appearance font_15 fw-500 pointer link-opacity-color link-opacity-color--hover stroke-dark-parent-all mt mt--12 icon-block _choise">
					<span class="partner-detail__spoiler-btn-label"
						data-show="<?=Loc::getMessage("MORE_DETAIL_TEXT");?>"
						data-hide="<?=Loc::getMessage("HIDE_DETAIL_TEXT");?>"
					></span>
					<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down-hollow', 'partner-detail__spoiler-btn-icon ml ml--4 mt icon-block__icon stroke-dark-target stroke-dark-light', ['WIDTH' => 8, 'HEIGHT' => 5]);?>
				</button>
			</div>

			<?if ($arResult['CONTACT_PROPERTIES']):?>
				<div class="partner-detail__properties mt mt--24">
					<div class="line-block line-block--gap line-block--flex-wrap">
						<?foreach ($arResult['CONTACT_PROPERTIES'] as $property):?>
							<div class="partner-detail__property line-block__item">
								<div class="partner-detail__property-value button-rounded-x p-block p-block--4 p-inline p-inline--8">
									<?if ($property['TYPE'] == 'LINK'):?>
										<a rel="nofollow" target="_blank" href="<?=$property['HREF'];?>" class="dark_link">
											<?=$property['VALUE'];?>
										</a>
									<?else:?>
										<?=$property['VALUE'];?>
									<?endif;?>
								</div>
							</div>
						<?endforeach;?>
					</div>	
				</div>
			<?endif;?>	
		</div>
	<?endif;?>
<?$propsPartnersHTML = ob_get_clean();?>

<div class="partner-detail mb mb--40">
	<div class="partner-detail__card">
		<?
		$bSideImage = $arResult['FIELDS']['DETAIL_PICTURE'] 
			&& in_array(($arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] ?? ''), ['LEFT', 'RIGHT']);
		?>
		<?if ($bSideImage): // line-blocks for side image?>
			<div class="mb mb--64">
				<div class="partner-detail__card-images line-block line-block--gap line-block--gap-12 line-block--align-normal<?=$arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] === 'RIGHT' ? ' line-block--row-reverse' : '';?>">
					<div class="line-block__item flex-1 partner-detail__card-detail-image">
		<?endif;?>

		<?// top banner?>
		<?$templateData['BANNER_TOP_ON_HEAD'] = false;?>
		<?if ($arResult['FIELDS']['DETAIL_PICTURE']):?>
			<?
			// single detail image
			$templateData['BANNER_TOP_ON_HEAD'] = isset($arResult['PROPERTIES']['PHOTOPOS']) && $arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] == 'TOP_ON_HEAD';

			$atrTitle = (strlen($arResult['DETAIL_PICTURE']['DESCRIPTION']) ? $arResult['DETAIL_PICTURE']['DESCRIPTION'] : (strlen($arResult['DETAIL_PICTURE']['TITLE']) ? $arResult['DETAIL_PICTURE']['TITLE'] : $arResult['NAME']));
			$atrAlt = (strlen($arResult['DETAIL_PICTURE']['DESCRIPTION']) ? $arResult['DETAIL_PICTURE']['DESCRIPTION'] : (strlen($arResult['DETAIL_PICTURE']['ALT']) ? $arResult['DETAIL_PICTURE']['ALT'] : $arResult['NAME']));

			$bTopImg = (strpos($arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'], 'TOP') !== false);
			$templateData['IMG_TOP_SIDE'] = isset($arResult['PROPERTIES']['PHOTOPOS']) && $arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] == 'TOP_SIDE';
			?>
			<?if (!$templateData['IMG_TOP_SIDE']):?>
				<?if ($bTopImg):?>
					<?if ($templateData['BANNER_TOP_ON_HEAD']):?>
						<?$this->SetViewTarget('side-over-title');?>
					<?else:?>
						<?$this->SetViewTarget('top_section_filter_content');?>
					<?endif;?>
				<?endif;?>

				<?\TSolution\Functions::showBlockHtml([
					'FILE' => '/images/detail_single.php',
					'PARAMS' => [
						'TYPE' => $arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'],
						'URL' => $arResult['DETAIL_PICTURE']['SRC'],
						'ALT' => $atrAlt,
						'TITLE' => $atrTitle,
						'TOP_IMG' => $bTopImg,
					],
				]);?>

				<?if ($bTopImg):?>
					<?$this->EndViewTarget();?>
				<?endif;?>
			<?endif;?>
		<?endif;?>

		<?if ($bSideImage): // line-blocks for side image?>
				</div>
				<div class="line-block__item flex-1">
		<?endif;?>

		<?
		$cardImageWrapperClassList = ['bordered outer-rounded-x flexbox height-100'];
		if ($bDetailPicutre) {
			$cardImageWrapperClassList[] = 'flexbox--align-center';
		} else {
			$cardImageWrapperClassList[] = 'partner-detail__card-image--no-detail-picture';
		}
		if (!$bSideImage) {
			$cardImageWrapperClassList[] = 'mb mb--64';
		}

		$cardImageWrapperClassList = TSolution\Utils::implodeClasses($cardImageWrapperClassList);
		?>
		<div class="partner-detail__card-image white-bg-fixed bordered outer-rounded-x flexbox height-100 p p--40 <?=$cardImageWrapperClassList;?>">
			<?
			$cardImageClassList = ['image-rounded-x flexbox flexbox--justify-center m m--40'];
			if (!$bDetailPicutre) {
				$cardImageClassList[] = 'sticky-from-992';
			}

			$cardImageClassList = TSolution\Utils::implodeClasses($cardImageClassList);
			?>
			<div class="partner-detail__image <?=$cardImageClassList;?>">
				<img src="<?=$arResult['IMAGE']['PREVIEW_SRC'];?>" alt="<?=htmlspecialchars($arResult['IMAGE']['ALT']);?>" title="<?=htmlspecialchars($arResult['IMAGE']['TITLE']);?>">
			</div>

			<?if (!$bDetailPicutre): // show description|props right to logo if no detail picture?>
				<?=$propsPartnersHTML;?>
			<?endif;?>
		</div>

		<?if ($bSideImage): // line-blocks for side image?>
					</div>
				</div>
			</div>
		<?endif;?>

		<?if ($bDetailPicutre): // show detail picture left|right?>
			<?=$propsPartnersHTML;?>
		<?endif;?>
	</div>
</div>

<?/* linked blocks */?>

<?// files?>
<?$templateData['DOCUMENTS'] = boolval($arResult['DOCUMENTS']);?>
<?if ($templateData['DOCUMENTS']):?>
	<?$this->SetViewTarget('PRODUCT_FILES_INFO');?>
		<?TSolution\Functions::showBlockHtml([
			'FILE' => '/documents.php',
			'PARAMS' => [
				'ITEMS' => $arResult['DOCUMENTS']
			],
		]);?>
	<?$this->EndViewTarget();?>
<?endif;?>

<?/* */?>
<?
$cntBanners = count($arResult['ITEMS'][$arParams['BANNER_TYPE_THEME_CHILD']]['ITEMS']);

$sideBannersClasses = ['banners-big__side-items', 'banners-big__side-items--right', 'gap'];
if ($arParams['NO_OFFSET_BANNER']) {
	$sideBannersClasses[] = 'gap--0';
}
else {
	$sideBannersClasses[] = 'gap--x';
}

$sideBannersClasses = implode(' ', $sideBannersClasses);
?>
<div class="<?=$sideBannersClasses?>">
	<?foreach ($arResult['ITEMS'][$arParams['BANNER_TYPE_THEME_CHILD']]['ITEMS'] as $key => $arItem):?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
		$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

		$bHasUrl = (strlen($arItem['PROPERTIES']['LINKIMG']['VALUE']) ? true : false);
		$arItem['FORMAT_NAME'] = strip_tags($arItem['~NAME']);

		$sideBannerItemClasses = ['banners-big__side-item', 'theme-light', 'dark-block-after'];
		if ($cntBanners > 1) {
			$sideBannerItemClasses[] = 'banners-big__side-item--height-50';
		}
		if (!$arParams['NO_OFFSET_BANNER']) {
			$sideBannerItemClasses[] = 'outer-rounded-x';
		}

		$sideBannerItemClasses = implode(' ', $sideBannerItemClasses);
		?>
		<div class="<?=$sideBannerItemClasses?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
			<div class="banners-big__side-item-image" style="background-image:url(<?=($arItem['DETAIL_PICTURE']['SRC'] ? $arItem['DETAIL_PICTURE']['SRC'] : $arItem['PREVIEW_PICTURE']['SRC'])?>)"></div>

			<?if ($bHasUrl):?>
				<?php
				$link = trim((string)$arItem['PROPERTIES']['LINKIMG']['VALUE']);
				if (stripos($link, 'javascript:') === 0) { $link = ''; }
				$target = (string)$arItem['PROPERTIES']['TARGETS']['VALUE_XML_ID'];
				if (!in_array($target, ['_blank', '_self', '_parent', '_top'], true)) { $target = ''; }
				?>
				<a href="<?=htmlspecialcharsbx($link)?>" class="banners-big__side-item-link no-decoration" title="<?=htmlspecialcharsbx($arItem['FORMAT_NAME'])?>" <?=($target ? 'target="'.htmlspecialcharsbx($target).'"' : '')?>>
			<?endif;?>

			<div class="flexbox flexbox--direction-column flexbox--justify-end height-100">
				<div class="p p--32">
					<?if (strlen($arItem['PROPERTIES']['TOP_TEXT']['VALUE'])):?>
						<div class="banners-big__side-item-top-text font_13 lineclamp-3 white_text_black_reverse mb mb--8"><?=htmlspecialcharsbx($arItem['PROPERTIES']['TOP_TEXT']['VALUE']);?></div>
					<?endif?>
					<div class="banners-big__side-item-title switcher-title font_18 fw-500 lineclamp-5 white_text_black_reverse"><?=htmlspecialcharsbx($arItem['NAME']);?></div>
				</div>
			</div>

			<?if ($bHasUrl):?>
				</a>
			<?endif;?>
		</div>
	<?endforeach;?>
</div>

<?
$bClickToShowForthDepth = TSolution::getFrontParametrValue('CLICK_TO_SHOW_4DEPTH') === 'Y';

$dropdownItemClassList = ['header-menu__dropdown-item'];
if ($countElementMenu) {
	$dropdownItemClassList[] = $countElementMenu;
}
if ($bShowChilds) {
	$dropdownItemClassList[] = 'header-menu__dropdown-item--with-dropdown';
}
if ($arSubItem["SELECTED"]) {
	$dropdownItemClassList[] = 'active';
}
if ($bHasPicture) {
	$dropdownItemClassList[] = 'has_img';
}
if (TSolution::getFrontParametrValue('IMAGES_WIDE_MENU_POSITION') === 'TOP') {
	$dropdownItemClassList[] = 'line-block--column';
}

$dropdownItemClassList = TSolution\Utils::implodeClasses($dropdownItemClassList);
?>
<li class="<?=$dropdownItemClassList;?> line-block line-block--gap line-block--gap-20 line-block--align-normal pr pr--20">
	<?if ($bHasPicture):?>
		<?
		$imageClassList = ['header-menu__dropdown-item-img'];
		$imageSize = 0;

		switch (TSolution::getFrontParametrValue('IMAGES_WIDE_MENU')) {
			case 'ICONS':
				$imageSize = 40;
				$imageClassList[] = 'header-menu__dropdown-item-img--sm';
				break;
			case 'TRANSPARENT_PICTURES':
				$imageSize = 56;
				$imageClassList[] = 'header-menu__dropdown-item-img--md';
				break;
			case 'PICTURES':
				$imageSize = 72;
				$imageClassList[] = 'header-menu__dropdown-item-img--lg';
				if ($bPicture) {
					$imageClassList[] = 'header-menu__dropdown-item-img--cover rounded overflow-block';
				}
				break;
		}

		if ($bIcon) {
			$arImg = CFile::ResizeImageGet($arSubItem['PARAMS']['ICON'], array('width' => $imageSize, 'height' => $imageSize), BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
		} elseif ($bTransparentPicture) {
			$arImg = CFile::ResizeImageGet($arSubItem['PARAMS']['TRANSPARENT_PICTURE'], array('width' => $imageSize, 'height' => $imageSize), BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
		} elseif ($bPicture) {
			$arImg = CFile::ResizeImageGet($arSubItem['PARAMS']['PICTURE'], array('width' => $imageSize*1.5, 'height' => $imageSize*1.5), BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
		}

		$imageClassList = TSolution\Utils::implodeClasses($imageClassList);
		?>
		<?if (is_array($arImg)):?>
			<div class="<?=$imageClassList;?> no-shrinked line-block__item">
				<div class="header-menu__dropdown-item-img-inner">
					<a href="<?=$arSubItem["LINK"];?>">
						<?if ($bIcon):?>
							<?if (TSolution::getFrontParametrValue('COLORED_CATALOG_ICON') === 'Y'):?>
								<?TSolution\Functions::showSVG([
									'PATH' => $arImg['src']
								]);?>
							<?else:?>
								<?=TSolution::showIconSvg('', $arImg['src'], 'fill-theme');?>
							<?endif;?>
						<?else:?>
							<img src="<?=$arImg["src"];?>" alt="<?=$arSubItem["TEXT"];?>" title="<?=$arSubItem["TEXT"];?>" height="<?=$imageSize;?>" width="<?=$imageSize;?>" />
						<?endif;?>
					</a>
				</div>
			</div>
		<?endif;?>
	<?endif;?>

	<div class="header-menu__wide-item-wrapper flex-1 line-block__item line-block line-block--column line-block--align-normal line-block--gap line-block--gap-12">
		<a class="header-menu__wide-child-link underline-hover font_16 link switcher-title line-block__item line-block line-block--gap line-block--gap-16 lineclamp-3" href="<?=$arSubItem["LINK"];?>">
			<span class="header-menu__wide-child-link-text link"><?=$arSubItem["TEXT"];?></span>
			<?if ($bShowChilds):?>
				<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', 'fill-dark-light header-menu__wide-submenu-right-arrow only_more_items icon-block__dropdown-icon icon-block__dropdown-icon--wide icon-block__icon--no-offset', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
			<?endif;?>
		</a>
		<?if ($bShowChilds):?>
			<?
			$iCountChilds = count($arSubItem["CHILD"]);
			$counterWide = 1;
			?>
			<ul class="header-menu__wide-submenu line-block__item line-block line-block--column line-block--gap line-block--gap-8 line-block--align-normal">
				<?foreach ($arSubItem["CHILD"] as $key => $arSubItem2):?>
					<?
					$bShowChilds = $arSubItem2["CHILD"] && $arParams["MAX_LEVEL"] > 3;

					$submenuItemClassList = ['header-menu__wide-submenu-item'];
					if ($counterWide > $iVisibleItemsMenu) {
						$submenuItemClassList[] = 'collapsed';
					}
					if ($counterWide == count($arSubItem["CHILD"])) {
						$submenuItemClassList[] = 'header-menu__wide-submenu-item--last';
					}
					if ($bShowChilds) {
						$submenuItemClassList[] = 'header-menu__wide-submenu-item--with-dropdown';
					}
					if ($arSubItem2["SELECTED"]) {
						$submenuItemClassList[] = 'active';
					}

					$submenuItemClassList = TSolution\Utils::implodeClasses($submenuItemClassList);
					?>
					<li class="<?=$submenuItemClassList;?> font_14 rounded-x line-block__item" <?=($counterWide > $iVisibleItemsMenu ? 'style="display: none;"' : '');?>>
						<div class="header-menu__wide-submenu-item-inner relative">
							<?
							$submenuItemLinkClassList = ['header-menu__wide-child-link'];
							if ($arSubItem2["SELECTED"]) {
								$submenuItemLinkClassList[] = 'fw-500';
							} else {
								$submenuItemLinkClassList[] = 'primary-color';
							}

							$submenuItemLinkClassList = TSolution\Utils::implodeClasses($submenuItemLinkClassList);
							?>
							<a class="<?=$submenuItemLinkClassList;?> no-decoration lineclamp-3" href="<?=$arSubItem2["LINK"];?>">
								<span class="header-menu__wide-submenu-item-name link underline-hover"><?=$arSubItem2["TEXT"];?></span><?if ( $bShowChilds && $bClickToShowForthDepth ):?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?endif;/*!!!hack for correct move icon to new line!!!*/?><?
								?><?if ($bShowChilds && $bClickToShowForthDepth):?><?
									?><button type="button" class="btn--no-btn-appearance toggle_block icon-block__icon icon-block__icon--no-offset"><?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down-hollow', 'header-menu__wide-submenu-right-arrow menu-arrow stroke-dark-light', ['WIDTH' => 8,'HEIGHT' => 5]);?></button>
								<?endif;?>
								<?if ($bShowChilds):?>
									<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', 'fill-dark-light header-menu__wide-submenu-right-arrow only_more_items', ['WIDTH' => 5, 'HEIGHT' => 3]);?>
								<?endif;?>
							</a>
							<?if ($bShowChilds):?>
								<div class="submenu-wrapper"<?=($bClickToShowForthDepth ? ' style="display:none"' : '')?>>
									<ul class="header-menu__wide-submenu pt pt--8 pb pb--8 pl pl--8 line-block line-block--column line-block--align-normal line-block--gap line-block--gap-8">
										<?foreach ($arSubItem2["CHILD"] as $arSubItem3):?>
											<li class="header-menu__wide-submenu-item<?=$arSubItem3['SELECTED'] ? ' active' : '';?>">
												<div class="header-menu__wide-submenu-item-inner">
													<a class="font_14 link underline-hover primary-color lineclamp-3 header-menu__wide-child-link<?=$arSubItem3['SELECTED'] ? ' fw-500' : '';?>" href="<?=$arSubItem3["LINK"];?>"><span class="header-menu__wide-submenu-item-name"><?=$arSubItem3["TEXT"];?></span></a>
												</div>
											</li>
										<?endforeach;?>
									</ul>
								</div>
							<?endif;?>
						</div>
					</li>
					<?$counterWide++;?>
				<?endforeach;?>

				<?if ($iCountChilds > $iVisibleItemsMenu && $bCurrentItemWideMenu):?>
					<li class="show-more-items-btn font_14 mt mt--0 mb mb--0" role="none">
						<button type="button" class="dotted no-decoration-hover width-100 text-align-left with_dropdown svg relative btn--no-btn-appearance color_dark">
							<?=\Bitrix\Main\Localization\Loc::getMessage("S_MORE_ITEMS");?>
						</button>
					</li>
				<?endif;?>
			</ul>
		<?endif;?>
	</div>
</li>

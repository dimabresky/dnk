<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc;

$arrowParent = TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right', 'icon-block__dropdown-icon icon-block__icon--size-12 down menu-arrow fill-dark-light-block', ['WIDTH' => 3, 'HEIGHT' => 5]);
$arrowPointer = TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'wrapper wrapper--16 stroke-dark-target arrow-parent__item-arrow mt mt--4', ['WIDTH' => 5, 'HEIGHT' => 10]);

ob_start();
	TSolution\Functions::showBlockHtml(['FILE' => 'menu/mobile/partials/button_prev.php']);
$buttonBack = trim(ob_get_clean());
?>
<div class="mobilemenu__menu mobilemenu__menu--with-hover mobilemenu__menu--top">
	<ul class="mobilemenu__menu-list">
		<?foreach ($arResult as $arItem):?>
			<?
			$bShowChilds = $arParams['MAX_LEVEL'] > 1;
			$bParent = $arItem['CHILD'] && $bShowChilds;
			$bShowButton = $arItem['PARAMS']['SHOW_BUTTON'] ?? false;
			?>
			<li class="mobilemenu__menu-item<?=($arItem['SELECTED'] ? ' mobilemenu__menu-item--selected' : '');?><?=($bParent ? ' mobilemenu__menu-item--parent' : '');?>">
				<div class="<?=!$bShowButton ? ' link-wrapper' : '';?>">
					<?if ($bShowButton):?>
						<button type="button" class="btn btn-default btn-wide relative toggle_block btn-lg">
							<span class="font_15"><?=$arItem['TEXT'];?></span>
						</button>
					<?else:?>
						<a class="mobilemenu__item-link no-decoration" href="<?=$arItem['LINK'];?>" title="<?=htmlspecialcharsbx($arItem['TEXT']);?>">
							<span class="font_15"><?=$arItem['TEXT'];?></span>
							<?=$bParent ? $arrowParent : '';?>
						</a>
						<?if ($bParent):?>
							<span class="toggle_block"></span>
						<?endif;?>
					<?endif;?>
				</div>

				<?if ($bParent):?>
					<ul class="mobilemenu__menu-dropdown dropdown line-block--gap-0">
						<?=$buttonBack;?>

						<li class="mobilemenu__menu-item mobilemenu__menu-item--title">
							<div class="link-wrapper">
								<a class="mobilemenu__item-link no-decoration flexbox pt pt--8 mb mb--16 stroke-dark-parent-all stroke-dark-light mobilemenu__menu-parent-link" href="<?=$arItem['LINK'];?>">
									<span class="line-block line-block--gap line-block--gap-12">
										<span class="font_18 fw-500"><?=$arItem['TEXT'];?></span>
										<?=$arrowPointer;?>
									</span>
								</a>
							</div>
						</li>

						<?foreach ($arItem['CHILD'] as $arSubItem):?>
							<?
							$bShowChilds = $arParams['MAX_LEVEL'] > 2;
							$bParent = $arSubItem['CHILD'] && $bShowChilds;
							?>
							<li class="mobilemenu__menu-item<?=($arSubItem['SELECTED'] ? ' mobilemenu__menu-item--selected' : '');?><?=($bParent ? ' mobilemenu__menu-item--parent' : '');?>">
								<div class="link-wrapper ">
									<a class="mobilemenu__item-link no-decoration" href="<?=$arSubItem['LINK'];?>" title="<?=htmlspecialcharsbx($arSubItem['TEXT']);?>">
										<span class="font_15"><?=$arSubItem['TEXT'];?></span>
										<?=$bParent ? $arrowParent : '';?>
									</a>
									<?if ($bParent):?>
										<span class="toggle_block"></span>
									<?endif;?>
								</div>

								<?if ($bParent):?>
									<ul class="mobilemenu__menu-dropdown dropdown">
										<?=$buttonBack;?>

										<li class="mobilemenu__menu-item mobilemenu__menu-item--title">
											<div class="link-wrapper">
												<a class="mobilemenu__item-link no-decoration flexbox pt pt--8 mb mb--16 stroke-dark-parent-all stroke-dark-light mobilemenu__menu-parent-link" href="<?=$arSubItem['LINK'];?>">
													<span class="line-block line-block--gap line-block--gap-12">
														<span class="font_18 fw-500"><?=$arSubItem['TEXT'];?></span>
														<?=$arrowPointer;?>
													</span>
												</a>
											</div>
										</li>

										<?foreach ($arSubItem["CHILD"] as $arSubSubItem):?>
											<?
											$bShowChilds = $arParams['MAX_LEVEL'] > 3;
											$bParent = $arSubSubItem['CHILD'] && $bShowChilds;
											?>
											<li class="mobilemenu__menu-item<?=($arSubSubItem['SELECTED'] ? ' mobilemenu__menu-item--selected' : '');?><?=($bParent ? ' mobilemenu__menu-item--parent' : '');?>">
												<div class="link-wrapper fill-dark-light-block">
													<a class="mobilemenu__item-link no-decoration" href="<?=$arSubSubItem['LINK'];?>" title="<?=htmlspecialcharsbx($arSubSubItem['TEXT']);?>">
														<span class="font_15"><?=$arSubSubItem['TEXT'];?></span>
														<?=$bParent ? $arrowParent : '';?>
													</a>
													<?if ($bParent):?>
														<span class="toggle_block"></span>
													<?endif;?>
												</div>

												<?if ($bParent):?>
													<ul class="mobilemenu__menu-dropdown dropdown">
														<?=$buttonBack;?>

														<li class="mobilemenu__menu-item mobilemenu__menu-item--title">
															<div class="link-wrapper">
																<a class="mobilemenu__item-link no-decoration flexbox pt pt--8 mb mb--16 stroke-dark-parent-all stroke-dark-light mobilemenu__menu-parent-link" href="<?=$arSubSubItem['LINK'];?>">
																	<span class="line-block line-block--gap line-block--gap-12">
																		<span class="font_18 fw-500"><?=$arSubSubItem['TEXT'];?></span>
																		<?=$arrowPointer;?>
																	</span>
																</a>
															</div>
														</li>

														<?foreach ($arSubSubItem["CHILD"] as $arSubSubSubItem):?>
															<li class="mobilemenu__menu-item bg-opacity-theme-parent-hover <?=($arSubSubSubItem['SELECTED'] ? ' mobilemenu__menu-item--selected' : '');?>">
																<div class="link-wrapper">
																	<a class="mobilemenu__item-link no-decoration" href="<?=$arSubSubSubItem['LINK'];?>" title="<?=htmlspecialcharsbx($arSubSubSubItem['TEXT']);?>">
																		<span class="font_15"><?=$arSubSubSubItem['TEXT'];?></span>
																	</a>
																</div>
															</li>
														<?endforeach;?>
													</ul>
												<?endif;?>
											</li>
										<?endforeach;?>
									</ul>
								<?endif;?>
							</li>
						<?endforeach;?>
					</ul>
				<?endif;?>
			</li>
		<?endforeach;?>
	</ul>
</div>

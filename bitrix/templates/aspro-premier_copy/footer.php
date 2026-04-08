							<?if(!$isIndex):?>
								<?TSolution::checkRestartBuffer();?>
							<?endif;?>
							<?IncludeTemplateLangFile(__FILE__);?>
							<?global $arTheme, $isIndex, $is404;?>
							<?if(!$isIndex):?>
									<?if($is404):?>
										</div>
									<?else:?>
											<?TSolution::get_banners_position('CONTENT_BOTTOM');?>
											</div> <?// class=right_block?>
											<?if($APPLICATION->GetProperty("MENU") != "N" && !defined("ERROR_404")):?>
												<?TSolution::ShowPageType('left_block');?>
											<?endif;?>
										</div><?// class=col-md-12 col-sm-12 col-xs-12 content-md?>
									<?endif;?>
									<?if($APPLICATION->GetProperty("FULLWIDTH")!=='Y'):?>
										</div><?// class="maxwidth-theme?>
									<?endif;?>
								</div><?// class=row?>
							<?else:?>
								<?if (!TSolution::IsMainUiPage()):?>
									<?TSolution::ShowPageType('indexblocks');?>
								<?endif;?>
							<?endif;?>
						</div><?// class=container?>
						<?TSolution::get_banners_position('FOOTER');?>
					</div><?// class=main?>
				</div><?// class=body?>
				<?TSolution::ShowPageType('footer');?>
			</div><?// class=layout__right-column?>

			<?if ($APPLICATION->GetProperty('SHOW_LAYOUT_ASIDE') === 'Y'):?>
				<?TSolution::showPageType('left_column');?>
			<?endif;?>
		</div><?// class=layout?>
		
		<?@include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/footer/bottom_footer.php'));?>
                <script>
                        (function(w,d,u){
                                var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                                var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                        })(window,document,'https://cdn-ru.bitrix24.by/b30800294/crm/site_button/loader_2_msdl8k.js');
                </script>

	</body>
</html>
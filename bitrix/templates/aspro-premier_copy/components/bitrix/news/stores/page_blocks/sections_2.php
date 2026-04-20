<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main\Localization\Loc;

$bHasMapMarkers = false;
if (isset($arItems) && is_array($arItems)) {
	foreach ($arItems as $arItemMapCheck) {
		if (!empty($arItemMapCheck['PROPERTY_MAP_VALUE'])) {
			$bHasMapMarkers = true;
			break;
		}
	}
}
$bShowMapListSplit = ($itemsCnt && $bUseMap && $bHasMapMarkers);
$bUseTabsEffective = ($bUseTabs && $bUseMap && !$bShowMapListSplit);
?>
<div class="contacts-v2" itemscope itemtype="http://schema.org/LocalBusiness">
	<?//hidden text for validate microdata?>
	<div class="hidden">
		<?global $arSite;?>
		<span itemprop="name"><?=$arSite["NAME"]?></span>
	</div>

	<div class="contacts__row">
		<div class="contacts__col contacts__col--left flex-1">
			<div class="contacts__content-wrapper">
				<div class="contacts__ajax_items">
					<?
					// AJAX replaces everything between checkRestartBuffer calls (tabs + tab pane markup).
					TSolution::checkRestartBuffer($bFront = true, $param = '', $reset = true);
					?>
					<div class="contacts__panel-wrapper">
						<?
						if($bUseTabsEffective){
							include realpath(__DIR__.'/../include_tabs.php');
						}
						?>
					</div>

					<?if($bUseTabsEffective):?>
						<div class="contacts__tab-content contacts__tab-content--map">
					<?endif;?>

					<?if($itemsCnt):?>
						<?if ($bShowMapListSplit):?>
							<div class="contacts__desc" itemprop="description">
								<?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-regions-desc.php", Array(), Array("MODE" => "html", "NAME" => "Description"));?>
							</div>
							<div class="contacts__map-list-split">
								<div class="contacts__map-list-split__list">
									<?@include_once($arParams["SECTION_ELEMENTS_TYPE_VIEW"].'.php');?>
								</div>
								<div class="contacts__map-list-split__map">
									<?include realpath(__DIR__.'/../include_map.php');?>
								</div>
							</div>
						<?else:?>
							<?
							if($bUseMap){
								include realpath(__DIR__.'/../include_map.php');
							}
							?>

							<div class="contacts__desc" itemprop="description">
								<?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-regions-desc.php", Array(), Array("MODE" => "html", "NAME" => "Description"));?>
							</div>

							<?@include_once($arParams["SECTION_ELEMENTS_TYPE_VIEW"].'.php');?>
						<?endif;?>
					<?else:?>
						<div class="alert alert-warning"><?=GetMessage('SECTION_EMPTY')?></div>

						<?@include_once($arParams["SECTION_ELEMENTS_TYPE_VIEW"].'.php');?>
					<?endif;?>

					<?if($bUseTabsEffective):?>
						</div>
					<?endif;?>

					<?
					// die if ajax
					TSolution::checkRestartBuffer($bFront = true);
					?>
				</div>
			</div>
		</div>

		<?if ($arParams['STICKY_PANEL'] !== 'N' && (!defined('STORES_PAGE') || !STORES_PAGE)):?>
			<div class="contacts__col contacts__col--right">
				<?ob_start();?>
				<?TSolution::showContactImg();?>
				<?$htmlImage = trim(ob_get_clean());?>

				<div class="contacts__sticky-panel bordered sticky-block outer-rounded-x<?=($htmlImage ? '' : ' contacts__sticky-panel--without-image')?>">
					<?if($htmlImage):?>
						<div class="contacts__sticky-panel__image">
							<?=$htmlImage?>
							<div class="contact-property contact-property--address">
								<div class="contact-property__label font_12 color_light fw-500"><?=Loc::getMessage('T_CONTACTS_MAIN_OFFICE');?></div>
							</div>
						</div>
					<?endif;?>

					<div class="contacts__sticky-panel__info">
							<?TSolution::showContactAddr(['CLASS' => 'font_18 color_dark switcher-title']);?>
							<div class="contacts__sticky-panel__properties mt mt--6">
								<div class="contacts__sticky-panel__property">
									<?TSolution::showContactSchedule([
										'CLASS' => 'font_13 secondary-color']
									);?>
								</div>
								<div class="contacts__sticky-panel__property mt mt--16">
									<?TSolution::showContactPhones([
										'LABEL' => Loc::getMessage('T_CONTACTS_PHONE'),
									]);?>
								</div>
								<div class="contacts__sticky-panel__property mt mt--12">
									<?TSolution::showContactEmail([
										'LABEL' => Loc::getMessage('T_CONTACTS_EMAIL')
									]);?>
								</div>
							</div>
							<?if($bUseFeedback):?>
								<div class="contacts__sticky-panel__btn-wraper pt pt--24">
									<button type="button" class="btn btn-secondary-black btn-wide animate-load" data-event="jqm" data-param-id="aspro_premier_question" data-name="question"><?=Loc::getMessage('T_CONTACTS_QUESTION2')?></button>
								</div>
							<?endif;?>
					</div>
				</div>
			</div>
		<?endif;?>
	</div>
</div>

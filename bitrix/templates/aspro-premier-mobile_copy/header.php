<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Aspro\Premier\Mobile\General as MSolution;
global $APPLICATION, $arTheme;

IncludeTemplateLangFile(__FILE__);
$bIncludedModule = \Bitrix\Main\Loader::includeModule('aspro.premier');
?><!DOCTYPE html>
<html lang="<?=LANGUAGE_ID?>">
	<head>
                <?include $_SERVER['DOCUMENT_ROOT'] . '/include/header/google_metrics_includer.php'?>
                
		<title><?$APPLICATION->ShowTitle()?></title>
		<?if($bIncludedModule):?><?MSolution::start();?><?endif;?>
                <link rel="preconnect" href="https://cdn.diginetica.net/">
                <link rel="preconnect" href="https://tracking.diginetica.net/">
                <link rel="preconnect" href="https://tracking-app.diginetica.net/">
                <script type="text/javascript">
                    var digiScript = document.createElement('script');
                    digiScript.src = '//cdn.diginetica.net/8995/client.js';
                    digiScript.defer = true;
                    digiScript.async = true;
                    document.head.appendChild(digiScript);
                </script>
	</head>
	<body id="main" class="site_<?=SITE_ID?> <?=($bIncludedModule ? MSolution::getConditionClass() : '')?>">
                
		<div class="bx_areas"><?if($bIncludedModule){TSolution::ShowPageType('header_counter');}?></div>

		<?if(!$bIncludedModule):?>
			<?$APPLICATION->SetTitle(GetMessage("ERROR_INCLUDE_MODULE_PREMIER_TITLE"));?>
			<?$APPLICATION->IncludeFile(SITE_DIR."include/error_include_module.php");?>
				</body></html>
			<?die();?>
		<?endif;?>

		<div class="layout">
			<div id="panel"><?$APPLICATION->ShowPanel();?></div>
			<?$arTheme = $APPLICATION->IncludeComponent("aspro:theme.premier", "", array(), false, ['HIDE_ICONS' => 'Y']);?>
			<?include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/aspro-premier/defines.php');?>

			<?TSolution::get_banners_position('TOP_HEADER');?>
			<div id="mobileheader">
				<?MSolution::showPageTypeFromSolution('header_mobile');?>
				<div id="mobilemenu" class="mobile-scroll scrollbar">
					<?MSolution::showPageTypeFromSolution('header_mobile_menu');?>
				</div>
			</div>
			<div id="mobilefilter" class="scrollbar-filter"><?$APPLICATION->ShowViewContent('filter_content');?></div>
			<div id="popup-offers" class="scrollbar-filter scrollbar-filter--offers"><?$APPLICATION->ShowViewContent('offers_content');?></div>
			<?TSolution::get_banners_position('TOP_UNDERHEADER');?>
			<main>
				<?if(!$isIndex && !$is404 && !$isForm):?>
					<?$APPLICATION->ShowViewContent('section_bnr_content');?>
					<?if($APPLICATION->GetProperty("HIDETITLE")!=='Y'):?>
						<!--title_content-->
						<?MSolution::showPageTypeFromSolution('page_title');?>
						<!--end-title_content-->
					<?endif;?>
					<?$APPLICATION->ShowViewContent('top_section_filter_content');?>
					<?$APPLICATION->ShowViewContent('top_detail_content');?>
				<?endif; // if !$isIndex && !$is404 && !$isForm?>

				<?if(!$isIndex):?>
					<?if($APPLICATION->GetProperty("FULLWIDTH")!=='Y'):?>
						<div class="maxwidth-theme">
					<?endif;?>
					<?TSolution::get_banners_position('CONTENT_TOP');?>
				<?endif;?>
				<?TSolution::checkRestartBuffer();?>

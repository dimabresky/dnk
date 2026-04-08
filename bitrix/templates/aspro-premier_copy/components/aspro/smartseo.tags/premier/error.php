<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

\Bitrix\Main\UI\Extension::load('ui.alerts');
?>
<?if($GLOBALS['USER']->IsAdmin()):?>
	<div class="aspro-smartseo-content__wrapper">
	    <div class="ui-alert ui-alert-icon-danger">
	      <span class="ui-alert-message"><?= implode('<br>', $arResult['ERROR_MESSAGE']) ?></span>
	    </div>
	</div>
<?endif;?>
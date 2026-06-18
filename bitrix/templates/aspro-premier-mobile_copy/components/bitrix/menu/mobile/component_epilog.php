<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$templateFolder = $this->{'__template'}->{'__folder'};
$assetPath = $GLOBALS['APPLICATION']->oAsset->getFullAssetPath($templateFolder.'/script.js');
?>
<script src="<?=$assetPath?>"></script>
<?if($GLOBALS['arTheme']['NLO_MENU']['VALUE'] === 'Y'):?>
    <script>BX.loadCSS(['<?=$GLOBALS['APPLICATION']->oAsset->getFullAssetPath($templateFolder.'/style.css');?>']);</script>
<?endif;?>

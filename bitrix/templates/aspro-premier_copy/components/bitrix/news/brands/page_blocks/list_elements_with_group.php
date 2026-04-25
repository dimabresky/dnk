<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?use \Aspro\Premier as Solution;?>

<?$APPLICATION->SetPageProperty("HIDE_LEFT_BLOCK", "Y");?>

<?//popular brands?>
<?include_once('with_group/popular.php');?>

<?
$arAgentInfo = Solution\Agents\Brand::getInfo(Solution\Agents\Brand::getAlphabetAgentName($arParams['IBLOCK_ID']));
$bUseAgent = $arParams['USE_AGENT'] === 'Y';
if ($bUseAgent) {
	if ($arAgentInfo && $arAgentInfo['ACTIVE'] === 'N') {
		Solution\Agents\Common::update($arAgentInfo['ID'], ['ACTIVE' => 'Y']);
	} else {
		Solution\Agents\Brand::addAphabet(Solution\Agents\Brand::getAlphabetAgentName($arParams['IBLOCK_ID']));
	}
} elseif ($arAgentInfo && $arAgentInfo['ACTIVE'] === 'Y') {
	Solution\Agents\Common::update($arAgentInfo['ID'], ['ACTIVE' => 'N']);
}
?>

<?if (!$bUseAgent) {
	$arFilterLetters = Solution\Brand::getAlphabet($arParams['IBLOCK_ID']);
} else {
	if (!($arFilterLetters = TSolution::unserialize(\Bitrix\Main\Config\Option::get(TSolution::moduleID, 'FILTER_BRANDS_LETTERS', serialize(array()), SITE_ID)))) {
		$arFilterLetters = Solution\Brand::getAlphabet($arParams['IBLOCK_ID']);
	}
}?>

<?if ($arFilterLetters):?>
	<?//show filter?>
	<?if (count($arFilterLetters) > 1):?>
		<?include_once('with_group/filter.php');?>
		<?TSolution\Extensions::init('chip');?>
	<?endif;?>

	<?//show elements?>
	<?include_once('with_group/elements.php');?>
<?else:?>
	<div class="alert alert-warning"><?= GetMessage("SECTION_EMPTY") ?></div>
<?endif;?>

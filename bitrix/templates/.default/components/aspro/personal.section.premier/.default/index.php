<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$APPLICATION->SetAdditionalCSS($this->__folder.'/css/index.css');

$arCustomMainBlocks = CUtil::JsObjectToPhp($arParams['~CUSTOM_MAIN_BLOCKS'] ?? '[]', true);
$arCustomMainBlocks = is_array($arCustomMainBlocks) ? $arCustomMainBlocks : [];
foreach ($arCustomMainBlocks as $i => $arCustomMainBlock) {
	if (!is_array($arCustomMainBlock)) {
		unset($arCustomMainBlocks[$i]);
		continue;
	}

	$id = $arCustomMainBlocks[$i]['id'] = trim($arCustomMainBlock['id']);
	$name = $arCustomMainBlocks[$i]['name'] = trim($arCustomMainBlock['name']);
	$page = $arCustomMainBlocks[$i]['url'] = trim($arCustomMainBlock['page']);

	if (
		!strlen($id) ||
		!strlen($name) ||
		!strlen($page) ||
		!file_exists(__DIR__.'/include/index/'.$page.'.php')
	) {
		unset($arCustomMainBlocks[$i]);
		continue;
	}
}

$arCustomMainBlocksIds = array_column($arCustomMainBlocks, 'id');

$arParams['MAIN_BLOCKS_ORDER'] = isset($arParams['MAIN_BLOCKS_ORDER']) && strlen($arParams['MAIN_BLOCKS_ORDER']) ? explode(',', $arParams['MAIN_BLOCKS_ORDER']) : [];

$arParams['MAIN_BLOCKS_ORDER'] = array_filter($arParams['MAIN_BLOCKS_ORDER'], function($block) {
	return !preg_match('/^-/', $block);
});

$this->__component->correctUserPhones();
?>
<div class="personal__wrapper">
	<?foreach($arParams['MAIN_BLOCKS_ORDER'] as $i => $block):?>
		<?
		$lastBlock = $arParams['MAIN_BLOCKS_ORDER'][$i - 1] ?? '';
		$nextBlock = $arParams['MAIN_BLOCKS_ORDER'][$i + 1] ?? '';

		if (
			(
				'private' === $block && 
				'account' === $lastBlock
			) ||
			(
				'account' === $block && 
				'private' === $lastBlock 
			)
		) {
			continue;
		}

		$bCustom = strpos($block, 'custom_') !== false;
		if ($bCustom) {
			$id = str_replace('custom_', '', $block);

			if (!in_array($id, $arCustomMainBlocksIds)) {
				continue;
			}
		}

		$extClass = '';
		if ('banners' === $block) {
			$extClass .= $arParams['BANNERS_HIDDEN_SM'] === 'Y' ? ' hidden-sm' : '';
			$extClass .= $arParams['BANNERS_HIDDEN_XS'] === 'Y' ? ' hidden-xs' : '';
		}
		$extClass = trim($extClass);
		?>
		<div class="personal__main-block mb mb--12 personal__main-block--<?=$block?><?=(strlen($extClass) ? ' '.$extClass : '')?>">
			<?if ('private' === $block):?>
				<div class="grid-list grid-list--items grid-list--fill-bg<?=($nextBlock === 'account' ? ' grid-list--personal-3-2' : ' grid-list--items-1')?>">
					<?include_once 'include/index/'.$block.'.php';?>
					<?if ($nextBlock  === 'account'):?>
						<?include_once 'include/index/'.$nextBlock.'.php';?>
					<?endif;?>
				</div>
			<?elseif ('account' === $block):?>
				<div class="grid-list grid-list--items grid-list--fill-bg<?=($nextBlock === 'private' ? ' grid-list--personal-2-3' : ' grid-list--items-1')?>">
					<?include_once 'include/index/'.$block.'.php';?>
					<?if ($nextBlock  === 'private'):?>
						<?include_once 'include/index/'.$nextBlock.'.php';?>
					<?endif;?>
				</div>
			<?elseif (in_array($block, ['banners', 'links', 'orders', 'votes', 'recoms'])):?>
				<?include_once 'include/index/'.$block.'.php';?>
			<?elseif ($bCustom):?>
				<?
				foreach ($arCustomMainBlocks as $i => $arCustomMainBlock) {
					if ($arCustomMainBlock['id'] === $id) {
						$page = $arCustomMainBlock['page'];
						include_once 'include/index/'.$page.'.php';

						break;
					}
				}
				?>
			<?endif;?>
		</div>
	<?endforeach;?>
</div>

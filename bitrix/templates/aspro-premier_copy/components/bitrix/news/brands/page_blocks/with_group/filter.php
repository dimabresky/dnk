<?
$bHasLatinLetters = $bHasCyrilicLetters = $bShowAdditionalDiv = false;
$arLatinAlphabet = array_column(\Aspro\Premier\Brand::getLatinLetters(), 'LETTER');
$arCyrilicAlphabet = array_column(\Aspro\Premier\Brand::getCyrilicLetters(), 'LETTER');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

$letterRequest = $request->get('letter');
?>
<div class="filter-letters mb mb--24">
	<div class="line-block line-block--flex-wrap line-block--column line-block--align-normal line-block--gap line-block--gap-8 mobile-offset mobile-scrolled">
		<div class="line-block line-block--flex-wrap line-block--gap line-block--gap-8 font_14 font_short">
			<div class="line-block__item">
				<button type="button" class="chip chip--rectangular-shape chip--transparent bordered filter-link border-theme-active bg-theme-active color-theme-hover-no-active<?=(!$letterRequest ? ' active' : '');?>">
					<div class="chip__label">
						<?=GetMessage('ALL_LETTERS');?>
					</div>
				</button>
			</div>
			<?foreach ($arFilterLetters as $key => $arLetter):?>
				<?$letter = $arLetter['LETTER'];
				$code = 'nums--';
				if (in_array($letter, $arLatinAlphabet)) {
					$code = 'en--';
					$bHasLatinLetters = true;
				} elseif (in_array($letter, $arCyrilicAlphabet)) {
					$code = 'ru--';
					$bHasCyrilicLetters = true;
				}
				$arFilterLetters[$key]['PREFIX'] = $code;?>
				<?if ($bHasLatinLetters && $bHasCyrilicLetters && !$bShowAdditionalDiv):?>
					<?$bShowAdditionalDiv = true;?>
					</div>
					<div class="line-block line-block--flex-wrap line-block--gap line-block--gap-8 font_14 font_short">
				<?endif;?>
				<div class="line-block__item">
					<button type="button" class="chip chip--rectangular-shape chip--transparent bordered filter-link border-theme-active bg-theme-active color-theme-hover-no-active<?=(strtoupper($letterRequest) === strtoupper($code.$arLetter['CODE']) ? ' active' : '');?>" data-letter="<?=$code.$arLetter['CODE'];?>">
						<div class="chip__label">
							<?=$letter;?>
						</div>
					</button>
				</div>
			<?endforeach;?>
		</div>
	</div>
</div>
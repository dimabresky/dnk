<?
global $USER, $pathForAjax;
if ($USER->IsAuthorized()) {
	$userId = $USER->GetID();
}

if ($userId) {
	global $USER_FIELD_MANAGER;
	$ufId = ($userId % 1000).($comment['ID'] % 1000);
	$fields = $USER_FIELD_MANAGER->GetUserFields("BLOG_COMMENT_ID", $ufId);
	$fieldValueLike = $fields['UF_LIKE_ID']['VALUE'];
	$fieldValueLike = TSolution::unserialize((string)$fieldValueLike);

	if (isset($fieldValueLike[$userId])) {
		$valuelike = $fieldValueLike[$userId];
	} else {
		$valuelike = 'N';
	}

	$bActiveLike = $valuelike == 'Y';

	$fieldValueDisLike = $fields['UF_DISLIKE_ID']['VALUE'];
	$fieldValueDisLike = TSolution::unserialize((string)$fieldValueDisLike);

	if (isset($fieldValueDisLike[$userId])) {
		$valuedislike = $fieldValueDisLike[$userId];
	} else {
		$valuedislike = 'N';
	}

	$bActiveDisLike = $valuedislike == 'Y';
}
?>
<span class="rating-vote" data-comment_id="<?=$comment['ID']?>" data-user_id="<?=$userId?>" data-ajax_url="<?=$pathForAjax.'/ajaxLike.php';?>">
	<button type="button" class="btn--no-btn-appearance rating-vote__item rating-vote__item-like dark_link plus<?=$userId ? '' : ' disable'?><?=$bActiveLike ? ' active' : ''?>" data-action="plus" title="<?=GetMessage('LIKE');?>">
		<span class="rating-vote__icon">
			<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/catalog/item_icons.svg#like", 'mt mb--4 fill-dark-light', ['WIDTH' => 16, 'HEIGHT' => 16]);?>
		</span>

		<span class="rating-vote__result secondary-color font_13">
			<?=intval($comment['UF_ASPRO_COM_LIKE']);?>
		</span>
	</button>

	<button type="button" class="btn--no-btn-appearance rating-vote__item rating-vote__item-dislike dark_link minus<?=$userId ? '' : ' disable';?><?=$bActiveDisLike ? ' active' : '';?>" data-action="minus" title="<?=GetMessage('DISLIKE');?>">
		<span class="rating-vote__icon">
			<?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/catalog/item_icons.svg#dislike", 'mt mt--4 fill-dark-light', ['WIDTH' => 16, 'HEIGHT' => 16]);?>
		</span>

		<span class="rating-vote__result secondary-color font_13">
			<?=intval($comment['UF_ASPRO_COM_DISLIKE']);?>
		</span>
	</button>
</span>
<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$iblockId = TSolution\Cache::$arIBlocks[SITE_ID][VENDOR_PARTNER_NAME.'_'.VENDOR_SOLUTION_NAME.'_adv'][VENDOR_PARTNER_NAME.'_'.VENDOR_SOLUTION_NAME.'_banners_lk'][0];
if ($iblockId) {
	$filterName = 'PREMIER_FILTER_LINKED_BANNERS';
	$GLOBALS[$filterName] = [
		'ACTIVE' => 'Y',
		'IBLOCK_ID' => $iblockId,
	];

	if (
		TSolution\Regionality::checkUseRegionality() &&
		TSolution::GetFrontParametrValue('REGIONALITY_FILTER_ITEM') == 'Y'
	) {
		if ($arRegion = TSolution\Regionality::getCurrentRegion()) {
			$GLOBALS[$filterName][] = [
				'LOGIC' => 'OR',
				[
					'PROPERTY_LINK_REGION' => false,
				],
				[
					'PROPERTY_LINK_REGION' => $arRegion['ID'],
				],
			];
		}
	}

	$arGroupsIds = [];
	$userId = $GLOBALS['USER'] ? $GLOBALS['USER']->GetID() : false;
	if ($userId) {
		$res = \CUser::GetUserGroupList($userId);
		while ($arGroup = $res->Fetch()) {
			$arGroupsIds[] = $arGroup['GROUP_ID'];
		}
	}

	if (!in_array(2, $arGroupsIds)) {
		$arGroupsIds[] = 2;
	}

	$GLOBALS[$filterName][] = [
		'LOGIC' => 'OR',
		[
			'PROPERTY_LINK_GROUPS' => false,
		],
		[
			'PROPERTY_LINK_GROUPS' => $arGroupsIds,
		],
	];

	$cnt = TSolution\Cache::CIblockElement_GetList(
		[
			'CACHE' => [
				'TAG' => TSolution\Cache::GetIBlockCacheTag($iblockId),
			],
		],
		$GLOBALS[$filterName], 
		[]
	);
	?>
	<?if ($cnt):?>
		<?TSolution\Functions::showBlockHtml([
			'FILE' => '/catalog/banners_in_list.php',
			'PARAMS' => [
				'IBLOCK_ID' => $iblockId,
				'FILTER_NAME' => $filterName,
			],
		])?>
	<?endif;?>
	<?
}

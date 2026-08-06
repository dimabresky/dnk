<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if($arItems){
	$setionIDRequest = (isset($_GET["section_id"]) && $_GET["section_id"] ? $_GET["section_id"] : 0);

	foreach($arItems as $arItem){
		$arItemsID[$arItem["ID"]] = $arItem["ID"];
		if($arItem["IBLOCK_SECTION_ID"] && $arItem["IBLOCK_ID"] == $catalogIBlockID){
			if(is_array($arItem["IBLOCK_SECTION_ID"])){
				foreach($arItem["IBLOCK_SECTION_ID"] as $id){
					$arAllSections[$id]["COUNT"]++;
					$arAllSections[$id]["ITEMS"][$arItem["ID"]] = $arItem["ID"];
				}
			}
			else
			{
				$arAllSections[$arItem["IBLOCK_SECTION_ID"]]["COUNT"]++;
				$arAllSections[$arItem["IBLOCK_SECTION_ID"]]["ITEMS"][$arItem["ID"]] = $arItem["ID"];
			}
		}
	}

	$arSectionsID = array_keys($arAllSections);
}
?>
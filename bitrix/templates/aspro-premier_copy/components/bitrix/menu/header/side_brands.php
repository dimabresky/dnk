<?
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.include',
	'.default',
	[
		"COMPONENT_TEMPLATE" => ".default",
		"PATH" => SITE_DIR . "include/header/menu/brands.php",
		"AREA_FILE_SHOW" => "file",
		"AREA_FILE_SUFFIX" => "",
		"AREA_FILE_RECURSIVE" => "Y",
		"EDIT_TEMPLATE" => "include_area.php",
		"LARGE_CATALOG_BUTTON" => "Y",
	],
	false,
	[
		'HIDE_ICONS' => 'Y'
	]
);
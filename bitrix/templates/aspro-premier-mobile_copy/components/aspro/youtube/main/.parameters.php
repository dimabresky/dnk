<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
$arFromTheme = $fromYoutubeSettings = [];
if (isset($_REQUEST['src_path'])) {
	$_SESSION['src_path_component'] = $_REQUEST['src_path'];
}

if (strpos($_SESSION['src_path_component'], 'custom') === false) {
	$arFromTheme = ["FROM_THEME" => GetMessage("V_FROM_THEME")];
	$fromYoutubeSettings = ['FROM_SETTINGS_YOUTUBE' => GetMessage('V_SETTINGS_YOUTUBE')];
}

$arTemplateParameters = array(
	'COUNT_VIDEO_ON_LINE_YOUTUBE' => array(
		'NAME' => GetMessage('COUNT_VIDEO_ON_LINE_YOUTUBE'),
		'TYPE' => 'LIST',
		'VALUES' => array_merge($arFromTheme, $fromYoutubeSettings) + [
				'2' => 2,
				'3' => 3,
				'4' => 4,
			],
		'DEFAULT' => "3",
	),
	"WIDE" => Array(
		"NAME" => GetMessage("T_NARROW"),
		"TYPE" => "LIST",
		"VALUES" => array_merge(
			$arFromTheme,
			[
				'Y' => GetMessage('V_YES'),
				'N' => GetMessage('V_NO'),
			]
		),
		"DEFAULT" => "N",
    ),
    "ITEMS_OFFSET" => Array(
		"NAME" => GetMessage("T_ITEMS_OFFSET"),
		"TYPE" => "LIST",
		"VALUES" => array_merge(
			$arFromTheme,
			[
				'Y' => GetMessage('V_YES'),
				'N' => GetMessage('V_NO'),
			]
		),
		"DEFAULT" => "Y",
	),
	"SHOW_TITLE" => array(
		"PARENT" => "ADDITIONAL_SETTINGS",
		"NAME" => GetMessage("SHOW_TITLE_YOUTUBE"),
		"TYPE" => "LIST",
		"VALUES" => array_merge(
			$arFromTheme,
			[
				true => GetMessage('V_YES'),
				false => GetMessage('V_NO'),
			]
		),
		"DEFAULT" => "Y",
	),
	'TITLE_POSITION' => array(
		"NAME" => GetMessage('TITLE_POSITION'),
		"TYPE" => "LIST",
		"VALUES" => array_merge(
			$arFromTheme,
			[
				'NORMAL' => GetMessage('TITLE_POSITION_NORMAL'),
				'CENTERED' => GetMessage('TITLE_POSITION_CENTER'),
				'LEFT' => GetMessage('TITLE_POSITION_LEFT'),
			]
		),
		"DEFAULT" => "FROM_THEME",
	),
);
?>
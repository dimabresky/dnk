<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// define global template name for body class
global $bodyDopClass, $bigBannersIndexClass;

if (
    strpos($bigBannersIndexClass, 'hidden') === false &&
    $templateData['BANNERS_COUNT']
) {
    if ($arParams['HEADER_OPACITY']) {
        $bodyDopClass .= ' header_opacity';

        if ($templateData['CURRENT_BANNER_COLOR']) {
            $GLOBALS['APPLICATION']->SetPageProperty('HEADER_LOGO', $templateData['CURRENT_BANNER_COLOR']);
            $GLOBALS['APPLICATION']->SetPageProperty('HEADER_COLOR_CLASS', 'theme-'.($templateData['CURRENT_BANNER_COLOR'] == 'dark' ? 'light' : 'dark'));
        }
    }
    elseif (
        $arParams['NO_OFFSET_BANNER'] &&
        $arParams['NARROW_BANNER']
    ) {
        $bodyDopClass .= ' header-no-border';
    }

    if ($templateData['CURRENT_BANNER_INDEX']) {
        $bannerIndexStyle = '<style>
        .main-slider:not(.swiper-initialized) .swiper-slide:not([data-slide_index="'.$templateData['CURRENT_BANNER_INDEX'].'"]) {
            display: none;
        }
        </style>';
        $APPLICATION->AddHeadString($bannerIndexStyle);
    }
}

$arExtensions = ['swiper', 'top_banner', 'banners'];
if ($templateData['HAS_VIDEO']) {
	$arExtensions[] = 'video_banner';
}
if ($arParams['HEADER_OPACITY'] && $templateData['BANNERS_COUNT']) {
    $arExtensions[] = 'header_opacity';
}
\TSolution\Extensions::init($arExtensions);

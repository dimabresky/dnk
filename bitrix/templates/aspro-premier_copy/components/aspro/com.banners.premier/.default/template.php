<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

if ($arResult['HAS_SLIDE_BANNERS']) {
    $cntBanners = count($arResult['ITEMS'][$arParams['BANNER_TYPE_THEME']]['ITEMS']);

    $currentBannerIndex = intval($arParams['CURRENT_BANNER_INDEX']) > 0 ? intval($arParams['CURRENT_BANNER_INDEX']) - 1 : 0;

    $templateData = array(
        'BANNERS_COUNT' => $cntBanners,
        'CURRENT_BANNER_INDEX' => $currentBannerIndex,
        'CURRENT_BANNER_COLOR' => '',
    );

    $bannerMobile = $arParams['BIGBANNER_MOBILE'];
    $bHideOnNarrow = $arParams['BIGBANNER_HIDEONNARROW'] === 'Y';
    $slideshowSpeed = abs(intval($arParams['BIGBANNER_SLIDESSHOWSPEED']));
    $animationSpeed = abs(intval($arParams['BIGBANNER_ANIMATIONSPEED']));
    $bAnimation = $slideshowSpeed && strlen($arParams['BIGBANNER_ANIMATIONTYPE']);

    if ($arParams['BIGBANNER_ANIMATIONTYPE'] === 'FADE') {
        $animationType = 'fade';
    }
    else {
        $animationType = 'slide';
        $animationDirection = 'horizontal';
        if ($arParams['BIGBANNER_ANIMATIONTYPE'] === 'SLIDE_VERTICAL') {
            $animationDirection = 'vertical';
        }
    }

    $bBgImage = !$arParams['IMG_POSITION'] || $arParams['IMG_POSITION'] == 'COVER';
    $bWideText = $arParams['WIDE_TEXT'];
    $sliderItems = 1;

    $bLowBanner = $arParams['HEIGHT_BANNER'] === 'LOW';
    $bNormalBanner = $arParams['HEIGHT_BANNER'] === 'NORMAL';
    $bHighBanner = !$bLowBanner && !$bNormalBanner;

    $bRoundPictures = !$arParams['HEADER_OPACITY'] && (!$arParams['NO_OFFSET_BANNER'] || $arParams['NARROW_BANNER']);
    if ($bRoundPictures && $arResult['HAS_CHILD_BANNERS']) {
        $bRoundPictures = !$arParams['NO_OFFSET_BANNER'];
    }

    $titleSizeClass = 'font_36';
    $subtextSizeClass = 'font_15';
    if ($bWideText) {
        $titleSizeClass = $bHighBanner ? 'font_72' : ($bNormalBanner ? 'font_60' : 'font_48');
    }

    $bannerClasses = ['banners-big', 'front', 'swipeignore'];
    $bannerClasses[] = $bHighBanner ? 'banners-big--high' : ($bNormalBanner ? 'banners-big--normal' : 'banners-big--low');
    if ($bHideOnNarrow) {
        $bannerClasses[] = 'hidden_narrow';
    }
    if ($arParams['NARROW_BANNER']) {
        $bannerClasses[] = 'banners-big--narrow';
    }
    if ($arResult['HAS_CHILD_BANNERS']) {
        $bannerClasses[] = 'banners-big--side-banners';
    }
    $bannerClasses[] = 'banners-big--adaptive-'.$bannerMobile;
    if (!$arParams['HEADER_OPACITY']) {
        $bannerClasses[] = 'banners-no-header-opacity';
    }

    $maxwidthBannerClasses = ['maxwidth-banner', 'gap'];
    if ($arParams['NARROW_BANNER']) {
        $maxwidthBannerClasses[] = 'maxwidth-theme';

        if ($arParams['NO_OFFSET_BANNER']) {
            $maxwidthBannerClasses[] = 'maxwidth-theme--no-paddings';
        }
    }
    if ($arParams['NO_OFFSET_BANNER']) {
        $maxwidthBannerClasses[] = 'gap--0';
    }
    else {
        $maxwidthBannerClasses[] = 'maxwidth-banner--paddings';
        $maxwidthBannerClasses[] = 'gap--x';
    }

    $carouselClasses = ['swiper', 'slider-solution', 'main-slider', 'banners-big__depend-height'];
    if ($arParams['HEADER_OPACITY']) {
        $carouselClasses[] = 'banners-big__depend-padding';
    }
    if ($bRoundPictures) {
        // $carouselClasses[] = 'outer-rounded-x'; // removed because there is bug with repaint in chrome
    }

    $arOptions = [
        // Disable preloading of all images
        'preloadImages' => false,
        'keyboard' => true,
        'init' => false,
        'countSlides' => $cntBanners,
        'type' => 'main_banner',
    ];

    if ($arOptions['countSlides'] > 10) {
        $arOptions['pagination']['dynamicBullets'] = true;
        $arOptions['pagination']['dynamicMainBullets'] = 3;
    }
    if ($arOptions['countSlides'] > 1) {
        $arOptions['loop'] = true;
    }
    if (!$arParams['NO_OFFSET_BANNER']) {
        $arOptions['spaceBetween'] = 24;
    }
    if ($arOptions['countSlides'] > 1) {
        $arOptions['slidesPerView'] = 'auto';
        $arOptions['pagination' ] = [
            'el' => '.swiper-pagination',
            'type' => 'bullets',
        ];
    }

    $bannerClasses = implode(' ', $bannerClasses);
    $maxwidthBannerClasses = implode(' ', $maxwidthBannerClasses);
    $carouselClasses = implode(' ', $carouselClasses);
    ?>
    <div class="<?=$bannerClasses?>">
        <div class="<?=$maxwidthBannerClasses?>">
            <div class="banners-big__wrapper min-width-0">
                <div class="<?=$carouselClasses?>" data-plugin-options='<?=json_encode($arOptions)?>'>
                    <div class="swiper-wrapper main-slider__wrapper">
                        <?foreach ($arResult['ITEMS'][$arParams['BANNER_TYPE_THEME']]['ITEMS'] as $i => $arItem):?>
                            <?
                            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

                            $bHasUrl = boolval(strlen($arItem['PROPERTIES']['LINKIMG']['VALUE']));
                            $target = $arItem['PROPERTIES']["TARGETS"]["VALUE_XML_ID"];

                            $imageBgSrc = $this->GetFolder().'/images/background.jpg';
                            if (is_array($arItem['DETAIL_PICTURE'])) {
                                $imageBgSrc = $arItem['DETAIL_PICTURE']['SRC'];
                            }
                            if (!$bHighBanner && $arItem['PROPERTIES']['NORMAL_BG_IMAGE']['VALUE']) {
                                $imageBgSrc = CFile::GetPath($arItem['PROPERTIES']['NORMAL_BG_IMAGE']['VALUE']);
                            }
                            if ($bLowBanner && $arItem['PROPERTIES']['LOW_BG_IMAGE']['VALUE']) {
                                $imageBgSrc = CFile::GetPath($arItem['PROPERTIES']['LOW_BG_IMAGE']['VALUE']);
                            }

                            $type = $arItem['PROPERTIES']['BANNERTYPE']['VALUE_XML_ID'];
                            $bOnlyImage = $type == 'T1' || !$type;
                            $bLinkOnName = strlen($arItem['PROPERTIES']['LINKIMG']['VALUE']);
                            $bOpacity = $arItem['PROPERTIES']['BANNER_OPACITY']['VALUE_XML_ID'] == 'Y';
                            $bCenterText = $arItem['PROPERTIES']['TEXT_CENTER']['VALUE_XML_ID'] == 'Y';
                            $verticalAlignment = $arItem['PROPERTIES']['VERTICAL_ALIGNMENT']['VALUE_XML_ID'] ?? 'CENTER';
                            $verticalTop = $verticalAlignment === 'TOP';
                            $verticalBottom = $verticalAlignment === 'BOTTOM';
                            $verticalCenter = $verticalAlignment === 'CENTER';
                            $verticalSpread = $verticalAlignment === 'SPREAD';

                            include('video.php');

                            $bannerColor = $arItem['PROPERTIES']['MAIN_COLOR']['VALUE'] ? $arItem['PROPERTIES']['MAIN_COLOR']['VALUE_XML_ID'] : 'dark';

                            $bannerSlideClasses = ['swiper-slide', 'main-slider__item', 'box'];
                            if ($bShowVideo) {
                                $bannerSlideClasses[] = 'vvideo';
                            }
                            if ($bRoundPictures) {
                                $bannerSlideClasses[] = 'outer-rounded-x';
                            }

                            $bannerItemClasses = ['banners-big__item', 'banners-big__depend-height', 'theme-'.$bannerColor];
                            if ($bShowVideo) {
                                $bannerItemClasses[] = 'vvideo';
                            }
                            if($bHasUrl) {
                                $bannerItemClasses[] = 'wurl';
                            }
                            if ($bOpacity) {
                                $bannerItemClasses[] = 'banners-big__item--opacity';
                            }
                            if ($arParams['HEADER_OPACITY']) {
                                $bannerItemClasses[] = 'banners-big__depend-padding';
                            }
                            if($bOnlyImage && $bShowVideo) {
                                $bannerItemClasses[] = 'banners-big__item--img-with-video';
                            }

                            $bannerInnerClasses = ['banners-big__inner'];
                            if ($type == 'T3' && !$bCenterText) {
                                $bannerInnerClasses[] = 'banners-big__inner--righttext';
                            }

                            $bannerTextClasses = ['banners-big__text', 'banners-big__depend-height'];
                            if ($bCenterText) {
                                $bannerTextClasses[] = 'banners-big__text--center';
                            }
                            if ($verticalTop) {
                                $bannerTextClasses[] = 'banners-big__text--v-top';
                            }
                            elseif ($verticalCenter) {
                                $bannerTextClasses[] = 'banners-big__text--v-center';
                            }
                            elseif ($verticalBottom) {
                                $bannerTextClasses[] = 'banners-big__text--v-bottom';
                            }
                            elseif ($verticalSpread) {
                                $bannerTextClasses[] = 'banners-big__text--v-spread';
                            }
                            if ($bWideText) {
                                $bannerTextClasses[] = 'banners-big__text--wide';
                            }
                            if (
                                $verticalCenter &&
                                !$bCenterText &&
                                $type != 'T3' &&
                                (
                                    !$arParams['CONTENT_NARROW'] ||
                                    $arParams['NARROW_BANNER']
                                )
                            ) {
                                $bannerTextClasses[] = 'banners-big__text--add-padding';
                            }

                            $bannerImageClasses = ['banners-big__img-wrapper', 'banners-big__depend-height'];
                            if ($type !== 'T3') {
                                $bannerImageClasses[] = 'banners-big__img-wrapper--back-right';
                            }

                            // first visible slide is the first item or $currentBannerIndex
                            // saving his color for to usage in component_epilog
                            $dataSrc = '';
                            if (
                                $bannerColor &&
                                (
                                    $i == $currentBannerIndex ||
                                    !$i
                                )
                            ) {
                                $templateData['CURRENT_BANNER_COLOR'] = $bannerColor;
                                $dataSrc = 'data-src=""';
                            }

                            $needShowBG = $bOnlyImage;
                            $csDateRaw = $arItem['PROPERTIES']['CS_DATETIME']['VALUE'] ?? '';
                            $csDate = is_array($csDateRaw) ? trim((string) reset($csDateRaw)) : trim((string) $csDateRaw);
                            if ($csDate !== '') {
                                $bannerSlideClasses[] = 'banners-big__slide--countdown';
                            }
                            $bannerSlideClasses = implode(' ', $bannerSlideClasses);
                            $bannerItemClasses = implode(' ', $bannerItemClasses);
                            $bannerInnerClasses = implode(' ', $bannerInnerClasses);
                            $bannerTextClasses = implode(' ', $bannerTextClasses);
                            $bannerImageClasses = implode(' ', $bannerImageClasses);

                            $name = ($arItem['DETAIL_TEXT'] ? $arItem['DETAIL_TEXT'] : strip_tags($arItem['~NAME'], '<br><br/>'));
                            ?>
                            <div
                                class="<?=$bannerSlideClasses?>"
                                <?=($bBgImage || $needShowBG ? 'style="background-image:url('.$imageBgSrc .')"' : '')?>
                                data-slide_index="<?=$i?>"
                                <?=($bannerColor ? ' data-color="'.$bannerColor.'"' : '')?>
                                <?=$videoInfoItem?>
                                data-background="<?=$imageBgSrc?>"
                                <?=$dataSrc?>
                            >
                                <div class="<?=$bannerItemClasses?>">
                                    <?if ($bHasUrl):?>
                                        <a class="target" href="<?=$arItem['PROPERTIES']['LINKIMG']['VALUE']?>" <?=(strlen($target) ? 'target="'.$target.'"' : '')?>></a>
                                    <?endif;?>

                                    <div
                                        id="<?=$this->GetEditAreaId($arItem['ID'])?>"
                                        class="banners-big__inner-wrap maxwidth-theme<?=($bVideoAutoStart ? ' loading' : '');?><?=($arParams['CONTENT_NARROW'] ? '' : ' maxwidth-theme--no-maxwidth')?>"
                                        <?=$bannerMobile == 2 && ($bBgImage || $needShowBG) ? 'style="background-image:url('.$imageBgSrc .')"' : ''?>
                                        <?=$dataSrc?>
                                    >
                                        <?// make text block?>
                                        <?ob_start();?>
                                        <?if (!$bOnlyImage):?>
                                            <?if ($bLinkOnName):?>
                                                <a href="<?=$arItem['PROPERTIES']['LINKIMG']['VALUE']?>" class="banners-big__title-link no-decoration">
                                            <?endif;?>

                                                <?if (!isset($hasH1) && $arItem['PROPERTIES']['TITLE_H1']['VALUE_XML_ID'] == 'Y'):?>
                                                    <?$hasH1 = true;?>
                                                    <h1 class="banners-big__title switcher-title white_text_black_reverse fw-500 lineclamp-3 <?=$titleSizeClass?>"><?=$name?></h1>
                                                <?else:?>
                                                    <div class="banners-big__title switcher-title white_text_black_reverse fw-500 lineclamp-3 <?=$titleSizeClass?>"><?=$name?></div>
                                                <?endif;?>

                                            <?if ($bLinkOnName):?>
                                                </a>
                                            <?endif;?>

                                            <div class="banners-big__text-wrapper">
                                                <?if (strlen($arItem['PREVIEW_TEXT'])):?>
                                                    <div class="banners-big__text-block white_text_black_reverse mt mt--16 lineclamp-5 <?=$subtextSizeClass?>">
                                                        <?=$arItem['PREVIEW_TEXT']?>
                                                    </div>
                                                <?endif;?>
                                            </div>

                                            <?include('buttons.php');?>
                                        <?endif;?>
                                        <?$text = trim(ob_get_clean());?>

                                        <?// make image block?>
                                        <?ob_start();?>
                                            <?
                                            $image = false;
                                            if (is_array($arItem['PREVIEW_PICTURE']) && array_key_exists('SRC', $arItem['PREVIEW_PICTURE']) ) {
                                                $image = $arItem['PREVIEW_PICTURE'];
                                            }
                                            if (!$bHighBanner && $arItem['PROPERTIES']['NORMAL_BANNER_IMAGE']['VALUE']) {
                                                $image = CFile::GetFileArray($arItem['PROPERTIES']['NORMAL_BANNER_IMAGE']['VALUE']);
                                            }
                                            if ($bLowBanner && $arItem['PROPERTIES']['LOW_BANNER_IMAGE']['VALUE']) {
                                                $image = CFile::GetFileArray($arItem['PROPERTIES']['LOW_BANNER_IMAGE']['VALUE']);
                                            }
                                            ?>
                                            <?if ($image):?>
                                                <?
                                                    $arImage1080 = CFile::ResizeImageGet($image['ID'], array('width' => 1080, 'height' => 10000), BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                                                ?>
                                                <?if ($bLinkOnName):?>
                                                    <a href="<?=$arItem['PROPERTIES']['LINKIMG']['VALUE']?>" class="image">
                                                <?endif;?>
                                                        <img class="plaxy banners-big__img" src="<?= $arImage1080['src']?>" alt="<?=($image['ALT'] ?: $arItem['FORMAT_NAME'])?>" title="<?=($image['TITLE'] ?: $arItem['FORMAT_NAME'])?>" <?=$dataSrc?> />
                                                <?if ($bLinkOnName):?>
                                                    </a>
                                                <?endif;?>

                                            <?endif;?>
                                        <?$img = trim(ob_get_clean());?>

                                        <div class="<?=$bannerInnerClasses?>">
                                            <?if (!$bOnlyImage):?>
                                                <div class="<?=$bannerTextClasses?>">
                                                    <?=$text?>
                                                </div>

                                                <?if ($image):?>
                                                    <?if ($i == $currentBannerIndex):?>
                                                        <link href="<?=$imageBgSrc?>" rel="preload" as="image">
                                                    <?endif;?>

                                                    <div class="<?=$bannerImageClasses?>">
                                                        <?=$image ? $img : ''?>
                                                    </div>
                                                <?endif;?>
                                            <?elseif ($bOnlyImage):?>
                                                <?if ($bLinkOnName):?>
                                                    <a href="<?=$arItem['PROPERTIES']['LINKIMG']['VALUE']?>"></a>
                                                <?endif;?>

                                                <?if ($bShowVideo):?>
                                                    <div class="video_block only_img--video ">
                                                        <span class="play btn-video bg-theme-after <?=($bVideoAutoStart ? 'loading' : '');?> <?=$buttonVideoClass;?>" title="<?=htmlspecialcharsbx($buttonVideoText)?>"></span>
                                                    </div>
                                                <?endif;?>
                                            <?endif;?>
                                        </div>
                                    </div>

                                    <?if ($sliderItems < 2):?>
                                        <?if ($bannerMobile == 2):?>
                                            <?if (strlen($text)):?>
                                                <div class="banners-big__adaptive-block theme-root">
                                                    <!--noindex-->
                                                    <?=$text?>
                                                    <!--/noindex-->
                                                </div>
                                            <?endif;?>
                                        <?elseif ($bannerMobile == 3):?>
                                            <?$tabletImgSrc = ($arItem['PROPERTIES']['TABLET_IMAGE']['VALUE'] ? CFile::GetPath($arItem['PROPERTIES']['TABLET_IMAGE']['VALUE']) : $imageBgSrc);?>
                                            <?if ($i == $currentBannerIndex):?>
                                                <link href="<?=$tabletImgSrc?>" rel="preload" as="image" media="(max-width: 767px)">
                                            <?endif;?>
                                            <div
                                                class="banners-big__adaptive-img"
                                                style="background-image:url(<?=$tabletImgSrc?>);"
                                                data-background="<?=$tabletImgSrc;?>"
                                                <?=$dataSrc?>
                                            ></div>
                                        <?endif;?>
                                    <?endif;?>
                                </div>
                                <?
                                if ($csDate !== ''):
                                    $csTitleRaw = $arItem['PROPERTIES']['CS_STRING']['VALUE'] ?? '';
                                    $csTitle = is_array($csTitleRaw) ? trim((string) ($csTitleRaw['TEXT'] ?? '')) : trim((string) $csTitleRaw);
                                    $csPictureId = $arItem['PROPERTIES']['CS_PICTURE']['VALUE'] ?? 0;
                                    if (is_array($csPictureId)) {
                                        $csPictureId = reset($csPictureId);
                                    }
                                    $csPicture = (int) $csPictureId > 0 ? (string) CFile::GetPath((int) $csPictureId) : '';
                                    $csColor = trim((string) ($arItem['PROPERTIES']['CS_COLOR_CODE']['VALUE'] ?? ''));
                                    $APPLICATION->IncludeComponent('dnk:banner.countdown', '', [
                                        'DATETIME' => $csDate,
                                        'TITLE' => $csTitle,
                                        'PICTURE' => $csPicture,
                                        'COLOR' => $csColor,
                                        'CACHE_TYPE' => 'N',
                                    ], false, ['HIDE_ICONS' => 'Y']);
                                endif;
                                ?>
                            </div>
                        <?endforeach;?>
                    </div>

                    <?if ($arOptions['countSlides'] > 1):?>
                        <div class="maxwidth-theme banners-big__nav-wrap<?=($arParams['CONTENT_NARROW'] ? '' : ' maxwidth-theme--no-maxwidth')?>">
                            <div class="swiper-pagination swiper-pagination--flex"></div>
                            <progress class="autoplay-progress" max="1" value="0"></progress>
                            <div class="slider-nav-wrap">
                                <?TSolution\Functions::showBlockHtml([
                                    'FILE' => 'ui/slider-navigation.php',
                                    'PARAMS' => [
                                        'CLASSES' => 'slider-nav slider-nav--blur slider-nav--static',
                                        'SVG_CLASSES' => 'stroke-use-svg-fff',
                                    ]
                                ]);?>
                            </div>
                        </div>
                    <?endif;?>
                </div>
            </div>

            <?if ($arResult['HAS_CHILD_BANNERS']):?>
                <?include('float_banners.php');?>
            <?endif;?>
        </div>
    </div>
    <?
}

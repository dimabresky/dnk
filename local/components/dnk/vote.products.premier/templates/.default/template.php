<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader,
    Bitrix\Sale,
    CPremier as Solution;

Loc::loadMessages(__FILE__);
?>
<?if ($arResult['ITEMS']):?>
    <?
    $countSlides = count($arResult['ITEMS']);
    $arOptions = [
        // Disable preloading of all images
        'preloadImages' => false,
        // Enable lazy loading
        'lazy' => false,
        'keyboard' => true,
        'init' => false,
        'loop' => false,
        'countSlides' => $countSlides,
        'slidesPerView' => 'auto',
        'freeMode' => [
            'enabled' => true,
            'momentum' => true,
            'sticky' => true,
        ],
        'spaceBetween' => 12,
        // 'rewind' => true,
        'pagination' => false,
        'watchSlidesProgress' => true, // fix slide on click on slide link in mobile template
        'breakpoints' => [
            601 => [
                'slidesPerView' => 2,
                'freeMode' => false,
            ],
            992 => [
                'slidesPerView' => 3,
                'freeMode' => false,
            ],
            1200 => [
                'slidesPerView' => 4,
                'freeMode' => false,
            ],
        ],
    ];

    $svgIconsSprite = $this->__folder.'/images/svg/icons.svg';
    $randHtml = htmlspecialcharsbx((string)$arResult['RAND']);
	$randJs = CUtil::JSEscape((string)$arResult['RAND']);
    ?>
    <div id="votes-products--<?=$randHtml;?>" class="votes--slider-wrap swiper-nav-offset">
        <div class="swiper slider-solution mobile-offset mobile-offset--right votes--slider outer-rounded-x" data-plugin-options='<?=json_encode($arOptions)?>'>
            <div class="swiper-wrapper grid-list--fill-bg">
                <?foreach ($arResult['ITEMS'] as $arItem):?>
                    <?
                    $imgSrc = SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg';
                    if ($imgId = $arItem['PREVIEW_PICTURE'] ?: $arItem['DETAIL_PICTURE'] ?: false) {
                        $arImg = \CFile::ResizeImageGet($imgId, ['width' => 80, 'height' => 80], BX_RESIZE_IMAGE_PROPORTIONAL, true);
                        $imgSrc = $arImg['src'];
                    }

                    $productTitle = htmlspecialcharsbx(str_replace(['&#8381;', '&nbsp;'], [Loc::getMessage('VP_T_TPL_RUB'), ' '], $arItem['NAME']));
                    ?>
                    <div class="swiper-slide grid-list__item votes--slider__product__wrapper"
                        data-id="<?=$arItem['ID'];?>"
                        data-postid="<?=$arItem['POST_ID'];?>"
                        data-productid="<?=$arItem['PRODUCT_ID'];?>"
                    >
                        <div class="votes--slider__product height-100 outer-rounded-x bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered color-theme-parent-all">
                            <div class="votes--slider__product__inner flexbox flexbox--column">
                                <?if ($arItem['DETAIL_PAGE_URL']):?>
                                    <a class="votes--slider__product__link item-link-absolute" href="<?=$arItem['DETAIL_PAGE_URL']?>" title="<?=$productTitle?>"></a>
                                <?endif;?>

                                <span class="votes--slider__product__ignore fill-theme-hover fill-use-svg-999" title="<?=Loc::getMessage('VP_T_IGNORE_PRODUCT');?>"><?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/header_icons.svg#close-14-14', '', ['WIDTH' => 12, 'HEIGHT' => 12]);?></span>

                                <div class="votes--slider__product__top">
                                    <div class="votes--slider__product__image">
                                        <img class="img-responsive rounded-x" src="<?=htmlspecialcharsbx((string)$imgSrc)?>" alt="<?=htmlspecialcharsbx((string)$productTitle)?>" title="<?=htmlspecialcharsbx((string)$productTitle)?>" />
                                    </div>

                                    <div class="votes--slider__product__title font_14 color_dark mt mt--12 switcher-title lineclamp-3<?=(strlen($arItem['DETAIL_PAGE_URL']) ? ' color-theme-target' : '')?>"><span><?=htmlspecialcharsbx($arItem['NAME'])?></span></div>
                                </div>

                                <div class="votes--slider__product__bottom mt mt--12">
                                    <div class="votes--slider__product__rating">
                                        <div class="inner_rating">
                                            <?for ($i = 1; $i <= 5; ++$i):?>
                                                <div class="item-rating rating__star-svg" title="<?=htmlspecialcharsbx(Loc::getMessage('RATING_MESSAGE_'.$i))?>">
                                                    <?=Solution::showSpriteIconSvg($svgIconsSprite.'#star-18-17', 'star', [
                                                        'WIDTH' => 18,
                                                        'HEIGHT' => 17,
                                                    ]);?>
                                                </div>
                                            <?endfor;?>
                                        </div>
                                    </div>

                                    <?php
                                    $itemData = [
                                        'ELEMENT_ID' => $arItem['PRODUCT_ID'],
                                        'POST_ID' => $arItem['POST_ID'],
                                        'BLOG_URL' => $arParams['BLOG_URL'],
                                    ];
                                    if ($arItem['ID'] != $arItem['PRODUCT_ID']) {
                                        $itemData['OFFER_ID'] = $arItem['ID'];
                                    }
                                    ?>
                                    <button type="button" class="btn--no-btn-appearance votes--slider__product__feedback font_14 fw-500 no-decoration relative z-index-2"
                                        data-event="jqm"
                                        data-name="vote"
                                        data-param-form_id="vote"
                                        data-param-params='<?=Bitrix\Main\Web\Json::encode($itemData);?>'
                                        ><span><?=Loc::getMessage('VP_T_FEEDBACK_PRODUCT')?></span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?endforeach;?>
            </div>
        </div>

        <?if ($arOptions['countSlides'] > 1):?>
            <div class="slider-nav swiper-button-prev slider-nav--shadow">
                <?=Solution::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#left-7-12', 'stroke-dark-light', [
                    'WIDTH' => 7,
                    'HEIGHT' => 12
                ]); ?>
            </div>

            <div class="slider-nav swiper-button-next slider-nav--shadow">
                <?=Solution::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#right-7-12', 'stroke-dark-light', [
                    'WIDTH' => 7,
                    'HEIGHT' => 12
                ]); ?>
            </div>
        <?endif;?>
    </div>

    <script>
    BX.ready(function(){
        new JVoteProducts(
            '#votes-products--<?=$randJs?>',
            <?=CUtil::PhpToJSObject([
                'rand' => $arResult['RAND'],
                'template' => $this->GetName(),
                'params' => $arParams,
                'signedParameters' => $arResult['SIGNED_PARAMS'],
            ], false, true)?>
        );
    });
    </script>
<?endif;?>

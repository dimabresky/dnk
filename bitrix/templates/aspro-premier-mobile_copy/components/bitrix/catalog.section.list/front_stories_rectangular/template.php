<?php
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$this->setFrameMode(true);

if (empty($arResult['SECTIONS'])) {
    return;
}

$sectionIndex = 0;
$arOptions = [
    'slidesPerView' => 2,
    'freeMode' => ['enabled' => true,'momentum' => true],
    'breakpoints' => [
        '425' => [
            'slidesPerView' => 3,
        ],
        '601' => [
            'slidesPerView' => 4,
        ],
        '992' => [
            'slidesPerView' => 5,
            'freeMode' => false,
        ],
        '1100' => [
            'slidesPerView' => 6,
            'freeMode' => false,
        ],
        '1200' => [
            'slidesPerView' => 8,
            'freeMode' => false,
        ],
    ],
];
?>
<div class="content_wrapper_block front_stories" data-params="<?=$arResult['SIGNED_PARAMS'];?>" id="<?=$this->randString();?>">
    <?TSolution\Functions::showTitleBlock([
        'PATH' => 'front-stories',
        'PARAMS' => $arParams,
    ]);?>
    <div class="maxwidth-theme">
        <div class="tab_slider_wrapp stories swiper-nav-offset relative">
            <div class="stories-slider stories-slider--rectangular slider-solution swiper short-nav hidden-dots visible-nav swipeignore appear-block mobile-offset mobile-offset--right" data-plugin-options='<?=Json::encode($arOptions);?>'>
                <div class="swiper-wrapper stories-slider__wrapper">
                    <?foreach ($arResult['SECTIONS'] as $arSection):?>
                        <?php
                        if ($arParams['COUNT_ELEMENTS'] && !$arSection['ELEMENT_CNT']) {
                            continue;
                        }
                        $this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_EDIT'));
                        $this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_DELETE'), ['CONFIRM' => GetMessage('CT_BNL_SECTION_DELETE_CONFIRM')]);
                        $storyItemClassList = [
                            'stories-slider__item stories-item item swiper-slide',
                            'ui-card ui-card--image-scale cover cover--full',
                            'outer-rounded-x overflow-block  pointer',
                        ];
                        ?>
                        <div class="<?=TSolution\Utils::implodeClasses($storyItemClassList);?>"
                            id="<?=$this->GetEditAreaId($arSection['ID']);?>"
                            data-section-id=<?=$arSection['ID'];?>
                            data-index=<?=$sectionIndex++;?>
                        >
                            <?if ($arSection['PICTURE']['SRC']):?>
                                <div class="ui-card__image ui-card__image--ratio-172_5-260 image relative overflow-block skeleton-item z-index-minus-1">
                                    <img src="<?=$arSection['PICTURE']['SRC'];?>"
                                        class="ui-card__img img"
                                        loading="lazy"
                                        alt="<?=$arSection['NAME'];?>"
                                        title="<?=$arSection['NAME'];?>"
                                        decoding="async"
                                    >
                                </div>
                            <?endif;?>

                            <div class="ui-card__info ui-card__info--absolute ui-card__info--absolute-off-12 ui-card__info--absolute-top z-index-1 color_light font_14 fw-500">
                                <?=$arSection['NAME'];?>
                            </div>
                        </div>
                    <?endforeach;?>
                </div>
            </div>

            <?TSolution\Functions::showBlockHtml([
                'FILE' => 'ui/slider-navigation.php',
                'PARAMS' => [
                    'CLASSES' => 'slider-nav slider-nav--shadow swiper-button-disabled',
                ],
            ]);?>
        </div>
    </div>
</div>

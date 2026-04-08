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
$arOptions = Json::encode([
    'spaceBetween' => 0,
    'slidesPerView' => 2,
    'breakpoints' => [
        '425' => [
            'slidesPerView' => 3,
        ],
        '601' => [
            'slidesPerView' => 4,
        ],
        '992' => [
            'slidesPerView' => 5,
        ],
        '1100' => [
            'slidesPerView' => 6,
        ],
        '1200' => [
            'slidesPerView' => 8,
        ],
    ],
]);
?>
<div class="content_wrapper_block front_stories" data-params="<?=$arResult['SIGNED_PARAMS'];?>" id="<?=$this->randString();?>">
    <?TSolution\Functions::showTitleBlock([
        'PATH' => 'front-stories',
        'PARAMS' => $arParams,
    ]);?>
    <div class="maxwidth-theme">
        <div class="tab_slider_wrapp stories swiper-nav-offset relative">
            <div class="stories-slider slider-solution swiper short-nav hidden-dots visible-nav swipeignore appear-block" data-plugin-options='<?=$arOptions;?>'>
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
                            'p-inline p-inline--20 color-theme-hover pointer'
                        ];
                        ?>
                        <div class="<?=TSolution\Utils::implodeClasses($storyItemClassList);?>"
                            id="<?=$this->GetEditAreaId($arSection['ID']);?>"
                            data-section-id=<?=$arSection['ID'];?>
                            data-index=<?=$sectionIndex++;?>
                        >
                            <?if ($arSection['PICTURE']['SRC']):?>
                                <div class="p-inline p-inline--12 p-block p-block--4 width-100">
                                    <div class="stories-item__image image relative ratio-1 overflow-block rounded skeleton-item mi mi--auto">
                                        <img src="<?=$arSection['PICTURE']['SRC'];?>"
                                            loading="lazy"
                                            alt="<?=$arSection['NAME'];?>"
                                            title="<?=$arSection['NAME'];?>"
                                            decoding="async"
                                            class="img absolute object-fit-cover height-100 width-100"
                                        >
                                    </div>
                                </div>
                            <?endif;?>

                            <div class="name font_14 color_dark f-500 mt mt--16 centered">
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

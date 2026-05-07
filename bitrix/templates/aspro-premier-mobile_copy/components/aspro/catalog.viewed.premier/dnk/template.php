<?
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

$arParams['TITLE_BLOCK'] = strlen($arParams['TITLE_BLOCK']) ? $arParams['TITLE_BLOCK'] : Loc::getMessage('CATALOG_VIEWED_TITLE');
?>
<!-- noindex -->
<?if (strlen($arResult['ERROR'])):?>
    <?ShowError($arResult['ERROR']);?>
<?else:?>
    <?if ($arResult['ITEMS']):?>
        <div class="catalog-viewed-list">
            <div class="maxwidth-theme">
                <?=TSolution\Template\Epilog\Blocks::showBlockTitle($arParams['TITLE_BLOCK']);?>
            </div>
            <div class="maxwidth-theme">
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
                    'pagination' => false,
                    'watchSlidesProgress' => true, // fix slide on click on slide link in mobile template
                    'type' => 'catalog_viewed',
                    'breakpoints' => [
                        425 => [
                            'slidesPerView' => 3,
                            'freeMode' => false,
                        ],
                        601 => [
                            'slidesPerView' => 4,
                            'freeMode' => false,
                        ],
                        992 => [
                            'slidesPerView' => 5,
                            'freeMode' => false,
                        ],
                        1100 => [
                            'slidesPerView' => 6,
                            'freeMode' => false,
                        ],
                    ],
                ];
                ?>
                <div class="catalog-viewed-list__slider-wrap swiper-nav-offset relative">
                    <div class="swiper slider-solution mobile-offset mobile-offset--right" data-plugin-options='<?=json_encode($arOptions)?>'>
                        <div class="swiper-wrapper">
                            <?foreach ($arResult['ITEMS'] as $arItem):?>
                                <div class="catalog-viewed__item swiper-slide" data-id=<?=$arItem['PRODUCT_ID']?> data-picture='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arItem['PICTURE']))?>'>
                                    <div class="catalog-viewed__item-wrap p p--20 outer-rounded-x bordered overflow-block white-bg height-100 color-theme-parent-all" id=<?=$this->GetEditAreaId($arItem['PRODUCT_ID'])?>>
                                        <?
                                        $arButtons = CIBlock::GetPanelButtons($arItem['IBLOCK_ID'], $arItem['PRODUCT_ID'], 0, array('SESSID' => false, 'CATALOG' => true));
                                        $this->AddEditAction($arItem['PRODUCT_ID'], $arButtons['edit']['edit_element']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                                        $this->AddDeleteAction($arItem['PRODUCT_ID'], $arButtons['edit']['delete_element']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => Loc::getMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));
                                        ?>
                                        <div class="catalog-viewed__item__inner flexbox flexbox--column">
                                            <a class="catalog-viewed__item__image mb mb--16 image-rounded-x skeleton-item"></a>
                                            <div class="catalog-viewed__item__info button-rounded-x skeleton-item" style="height:48px;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?endforeach;?>
                        </div>
                    </div>
                    <?if ($countSlides > 1):?>
                        <?TSolution\Functions::showBlockHtml([
                            'FILE' => 'ui/slider-navigation.php',
                            'PARAMS' => [
                                'CLASSES' => 'slider-nav slider-nav--shadow',
                            ]
                        ]);?>
                    <?endif;?>
                </div>
            </div>

            <script>
            BX.Aspro.Loader.once({
                appear: ['.catalog-viewed-list'],
                add: {
                    ext: ['swiper_init', 'prices', 'stickers', 'viewed', 'skeleton'],
                    js: '<?=$GLOBALS['APPLICATION']->oAsset->getFullAssetPath($this->__folder.'/template.js')?>',
                },
            }).then(() => {
                BX.message({
                    CATALOG_FROM_VIEWED: '<?=Loc::getMessage('CATALOG_FROM')?>',
                });

                showViewedItems(
                    document.querySelector('.catalog-viewed-list'),
                    <?=CUtil::PhpToJSObject($arResult['ITEMS'], false) ?>,
                    <?=CUtil::PhpToJSObject([
                        'SHOW_MEASURE' => $arParams['SHOW_MEASURE'] !== 'N' ? 'Y' : 'N',
                        'SHOW_BONUS' => 'N',
                        'MISSING_GOODS_PRICE_DISPLAY' => TSolution::GetFrontParametrValue('MISSING_GOODS_PRICE_DISPLAY'),
                        'MISSING_GOODS_PRICE_TEXT' => TSolution::GetFrontParametrValue('MISSING_GOODS_PRICE_TEXT'),
                    ], false)?>
                );
            });
            </script>
        </div>
    <?endif;?>
<?endif;?>
<!-- /noindex -->

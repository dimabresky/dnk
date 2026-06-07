<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
/* @var array $arParams */
/* @var array $arResult */
/* @global CMain $APPLICATION */
/* @global CUser $USER */
/* @global CDatabase $DB */
/* @var CBitrixComponentTemplate $this */
/* @var string $templateName */
/* @var string $templateFile */
/* @var string $templateFolder */
/* @var string $componentPath */
/* @var CBitrixComponent $component */
$this->setFrameMode(true);

$templateData = [
    'TABS_ID' => 'soc_comments_'.$arResult['ELEMENT']['ID'],
    'TABS_FRAME_ID' => 'soc_comments_div_'.$arResult['ELEMENT']['ID'],
    'BLOG_USE' => ($arResult['BLOG_USE'] ? 'Y' : 'N'),
    'FB_USE' => $arParams['FB_USE'],
    'VK_USE' => $arParams['VK_USE'],
    'BLOG' => [
        'BLOG_FROM_AJAX' => $arResult['BLOG_FROM_AJAX'],
    ],
];

if (!$templateData['BLOG']['BLOG_FROM_AJAX']) {
    if (!empty($arResult['ERRORS'])) {
        ShowError(implode('<br>', $arResult['ERRORS']));

        return;
    }

    $arData = [];
    $arJSParams = [
        'serviceList' => [],
        'settings' => [],
        'tabs' => [],
    ];

    if ($arResult['BLOG_USE']) {
        $templateData['BLOG']['AJAX_PARAMS'] = [
            'IBLOCK_ID' => $arResult['ELEMENT']['IBLOCK_ID'],
            'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
            'XML_ID' => $arParams['XML_ID'],
            'URL_TO_COMMENT' => $arParams['~URL_TO_COMMENT'],
            'WIDTH' => $arParams['WIDTH'],
            'COMMENTS_COUNT' => $arParams['COMMENTS_COUNT'],
            'BLOG_USE' => 'Y',
            'BLOG_FROM_AJAX' => 'Y',
            'FB_USE' => 'N',
            'VK_USE' => 'N',
            'BLOG_TITLE' => $arParams['~BLOG_TITLE'],
            'BLOG_URL' => $arParams['~BLOG_URL'],
            'PATH_TO_SMILE' => $arParams['~PATH_TO_SMILE'],
            'EMAIL_NOTIFY' => $arParams['EMAIL_NOTIFY'],
            'AJAX_POST' => $arParams['AJAX_POST'],
            'SHOW_SPAM' => $arParams['SHOW_SPAM'],
            'SHOW_RATING' => $arParams['SHOW_RATING'],
            'RATING_TYPE' => $arParams['~RATING_TYPE'],
            'CACHE_TYPE' => 'N',
            'CACHE_TIME' => '0',
            'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
            'TEMPLATE_THEME' => $arParams['~TEMPLATE_THEME'],
            'SHOW_DEACTIVATED' => $arParams['SHOW_DEACTIVATED'],
            'COMMENT_PROPERTY' => [
                'UF_BLOG_COMMENT_DOC',
            ],
            'OFFER_ID' => $arParams['OFFER_ID'],
            'REVIEW_COMMENT_REQUIRED' => $arParams['REVIEW_COMMENT_REQUIRED'],
            'REVIEW_FILTER_BUTTONS' => $arParams['REVIEW_FILTER_BUTTONS'],
            'REAL_CUSTOMER_TEXT' => $arParams['REAL_CUSTOMER_TEXT'],
            'MAX_IMAGE_COUNT' => $arParams['MAX_IMAGE_COUNT'],
            'NO_USE_IMAGE' => $arParams['NO_USE_IMAGE'],
        ];
        $arJSParams['serviceList']['blog'] = true;
        $arJSParams['settings']['blog'] = [
            'ajaxUrl' => $templateFolder.'/ajax.php',
            'ajaxParams' => [],
            'contID' => 'bx-cat-soc-comments-blg_'.$arResult['ELEMENT']['ID'],
        ];
        $ajaxUrlParams = [
            'IBLOCK_ID' => $arResult['ELEMENT']['IBLOCK_ID'],
            'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
            'XML_ID' => $arParams['XML_ID'],
            'SITE_ID' => SITE_ID,
        ];

        if ($arParams['OFFER_ID']) {
            $ajaxUrlParams['OFFER_ID'] = $arParams['OFFER_ID'];
        }
        if ($ajaxUrlParams) {
            $arJSParams['settings']['blog']['ajaxUrl'] .= '?'.http_build_query($ajaxUrlParams);
        }

        $arData['BLOG'] = [
            'NAME' => ($arParams['BLOG_TITLE'] != '' ? $arParams['BLOG_TITLE'] : GetMessage('IBLOCK_CSC_TAB_COMMENTS')),
            'ACTIVE' => 'Y',
            'CONTENT' => "<div id='bx-cat-soc-comments-blg_{$arResult['ELEMENT']['ID']}'>".GetMessage('IBLOCK_CSC_COMMENTS_LOADING').'</div>',
        ];
    }

    if (!empty($arData)) {
        $arTabsParams = [
            'DATA' => $arData,
            'ID' => $templateData['TABS_ID'],
        ];
        ?>
        <div id="<?= $templateData['TABS_FRAME_ID']; ?>" class="bx_soc_comments_div bx_important <?= $templateData['TEMPLATE_CLASS']; ?>">
            <?php
            $activeTabId = '';
            $tabIDList = [];
            ?>
            <div id="<?= $templateData['TABS_ID']; ?>" class="bx-catalog-tab-section-container">
                <div class="hidden">
                    <ul class="bx-catalog-tab-list1 nav nav-tabs" style="left: 0;">
                        <?php
                        $id = $templateData['TABS_ID'].'BLOG';
                        $tabActive = true;
                        $activeTabId = 'BLOG';
                        $tabIDList[] = 'BLOG';
                        ?>
                        <li id="<?= $id; ?>" class="secondary-color bordered font_upper_md BLOG"><a href="#<?= $id; ?>_cont" data-toggle="tab"></a></li>
                    </ul>
                </div>

                <div class="bx-catalog-tab-body-container catalog_reviews_extended">
                    <div class="bx-catalog-tab-container">
                        <div id='<?= $id; ?>_cont'>
                            <?= $arData['BLOG']['CONTENT']; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $arJSParams['tabs'] = [
                'activeTabId' => 'BLOG',
                'tabsContId' => $templateData['TABS_ID'],
                'tabList' => $tabIDList,
            ];
            ?>
        </div>
        <script>
            BX.ready(function() {
                var obCatalogComments_<?= $arResult['ELEMENT']['ID']; ?> = new JCCatalogSocnetsComments(<?= CUtil::PhpToJSObject($arJSParams, false, true); ?>);
                $(document).on('click', '.show-comment.btn', function() {
                    if (typeof commentAction !== 'undefined') {
                        commentAction(0, this, 'showComment');
                    }

                    if (BX.Aspro?.Captcha?.selector) {
                        $('.blog-comment__form .captcha-row ' + BX.Aspro.Captcha.selector).css('height', '');
                    }
                })
            })
        </script>
        <?php
    } else {
        ShowError(GetMessage('IBLOCK_CSC_NO_DATA'));
    }
}

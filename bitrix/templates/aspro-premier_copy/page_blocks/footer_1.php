<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
include($_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/footer/settings.php');
?>
<?if(CSite::InDir('/index.php')):?>
<?include $_SERVER['DOCUMENT_ROOT'].'/include/mainpage/components/seo/seo.php'?>
<?endif?>
<footer id="footer" class="footer footer__divider--top footer-1 <?=htmlspecialcharsbx($footerColorClass)?>">
    <div class="footer__top-part">
        <div class="maxwidth-theme">
            <div class="footer__top-part-inner">
                <?//check subscribe text?>
                <?$blockOptions = array(
                    'PARAM_NAME' => 'FOOTER_TOGGLE_SUBSCRIBE',
                    'BLOCK_TYPE' => 'SUBSCRIBE',
                    'IS_AJAX' => $bAjax,
                    'AJAX_BLOCK' => $ajaxBlock,
                    'SUBSCRIBE_TEMPLATE' => 'footer',
                    'VISIBLE' => $bShowSubscribe,
                    'SUBSCRIBE_PARAMS' => array(),
                    'WRAPPER' => 'footer__subscribe footer__divider--bottom p-block p-block--48',
                );?>
                <?=\TSolution\Functions::showFooterBlock($blockOptions);?>
            </div>
        </div>
    </div>

    <div class="footer__main-part">
        <div class="maxwidth-theme">
            <div class="footer__main-part-inner footer-grid footer-grid--3-992 p-block p-block--48">
                <?//show phone, address, email wrapper, social wrapper?>
                <?$visible = (($bShowPhone && $bPhone) || $bShowEmail || $bShowAddress);?>
                <?$blockOptions = array(
                    'PARAM_NAME' => 'FOOTER_ALL_BLOCK',
                    'BLOCK_TYPE' => 'FOOTER_ALL_BLOCK',
                    'TITLE' => GetMessage('FOOTER_CONTACTS'),
                    'IS_AJAX' => $bAjax,
                    'AJAX_BLOCK' => $ajaxBlock,
                    'VISIBLE' => $visible,
                    'WRAPPER' => 'footer__part footer__part--left footer-grid-column-span',
                    'INNER_WRAPPER' => 'footer__info',
                    'ITEMS' => [
                        [ //show phone and callback
                            'PARAM_NAME' => 'FOOTER_TOGGLE_PHONE',
                            'BLOCK_TYPE' => 'PHONE',
                            'VISIBLE' => $bShowPhone && $bPhone,
                            'DROPDOWN_TOP' => true,
                            'WRAPPER' => 'footer__phone footer__info-item',
                            'CALLBACK' => false,
                            'MESSAGE' => GetMessage("S_CALLBACK"),
                        ],
                        [ //show email
                            'PARAM_NAME' => 'FOOTER_TOGGLE_EMAIL',
                            'BLOCK_TYPE' => 'EMAIL',
                            'VISIBLE' => $bShowEmail,
                            'WRAPPER' => 'footer__email footer__info-item',
                            'NO_ICON' => true,
                        ],
                        [ //show address
                            'PARAM_NAME' => 'FOOTER_TOGGLE_ADDRESS',
                            'BLOCK_TYPE' => 'ADDRESS',
                            'VISIBLE' => $bShowAddress,
                            'WRAPPER' => 'footer__address footer__info-item',
                            'NO_ICON' => true,
                        ]
                    ]
                );?>
                <?=\TSolution\Functions::showFooterBlock($blockOptions);?>

                <div class="footer__part footer__part--right width-100">
                    <div class="footer__main-part-menu flexbox flexbox--direction-row column-gap column-gap--12">
                        <div class="footer__part-item">
                            <?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
                                array(
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "PATH" => SITE_DIR."include/footer/menu/menu_bottom1.php",
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "",
                                    "AREA_FILE_RECURSIVE" => "Y",
                                    "EDIT_TEMPLATE" => "include_area.php"
                                ),
                                false, array("HIDE_ICONS" => "Y")
                            );?>
                        </div>

                        <div class="footer__part-item">
                            <?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
                                array(
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "PATH" => SITE_DIR."include/footer/menu/menu_bottom2.php",
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "",
                                    "AREA_FILE_RECURSIVE" => "Y",
                                    "EDIT_TEMPLATE" => "include_area.php"
                                ),
                                false, array("HIDE_ICONS" => "Y")
                            );?>
                        </div>

                        <div class="footer__part-item">
                            <?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
                                array(
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "PATH" => SITE_DIR."include/footer/menu/menu_bottom3.php",
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "",
                                    "AREA_FILE_RECURSIVE" => "Y",
                                    "EDIT_TEMPLATE" => "include_area.php"
                                ),
                                false, array("HIDE_ICONS" => "Y")
                            );?>
                        </div>

                        <div class="footer__part-item">
                            <?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
                                array(
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "PATH" => SITE_DIR."include/footer/menu/menu_bottom4.php",
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "",
                                    "AREA_FILE_RECURSIVE" => "Y",
                                    "EDIT_TEMPLATE" => "include_area.php"
                                ),
                                false, array("HIDE_ICONS" => "Y")
                            );?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer__main-part-inner">
                <?//show social & pay systems?>
                <?$visible = $bShowSocial || $bShowPaySystems;?>
                <?$blockOptions = array(
                    'PARAM_NAME' => 'FOOTER_PS',
                    'BLOCK_TYPE' => 'FOOTER_ALL_BLOCK',
                    'TITLE' => '',
                    'IS_AJAX' => $bAjax,
                    'AJAX_BLOCK' => $ajaxBlock,
                    'VISIBLE' => $visible,
                    'WRAPPER' => 'flexbox flexbox--direction-row flexbox--justify-between flexbox--wrap gap gap--16'.($visible ? ' pb pb--48' : ''),
                    'INNER_WRAPPER' => '',
                    'ITEMS' => [
                        [ //show social
                            'PARAM_NAME' => 'FOOTER_TOGGLE_SOCIAL',
                            'BLOCK_TYPE' => 'SOCIAL',
                            'VISIBLE' => $bShowSocial,
                            'HIDE_MORE' => false,
                            'WRAPPER' => 'footer__social',
                            'NO_ICON' => true,
                        ],
                        [ //show pay systems
                            'PARAM_NAME' => 'FOOTER_TOGGLE_PAY_SYSTEMS',
                            'BLOCK_TYPE' => 'PAY_SYSTEMS',
                            'VISIBLE' => $bShowPaySystems,
                            'WRAPPER' => 'footer__pays',
                        ]
                    ]
                );?>
                <?=\TSolution\Functions::showFooterBlock($blockOptions);?>
            </div>
        </div>
    </div>

    <div class="footer__bottom-part secondary-color font_13">
        <div class="maxwidth-theme">
            <div class="footer__bottom-part-inner footer__divider--top p-block p-block--48">
                <div class="footer__bottom-part-items-wrapper gap gap--8 column-gap column-gap--24 column-gap--max-value row-gap row-gap--32">
                    <div class="footer__part-item">
                        <div class="footer__copy">
                            <?$APPLICATION->IncludeFile(SITE_DIR."include/footer/copy.php", Array(), Array(
                                    "MODE" => "php",
                                    "NAME" => "Copyright",
                                    "TEMPLATE" => "include_area.php",
                                )
                            );?>
                        </div>
                    </div>

                    <div class="footer__part-item line-block--gap line-block line-block--gap-24 line-block--flex-wrap line-block--shrink line-block--row-gap line-block--row-gap-16">
                        <?//show lang?>
                        <?
                        $arShowSites = TSolution\Functions::getShowSites();
                        $countSites = count($arShowSites);
                        $blockOptions = array(
                            'PARAM_NAME' => 'FOOTER_TOGGLE_LANG',
                            'BLOCK_TYPE' => 'LANG',
                            'IS_AJAX' => $bAjax,
                            'AJAX_BLOCK' => $ajaxBlock,
                            'VISIBLE' => $bShowLang && $countSites > 1,
                            'DROPDOWN_TOP' => true,
                            'WRAPPER' => 'footer__part-item footer__part-item-lang',
                            'SITE_SELECTOR_NAME' => $siteSelectorName,
                            'TEMPLATE' => 'main',
                            'SITE_LIST' => $arShowSites,
                        );?>
                        <?=TSolution\Functions::showFooterBlock($blockOptions);?>

                        <?//show theme block?>
                        <?$blockOptions = array(
                            'PARAM_NAME' => 'FOOTER_TOGGLE_THEME_SELECTOR',
                            'BLOCK_TYPE' => 'THEME_SELECTOR',
                            'SIZE' => 'sm',
                            'USE_TEXT' => 'Y',
                            'IS_AJAX' => $bAjax,
                            'AJAX_BLOCK' => $ajaxBlock,
                            'VISIBLE' => $bShowThemeSelector,
                            'PARAMS' => [
                                'USE_TEXT' => 'Y',
                            ],
                            'WRAPPER' => 'footer__part-item footer__part-item-theme',
                        );?>
                        <?=TSolution\Functions::showFooterBlock($blockOptions);?>

                        <div class="footer__part-item">
                            <div class="footer__license">
                                <?$APPLICATION->IncludeFile(SITE_DIR."include/footer/confidentiality.php", Array(), Array(
                                        "MODE" => "php",
                                        "NAME" => "Confidentiality",
                                        "TEMPLATE" => "include_area.php",
                                    )
                                );?>
                            </div>
                        </div>

                        <?if($arTheme['SHOW_OFFER']['VALUE'] === "Y"):?>
                            <div class="footer__part-item">
                                <div class="footer__offer">
                                    <?$APPLICATION->IncludeFile(SITE_DIR."include/footer/offer.php", Array(), Array(
                                            "MODE" => "php",
                                            "NAME" => "Offer",
                                            "TEMPLATE" => "include_area.php",
                                        )
                                    );?>
                                </div>
                            </div>
                        <?endif;?>
                    </div>

                    <div id="bx-composite-banner" class="footer__part-item"></div>

                    <?//show developer block?>
                    <?$blockOptions = array(
                        'PARAM_NAME' => 'FOOTER_TOGGLE_DEVELOPER',
                        'BLOCK_TYPE' => 'DEVELOPER',
                        'IS_AJAX' => $bAjax,
                        'AJAX_BLOCK' => $ajaxBlock,
                        'VISIBLE' => $bShowDeveloper,
                        'WRAPPER' => 'footer__part-item footer__developer',
                    );?>
                    <?=\TSolution\Functions::showFooterBlock($blockOptions);?>
                </div>

                <?//show text block?>
                <?$blockOptions = array(
                    'PARAM_NAME' => 'FOOTER_TOGGLE_TEXT',
                    'BLOCK_TYPE' => 'TEXT',
                    'IS_AJAX' => $bAjax,
                    'AJAX_BLOCK' => $ajaxBlock,
                    'VISIBLE' => $bShowText,
                    'WRAPPER' => 'footer__part-item footer__part-item-text'.($bShowText ? ' pt pt--48' : ''),
                );?>
                <?=TSolution\Functions::showFooterBlock($blockOptions);?>
            </div>
        </div>
    </div>
</footer>

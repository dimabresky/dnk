<?
$bShowButton1 = strlen($arItem['PROPERTIES']['BUTTON1TEXT']['VALUE']) && (strlen($arItem['PROPERTIES']['BUTTON1LINK']['VALUE']) || strlen($arItem['PROPERTIES']['FORM_CODE1']['VALUE']));
$bShowButton2 = strlen($arItem['PROPERTIES']['BUTTON2TEXT']['VALUE']) && (strlen($arItem['PROPERTIES']['BUTTON2LINK']['VALUE']) || strlen($arItem['PROPERTIES']['FORM_CODE2']['VALUE']));

$bShowButtons = $bShowButton1 || $bShowButton2;
$bShowButtonsWrapper = $bShowButtons || $bShowVideo;

$button1Class = $arItem['PROPERTIES']['BUTTON1CLASS']['VALUE_XML_ID'] ? $arItem['PROPERTIES']['BUTTON1CLASS']['VALUE_XML_ID'] : 'btn-default';
$button1Color = $arItem['PROPERTIES']['BUTTON1COLOR']['VALUE_XML_ID'] ? $arItem['PROPERTIES']['BUTTON1COLOR']['VALUE_XML_ID'] : '';
$button1Link = $arItem['PROPERTIES']['BUTTON1LINK']['VALUE'];
$button1Form = $arItem['PROPERTIES']['FORM_CODE1']['VALUE'] ? 'data-event="jqm" data-param-id="'.TSolution::getFormID($arItem['PROPERTIES']['FORM_CODE1']['VALUE']).'" data-name="order_from_banner"' : '';
$button1Text = $arItem['PROPERTIES']['BUTTON1TEXT']['VALUE'];
$button1Attr = $arItem['PROPERTIES']['BUTTON1ATTR']['VALUE'] ? $arItem['PROPERTIES']['BUTTON1ATTR']['VALUE'] : '';

$button2Class = $arItem['PROPERTIES']['BUTTON2CLASS']['VALUE_XML_ID'] ? $arItem['PROPERTIES']['BUTTON2CLASS']['VALUE_XML_ID'] : 'btn-default';
$button2Color = $arItem['PROPERTIES']['BUTTON2COLOR']['VALUE_XML_ID'] ? $arItem['PROPERTIES']['BUTTON2COLOR']['VALUE_XML_ID'] : '';
$button2Link = $arItem['PROPERTIES']['BUTTON2LINK']['VALUE'];
$button2Form = $arItem['PROPERTIES']['FORM_CODE2']['VALUE'] ? 'data-event="jqm" data-param-id="'.TSolution::getFormID($arItem['PROPERTIES']['FORM_CODE2']['VALUE']).'" data-name="order_from_banner"' : '';
$button2Text = $arItem['PROPERTIES']['BUTTON2TEXT']['VALUE'];
$button2Attr = $arItem['PROPERTIES']['BUTTON2ATTR']['VALUE'] ? $arItem['PROPERTIES']['BUTTON2ATTR']['VALUE'] : '';

$button1HrefSafe = (preg_match('#^(https?:)?//#i', (string)$button1Link) || mb_strpos((string)$button1Link, '/') === 0)
    ? (string)$button1Link : '#';
$button1ClassSafe = preg_replace('/[^a-z0-9_-]/i', '', (string)$button1Class);
$button1ColorSafe = preg_replace('/[^a-z0-9_-]/i', '', (string)$button1Color);

$button2HrefSafe = (preg_match('#^(https?:)?//#i', (string)$button2Link) || mb_strpos((string)$button2Link, '/') === 0)
    ? (string)$button2Link : '#';
$button2ClassSafe = preg_replace('/[^a-z0-9_-]/i', '', (string)$button2Class);
$button2ColorSafe = preg_replace('/[^a-z0-9_-]/i', '', (string)$button2Color);
?>
<?if ($bShowButtonsWrapper):?>
    <div class="banners-big__buttons theme-light mt <?=(!$bWideText || $bLowBanner ? 'mt--28' : 'mt--32')?>">
        <div class="line-block line-block--gap line-block--gap-12">
            <?if ($bShowButtons):?>
                <?if ($bShowButton1):?>
                    <div class="banners-big__buttons-item">
                        <a href="<?=htmlspecialcharsbx($button1HrefSafe)?>" <?=$button1Form?> class="btn btn-lg <?=htmlspecialcharsbx($button1ClassSafe)?> <?=htmlspecialcharsbx($button1ColorSafe)?>">
                            <?=htmlspecialcharsbx((string)$button1Text)?>
                        </a>
                    </div>
                <?endif;?>
                <?if ($bShowButton2):?>
                    <div class="banners-big__buttons-item">
                        <a href="<?=htmlspecialcharsbx($button2HrefSafe)?>" <?=$button2Form?> class="btn btn-lg <?=htmlspecialcharsbx($button2ClassSafe)?> <?=htmlspecialcharsbx($button2ColorSafe)?>">
                            <?=htmlspecialcharsbx((string)$button2Text)?>
                        </a>
                    </div>
                <?endif;?>
            <?endif;?>

            <?if ($bShowVideo):?>
                <div class="banners-big__buttons-item banners-big__buttons-item--video">
                    <span class="btn btn-lg <?=($bVideoAutoStart ? 'loading' : '')?> btn-video <?=($buttonVideoText ? 'with-text' : '')?> <?=$buttonVideoClass?> <?=$buttonVideoColor?>" title="<?=htmlspecialcharsbx($buttonVideoText)?>">
                        <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/video_buttons_sprite.svg#video-play-13-13', 'play', [
                            'WIDTH' => 13,
                            'HEIGHT' => 13
                        ]);?>
                        <?=$buttonVideoText?>
                    </span>
                </div>
            <?endif;?>
        </div>
    </div>
<?endif;?>

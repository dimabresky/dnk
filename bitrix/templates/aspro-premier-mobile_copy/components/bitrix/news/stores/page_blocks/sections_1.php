<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
use Bitrix\Main\Localization\Loc;

$arRegion = TSolution\Regionality::getCurrentRegion();
$bGallery = boolval($arRegion);
?>
<div class="contacts-v1 contacts-detail" itemscope itemtype="http://schema.org/LocalBusiness">
    <?// hidden text to validate microdata?>
    <div class="hidden">
        <?global $arSite;?>
        <span itemprop="name"><?=$arSite['NAME'];?></span>
    </div>
    <div class="grid-list grid-list--items grid-list--items-2-from-601">
        <div class="contacts-detail__image <?= $bGallery ? ' contacts-detail__image--gallery' : '';?>">
            <?TSolution::showContactImg($bGallery);?>
        </div>
        <?if ($bUseMap):?>
            <div class="map-view outer-rounded-x overflow-block relative">
                <?if ($arRegion):?>
					<?$frame = new \Bitrix\Main\Page\FrameHelper('header-allcmap-block'.$iCalledID);?>
					<?$frame->begin('');?>
				<?endif;?>
                <?$APPLICATION->IncludeFile(SITE_DIR.'include/contacts-site-map-'.(TSolution::GetFrontParametrValue('CONTACTS_TYPE_MAP') == 'GOOGLE' ? 'google' : 'yandex').'.php', [], [
                    'MODE' => 'html',
                    'TEMPLATE' => 'include_area.php',
                    'NAME' => 'Map',
                ]);?>
                <?if ($arRegion):?>
					<?$frame->end();?>
				<?endif;?>
            </div>
        <?endif;?>
    </div>
    <div class="grid-list grid-list--items grid-list--items-4-from-1200 grid-list--items-3-from-992 grid-list--items-2-from-601 mt mt--12">
        <div class="grid-list__item flexbox outer-rounded-x grey-bg p p--20">
            <?TSolution::showContactAddr([
                'LABEL' => Loc::getMessage('T_CONTACTS_ADDRESS'),
                'VIEW_TYPE' => 'grid',
            ]);?>
        </div>
        <div class="grid-list__item flexbox outer-rounded-x grey-bg p p--20">
            <?TSolution::showContactSchedule([
                'TEXT' => Loc::getMessage('T_CONTACTS_SCHEDULE'),
                'VIEW_TYPE' => 'grid',
            ]);?>
        </div>
        <div class="grid-list__item flexbox outer-rounded-x grey-bg p p--20">
            <?TSolution::showContactPhones([
                'CLASS' => 'mt mt--40',
                'LABEL' => Loc::getMessage('T_CONTACTS_PHONE'),
            ]);?>
        </div>
        <div class="grid-list__item flexbox outer-rounded-x grey-bg p p--20">
            <?TSolution::showContactEmail([
                    'TEXT' => Loc::getMessage('T_CONTACTS_EMAIL'),
                    'VIEW_TYPE' => 'grid',
            ]);?>
        </div>
        <div class="grid-list__item flexbox outer-rounded-x grey-bg p p--20">
            <div class="font_13 secondary-color flex-1"><?=Loc::getMessage('T_CONTACTS_SOCIAL');?></div>
            <div class="mt mt--40">
                <?$APPLICATION->IncludeComponent(
                    'aspro:social.info.premier',
                    '.default',
                    [
                        'CACHE_TYPE' => 'A',
                        'CACHE_TIME' => '3600000',
                        'CACHE_GROUPS' => 'N',
                        'COMPONENT_TEMPLATE' => '.default',
                        'SVG' => false,
                        'IMAGES' => true,
                        'ICONS' => true,
                        'SIZE' => 'large',
                        'HIDE_MORE' => false,
                    ],
                    false
                );?>
            </div>
        </div>
        <?if ($bUseFeedback):?>
            <button type="button" class="text-align-left grid-list__item flexbox outer-rounded-x bordered p p--20 shadow-hovered shadow-no-border-hovered white-bg pointer stroke-grey-hover" data-event="jqm" data-param-id="aspro_premier_question" data-name="question">
                <div class="font_13 secondary-color flex-1"><?=Loc::getMessage('T_CONTACTS_QUESTION1');?></div>
                <div class="flexbox flexbox--direction-row flexbox--align-center flexbox--justify-between gap gap--12 mt mt--40 width-100 text-align-left flex-grow-0">
                    <div class="font_15 color_dark fw-500"><?=Loc::getMessage('T_CONTACTS_QUESTION2');?></div>
                    <?= TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', '', ['WIDTH' => 6, 'HEIGHT' => 12]);?>
                </div>
            </button>
        <?endif;?>
    </div>
    <div class="mt mt--48 mb mb--80 max-width-1092">
        <?TSolution::showContactDesc();?>
    </div>
</div>

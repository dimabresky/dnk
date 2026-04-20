<?
use \Bitrix\Main\Localization\Loc;

$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.history.js');
?>
<div class="contacts__tabs">
    <div class="tabs tabs-history">
        <ul class="nav nav-tabs font_14 font_weight--600">
            <li class="bordered rounded-4 active"><a href="#map" data-toggle="tab"><?=Loc::getMessage('TABS_MAP')?></a></li>
            <li class="bordered rounded-4"><a href="#list" data-toggle="tab"><?=Loc::getMessage('TABS_LIST')?></a></li>
        </ul>
    </div>
</div>
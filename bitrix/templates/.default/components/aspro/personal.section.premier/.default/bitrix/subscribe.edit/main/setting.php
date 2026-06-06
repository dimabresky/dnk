<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $arTheme;

$application = \Bitrix\Main\Application::getInstance();
$session = $application->getSession();

if ($arResult['ID'] && $session->get('subscription/newsletter')) {
    TSolution\Validation::addConsents($arResult['ID'], 'subscription/newsletter');
}
?>
<div class="personal__top-form bordered outer-rounded-x p p--32">
    <h4><?=Loc::getMessage('subscr_title_settings');?></h4>

    <form id="subscribe-settings-form" name="subscribe-settings-form" action="<?=$arResult['FORM_ACTION'];?>" method="post" class="form mt mt--32">
        <div class="form-body">
            <?=bitrix_sessid_post();?>

            <?$email = (strlen($arResult['SUBSCRIPTION']['EMAIL']) ? $arResult['SUBSCRIPTION']['EMAIL'] : $arResult['REQUEST']['EMAIL']);?>
            <div class="form-group fill-animate <?=strlen($email) ? 'input-filed' : '';?>">
                <label for="EMAIL" class="font_13 color_dark"><?=Loc::getMessage('subscr_email');?>&nbsp;<span class="required-star">*</span></label>
                <div class="half-block">
                    <div class="input">
                        <input class="form-control email" type="text" id="EMAIL" name="EMAIL" value="<?=$email;?>" size="30" maxlength="255" required />
                    </div>
                    <div class="text_block font_13"><?=Loc::getMessage('subscr_settings_note1');?> <?=Loc::getMessage('subscr_settings_note2');?></div>
                </div>
            </div>

            <div class="form-group subscribes-block mt mt--32">
                <h6 class="mb mb--20"><?=Loc::getMessage('subscr_rub');?></h6>

                <?foreach ($arResult['RUBRICS'] as $itemID => $itemValue):?>
                    <input class="form-checkbox__input" type="checkbox" name="RUB_ID[]" id="rub_<?=$itemValue['ID'];?>" value="<?=$itemValue['ID'];?>" <?=$itemValue['CHECKED'] ? ' checked' : '';?> />
                    <label for="rub_<?=$itemValue['ID'];?>" class="form-checkbox__label">
                        <span class="bx_filter_input_checkbox">
                            <span><?=$itemValue['NAME'];?></span>
                        </span>
                        <span class="form-checkbox__box form-box"></span>
                    </label>
                <?endforeach;?>
            </div>

            <div class="form-group format-subscribe-group mt mt--32">
                <h6 class="mb mb--20"><?=Loc::getMessage('subscr_fmt');?></h6>

                <div class="form-radiobox width-100">
                    <input class="form-radiobox__input" type="radio" id="text" name="FORMAT" value="text" <?=$arResult['SUBSCRIPTION']['FORMAT'] == 'text' ? ' checked' : '';?> />
                    <label for="text" class="form-radiobox__label">
                        <span class="bx_filter_input_checkbox">
                            <span><?=Loc::getMessage('subscr_text');?></span>
                        </span>
                        <span class="form-radiobox__box"></span>
                    </label>
                </div>

                <div class="form-radiobox width-100 mt mt--12">
                    <input class="form-radiobox__input" type="radio" name="FORMAT" id="html" value="html" <?=$arResult['SUBSCRIPTION']['FORMAT'] == 'html' ? ' checked' : '';?> />
                    <label for="html" class="form-radiobox__label">
                        <span class="bx_filter_input_checkbox">
                            <span>HTML</span>
                        </span>
                        <span class="form-radiobox__box"></span>
                    </label>
                </div>
            </div>

            <?if (TSolution::GetFrontParametrValue('CAPTCHA_ON_SUBSCRIBE') === 'Y'):?>
                <?$arResult['CAPTCHACode'] = $APPLICATION->CaptchaGetCode();?>
                <div class="captcha-row clearfix fill-animate">
                    <label class="font_13 color_999"><span><?=GetMessage('CAPTCHA_FORM_TITLE');?>&nbsp;<span class="required-star">*</span></span></label>
                    <div class="captcha_image">
                        <img data-src="" src="/bitrix/tools/captcha.php?captcha_sid=<?=htmlspecialcharsbx($arResult['CAPTCHACode']);?>" class="captcha_img">
                        <input type="hidden" name="captcha_sid" class="captcha_sid" value="<?=htmlspecialcharsbx($arResult['CAPTCHACode']);?>">
                        <div class="captcha_reload"></div>
                        <span class="refresh"><a href="javascript:;" rel="nofollow"><?=GetMessage('REFRESH');?></a></span>
                    </div>
                    <div class="captcha_input">
                        <input type="text" class="inputtext form-control captcha" name="captcha_word" size="30" maxlength="50" value="" required>
                    </div>
                </div>
            <?endif;?>

            <?if (TSolution::GetFrontParametrValue('SHOW_LICENCE') === 'Y'):?>
                    <?TSolution\Functions::showBlockHtml([
                        'FILE' => 'consent/dnk/userconsent.php',
                        'PARAMS' => [
                            'OPTION_CODE' => 'AGREEMENT_SUBSCRIBE',
                            'SUBMIT_TEXT' => GetMessage("subscr_add"),
                            'REPLACE_FIELDS' => [],
                            'INPUT_NAME' => TSolution\Validation::LICENSE_INPUT_NAME,
                            'INPUT_ID' => 'licenses_subscribe',
                        ]
                    ]);?>
            <?endif;?>
        </div>

        <div class="form-footer mt mt--32">
            <div class="form-footer__buttons">
                <button type="submit" class="btn btn-default btn-lg" name="Save" value="Save"><?=$arResult['ID'] > 0 ? Loc::getMessage('subscr_upd') : Loc::getMessage('subscr_add');?></button>
                <?/* <input type="reset" class="btn btn-transparent btn-lg" value="<?=Loc::getMessage('subscr_reset')?>" name="reset" /> */?>
            </div>
        </div>

        <input type="hidden" name="PostAction" value="<?=$arResult['ID'] > 0 ? 'Update' : 'Add';?>" />
        <input type="hidden" name="ID" value="<?=$arResult['SUBSCRIPTION']['ID'];?>" />

        <?if ($_REQUEST['register'] == 'YES'):?>
            <input type="hidden" name="register" value="YES" />
        <?endif;?>

        <?if ($_REQUEST['authorize'] == 'YES'):?>
            <input type="hidden" name="authorize" value="YES" />
        <?endif;?>
    </form>

    <script>
        BX.Aspro.Utils.readyDOM(() => {
            $('#subscribe-settings-form').validate({
                submitHandler: function (form) {
                    if ($('form[name="subscribe-settings-form"]').valid()) {
                        setTimeout(function() {
                            $(form).find('button[type="submit"]').attr("disabled", "disabled");
                        }, 300);

                        const eventdata = {
                            form,
                            type: 'form_submit',
                            form_name: 'subscribe-settings-form'
                        };
                        BX.onCustomEvent('onSubmitForm', [eventdata]);
                    }
                }
            });
        });
    </script>
</div>

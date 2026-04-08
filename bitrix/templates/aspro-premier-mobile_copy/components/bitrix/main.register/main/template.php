<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$this->setFrameMode(false);

$arExtensions = ['profile', 'validate', 'phone_input', 'phone_mask', 'eye.password'];

if ($arResult['SHOW_SMS_FIELD']) {
    CJSCore::Init('phone_auth');
    $arExtensions[] = 'phonecode';
}

if (Tsolution::GetFrontParametrValue('USE_INTL_PHONE') === 'Y') {
    $arExtensions[] = 'intl_phone_input';
}

TSolution\Extensions::init($arExtensions);

global $arTheme;

// get phone auth params
[
    $bPhoneAuthSupported,
    $bPhoneAuthShow,
    $bPhoneAuthRequired,
    $bPhoneAuthUse,
] = TSolution\PhoneAuth::getOptions();

if (
    $USER->IsAuthorized()
    || (
        empty($arResult['ERRORS'])
        && !empty($_POST['register_submit_button'])
        && $arResult['USE_EMAIL_CONFIRMATION'] === 'N'
        && !$arResult['SHOW_SMS_FIELD']
    )
) {
    LocalRedirect($arParams['PERSONAL_PAGE']);
    exit;
}
?>
<div class="registraion-page pk-page">
    <?if ($arResult['SHOW_SMS_FIELD']):?>
        <div class="form form--send-sms">
            <div class="form-header">
                <div class="text">
                    <div class="title switcher-title font_24 color_222"><?=GetMessage('REGISTER_FIELD_SMS_SENDED_TITLE');?></div>
                    <div class="form_desc font_15"><?=GetMessage('main_register_sms_sended', ['#PHONE_NUMBER#' => $arResult['VALUES']['PHONE_NUMBER']]);?></div>
                </div>
            </div>
            <form id="registraion-page-form" method="post" action="<?=POST_FORM_ACTION_URI;?>" name="regform">
                <?if ($arResult['BACKURL'] != ''):?>
                    <input type="hidden" name="backurl" value="<?=htmlspecialcharsbx($arResult['BACKURL']);?>" />
                <?endif;?>

                <input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult['SIGNED_DATA']);?>" />
                <input type="hidden" name="code_submit_button" value="Y" />

                <div class="form_body">
                    <div class="form-group phone_code">
                        <?if (array_key_exists('SMS_CODE', $arResult['ERRORS'])) {
                            $class = 'class="error"';
                        }?>
                        <label class="font_14" for="input_SMS_CODE"><?=GetMessage('REGISTER_FIELD_SMS_CODE');?> <span class="required-star">*</span></label>
                        <div class="input">
                            <input id="input_SMS_CODE" class="form-control required" size="30" type="text" name="SMS_CODE" value="<?=htmlspecialcharsbx($arResult['SMS_CODE']);?>" autocomplete="off" <?=$class;?> />
                        </div>
                    </div>
                </div>
                <div class="form-footer hidden">
                    <button class="btn btn-default btn-lg btn-wide" type="submit" name="code_submit_button1" value="Y"><?=GetMessage('main_register_sms_send');?></button>
                </div>
            </form>
            <div id="bx_register_error" style="display:none"><?ShowError('error');?></div>
            <div id="bx_register_resend"></div>
            <script>
                document.regform.SMS_CODE.focus();

                if (
                    typeof appAspro === 'object'
                    && appAspro
                    && appAspro.phone
                ) {
                    appAspro.phone.init($('#registraion-page-form input.phone'));
                }

                BX.Aspro.Utils.readyDOM(() => {
                    $('#registraion-page-form').validate({
                        submitHandler: function(form) {
                            if ($(form).valid()) {
                                var $button = $(form).find('button[type=submit]');
                                if ($button.length) {
                                    if (!$button.hasClass('loadings')) {
                                        $button.addClass('loadings');

                                        var eventdata = {type: 'form_submit', form: form, form_name: 'REGISTER'};
                                        BX.onCustomEvent('onSubmitForm', [eventdata]);
                                    }
                                }
                            }
                        }
                    });

                    $('#registraion-page-form .phone_code input[type=text]').phonecode(
                        <?=CUtil::PhpToJSObject(
                            [
                                'USER_ID' => $arResult['VALUES']['USER_ID'],
                                'USER_PHONE_NUMBER' => $arResult['VALUES']['PHONE_NUMBER'],
                            ]
                        );?>,
                        function(input, data, response) {
                            if (
                                typeof response !== 'undefined'
                                && response === 'true'
                            ) {
                                let $form = $(input).closest('form');

                                if (
                                    $form.length &&
                                    !$form.find('button[type=submit].loadings').length
                                ) {
                                    $form.find('button[type=submit]').trigger('click');
                                }
                            }
                        }
                    );
                });

                new BX.PhoneAuth({
                    containerId: 'bx_register_resend',
                    errorContainerId: 'bx_register_error',
                    interval: <?=$arResult['PHONE_CODE_RESEND_INTERVAL'];?>,
                    data: <?=CUtil::PhpToJSObject(['signedData' => $arResult['SIGNED_DATA']]);?>,
                    onError: function(response) {
                        const errorDiv = BX('bx_register_error');
                        const errorNode = BX.findChildByClassName(errorDiv, 'errortext');

                        errorNode.innerHTML = '';
                        for (var i = 0; i < response.errors.length; i++) {
                            errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
                        }
                        errorDiv.style.display = '';
                    }
                });
            </script>
        </div>
    <?else:?>
        <div class="form">
            <?if ($arResult['ERRORS']):?>
                <?php
                foreach ($arResult['ERRORS'] as $key => $error) {
                    if (intval($key) == 0 && $key !== 0) {
                        $arResult['ERRORS'][$key] = str_replace('#FIELD_NAME#', $key.'&quot;'.GetMessage('REGISTER_FIELD_'.$key).'&quot;', $error);
                    }
                }
                ?>
                <div class="alert alert-danger"><?ShowError(implode('<br />', $arResult['ERRORS']));?></div>
            <?endif;?>

            <?if (
                empty($arResult['ERRORS'])
                && !empty($_POST['register_submit_button'])
                && $arResult['USE_EMAIL_CONFIRMATION'] === 'Y'
            ):?>
                <div class="alert alert-success"><?=GetMessage('REGISTER_EMAIL_WILL_BE_SENT');?></div>
            <?else:?>
                <form id="registraion-page-form" method="post" action="<?=POST_FORM_ACTION_URI;?>" name="regform" enctype="multipart/form-data" >
                    <?if (TSolution::checkContentFile(SITE_DIR.'include/register_description.php')):?>
                        <div class="top-text font_15">
                            <?$APPLICATION->IncludeFile(SITE_DIR.'include/register_description.php', [], ['MODE' => 'html', 'NAME' => GetMessage('REGISTER_INCLUDE_AREA')]);?>
                        </div>
                    <?endif;?>

                    <?if ($arResult['BACKURL'] != ''):?>
                        <input type="hidden" name="backurl" value="<?=htmlspecialcharsbx($arResult['BACKURL']);?>" />
                    <?endif;?>

                    <input type="hidden" name="register_submit_button" value="reg" />

                    <?php
                    $arTmpField = $arFields = $arUFields = [];
                    $arTmpField = array_combine($arResult['SHOW_FIELDS'], $arResult['SHOW_FIELDS']);
                    unset($arTmpField['PASSWORD']);
                    unset($arTmpField['CONFIRM_PASSWORD']);

                    if ($arResult['USER_PROPERTIES']['SHOW'] == 'Y') {
                        foreach ($arParams['USER_PROPERTY'] as $name) {
                            $arUFields[$name] = $arResult['USER_PROPERTIES']['DATA'][$name];
                        }
                    }

                    if ($arParams['SHOW_FIELDS']) {
                        foreach ($arParams['SHOW_FIELDS'] as $name) {
                            $arFields[$arTmpField[$name]] = $name;
                        }
                    } else {
                        $arFields = $arTmpField;
                    }

                    $arFields['PASSWORD'] = 'PASSWORD';
                    $arFields['CONFIRM_PASSWORD'] = 'CONFIRM_PASSWORD';
                    $arFields['LOGIN'] = 'LOGIN';
                    $class = 'form-control';

                    if ($arTheme['CABINET']['DEPENDENT_PARAMS']['PERSONAL_ONEFIO']['VALUE'] != 'N') {
                        $arResult['VALUES']['NAME'] = trim(implode(' ', [$arResult['VALUES']['LAST_NAME'], $arResult['VALUES']['NAME'], $arResult['VALUES']['SECOND_NAME']]));

                        unset($arFields['LAST_NAME']);
                        unset($arFields['SECOND_NAME']);
                    }
                    ?>
                    <div class="form-body">
                        <?foreach ($arFields as $FIELD):?>
                            <?php
                            if ($FIELD === 'PHONE_NUMBER') {
                                continue;
                            }

                            $bShowInputWrapper = $arTheme['CABINET']['DEPENDENT_PARAMS']['LOGIN_EQUAL_EMAIL']['VALUE'] != 'Y'
                                || (
                                    $FIELD != 'LOGIN'
                                    && $arTheme['CABINET']['DEPENDENT_PARAMS']['LOGIN_EQUAL_EMAIL']['VALUE'] == 'Y'
                                );
                            ?>

                            <?if ($bShowInputWrapper):?>
                                <div class="form-group <?= $arResult['VALUES'][$FIELD] ? 'input-filed' : '';?>">
                                    <label class="font_14" for="input_<?=$FIELD;?>">
                                        <?= ($arTheme['CABINET']['DEPENDENT_PARAMS']['PERSONAL_ONEFIO']['VALUE'] != 'N' && $FIELD == 'NAME') ? GetMessage('REGISTER_FIELD_ONENAME') : GetMessage('REGISTER_FIELD_'.$FIELD);?> <?if ($arResult['REQUIRED_FIELDS_FLAGS'][$FIELD] == 'Y'):?><span class="required-star">*</span><?endif;?>
                                    </label>

                                    <?if (array_key_exists($FIELD, $arResult['ERRORS'])):?>
                                        <?$class .= ' error';?>
                                    <?endif;?>
                                    <div class="input">
                            <?endif;?>
                                            <?switch ($FIELD) {
                                                case 'PASSWORD':?>
                                                    <input size="30" type="password" id="input_<?=$FIELD;?>" name="REGISTER[<?=$FIELD;?>]" required value="<?=$arResult['VALUES'][$FIELD];?>" autocomplete="off" class="form-control password <?=(array_key_exists($FIELD, $arResult['ERRORS'])) ? 'error' : '';?>"  />

                                                <?break;
                                                case 'CONFIRM_PASSWORD':?>
                                                    <input size="30" type="password" id="input_<?=$FIELD;?>" name="REGISTER[<?=$FIELD;?>]" required value="<?=$arResult['VALUES'][$FIELD];?>" autocomplete="off" class="form-control confirm_password <?=(array_key_exists($FIELD, $arResult['ERRORS'])) ? 'error' : '';?>" />

                                                <?break;
                                                case 'PERSONAL_GENDER':?>
                                                    <select name="REGISTER[<?=$FIELD;?>]" id="input_<?=$FIELD;?>">
                                                        <option value=""><?=GetMessage('USER_DONT_KNOW');?></option>
                                                        <option value="M"<?=$arResult['VALUES'][$FIELD] == 'M' ? ' selected="selected"' : '';?>><?=GetMessage('USER_MALE');?></option>
                                                        <option value="F"<?=$arResult['VALUES'][$FIELD] == 'F' ? ' selected="selected"' : '';?>><?=GetMessage('USER_FEMALE');?></option>
                                                    </select>
                                                    <?break;
                                                case 'PERSONAL_COUNTRY':
                                                case 'WORK_COUNTRY':?>
                                                    <select name="REGISTER[<?=$FIELD;?>]" id="input_<?=$FIELD;?>">
                                                        <?foreach ($arResult['COUNTRIES']['reference_id'] as $key => $value) {?>
                                                            <option value="<?=$value;?>"<?if ($value == $arResult['VALUES'][$FIELD]):?> selected="selected"<?endif;?>><?=$arResult['COUNTRIES']['reference'][$key];?></option>
                                                        <?}?>
                                                    </select>
                                                    <?break;
                                                case 'PERSONAL_PHOTO':
                                                case 'WORK_LOGO':?>
                                                    <input size="30" type="file" class="form-control" id="input_<?=$FIELD;?>" name="REGISTER_FILES_<?=$FIELD;?>" />
                                                    <?break;
                                                case 'PERSONAL_NOTES':
                                                case 'WORK_NOTES':?>
                                                    <textarea cols="30" rows="5" class="form-control" id="input_<?=$FIELD;?>" name="REGISTER[<?=$FIELD;?>]"><?= htmlspecialcharsbx($arResult['VALUES'][$FIELD]); ?></textarea>
                                                    <?break;?>
                                                <?case 'EMAIL':?>
                                                    <input size="30" type="email" id="input_<?=$FIELD;?>" name="REGISTER[<?=$FIELD;?>]" <?= $arResult['EMAIL_REQUIRED'] || in_array($FIELD, $arResult['REQUIRED_FIELDS']) ? 'required' : '';?> value="<?=$arResult['VALUES'][$FIELD];?>" class="<?=$class;?>" id="emails"/>
                                                <?break;?>
                                                <?case 'NAME':?>
                                                    <input size="30" type="text" id="input_<?=$FIELD;?>" name="REGISTER[<?=$FIELD;?>]" <?= $arResult['REQUIRED_FIELDS_FLAGS'][$FIELD] == 'Y' ? 'required' : '';?> value="<?=$arResult['VALUES'][$FIELD];?>" class="<?=$class;?>"/>
                                                <?break;?>
                                                <?case 'PERSONAL_PHONE':?>
                                                    <input size="30" type="text" id="input_<?=$FIELD;?>" name="REGISTER[<?=$FIELD;?>]" class="form-control phone <?=(array_key_exists($FIELD, $arResult['ERRORS'])) ? 'error' : '';?>" <?= $arResult['REQUIRED_FIELDS_FLAGS'][$FIELD] == 'Y' ? 'required' : '';?> value="<?=$arResult['VALUES'][$FIELD];?>" />
                                                <?break;?>
                                                <?break;
                                                default:?>
                                                    <?// hide login?>
                                                    <input size="30" id="input_<?=$FIELD;?>" class="form-control" <?= ($FIELD == 'LOGIN' && $arTheme['CABINET']['DEPENDENT_PARAMS']['LOGIN_EQUAL_EMAIL']['VALUE'] == 'Y') ? 'type="hidden" value="1"' : 'type="text"';?> name="REGISTER[<?=$FIELD;?>]" value="<?=$arResult['VALUES'][$FIELD];?>" />
                                                    <?if ($FIELD == 'PERSONAL_BIRTHDAY') {?>
                                                        <?$APPLICATION->IncludeComponent(
                                                            'bitrix:main.calendar',
                                                            '',
                                                            [
                                                                'SHOW_INPUT' => 'N',
                                                                'FORM_NAME' => 'regform',
                                                                'INPUT_NAME' => 'REGISTER[PERSONAL_BIRTHDAY]',
                                                                'SHOW_TIME' => 'N',
                                                            ],
                                                            null,
                                                            ['HIDE_ICONS' => 'Y']
                                                        );?>
                                                    <?}?>
                                                    <?break;?>
                                            <?}?>
                            <?if ($bShowInputWrapper):?>
                                    </div>

                                    <?if (array_key_exists($FIELD, $arResult['ERRORS'])):?>
                                        <label class="error"><?=GetMessage('REGISTER_FILL_IT');?></label>
                                    <?endif;?>
                                    <div class="text_block font_13">
                                        <?if (
                                            $arTheme['CABINET']['DEPENDENT_PARAMS']['LOGIN_EQUAL_EMAIL']['VALUE'] == 'Y'
                                            && $FIELD == 'EMAIL'
                                        ):?>
                                            <?=GetMessage('REGISTER_FIELD_TEXT_'.$FIELD.'_EQUAL');?>
                                        <?else:?>
                                            <?=GetMessage('REGISTER_FIELD_TEXT_'.$FIELD);?>
                                        <?endif;?>
                                    </div>
                                </div>
                            <?endif;?>
                        <?endforeach;?>

                        <?if ($arUFields):?>
                            <?foreach ($arUFields as $arUField):?>
                                <div class="r">
                                    <label><span><?=$arUField['EDIT_FORM_LABEL'];?>&nbsp;<?if ($arUField['MANDATORY'] == 'Y'):?><span class="required-star">*</span><?endif;?></span></label>
                                    <?$APPLICATION->IncludeComponent(
                                        'bitrix:system.field.edit',
                                        $arUField['USER_TYPE']['USER_TYPE_ID'],
                                        [
                                            'bVarsFromForm' => $arResult['bVarsFromForm'],
                                            'arUserField' => $arUField,
                                            'form_name' => 'regform',
                                        ],
                                        null,
                                        ['HIDE_ICONS' => 'Y']
                                    );?>
                                </div>
                            <?endforeach;?>
                        <?endif;?>

                        <?if ($arResult['USE_CAPTCHA'] == 'Y'):?>
                            <?php
                            /**
                             * @var TSolution\Captcha\Service $captcha
                             */
                            $captcha = TSolution\Captcha::getInstance();
                            ?>
                            <div class="captcha-row clearfix">
                                <label for="captcha_word" class="font_14"><span><?= $captcha->isService() && $captcha->isActive() ? GetMessage('FORM_GENERAL_RECAPTCHA') : GetMessage('REGISTER_CAPTCHA_PROMT');?>&nbsp;<span class="required-star">*</span></span></label>
                                <div class="captcha_image">
                                    <img data-src="" src="/bitrix/tools/captcha.php?captcha_sid=<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE']);?>" class="captcha_img" />
                                    <input type="hidden" name="captcha_sid" class="captcha_sid" value="<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE']);?>" />
                                    <div class="captcha_reload"></div>
                                    <span class="refresh"><a href="javascript:;" rel="nofollow"><?=GetMessage('REFRESH');?></a></span>
                                </div>
                                <div class="captcha_input">
                                    <input type="text" class="inputtext form-control captcha" name="captcha_word" size="30" maxlength="50" value="" required />
                                </div>
                            </div>
                        <?endif;?>

                        <?if (TSolution::GetFrontParametrValue('SHOW_LICENCE') == "Y"):?>
                            <?TSolution\Functions::showBlockHtml([
                                'FILE' => 'consent/userconsent.php',
                                'PARAMS' => [
                                    'OPTION_CODE' => "AGREEMENT_REGISTRATION",
                                    'SUBMIT_TEXT' => GetMessage("REGISTER_REGISTER"),
                                    'REPLACE_FIELDS' => [],
                                    'INPUT_NAME' => TSolution\Validation::LICENSE_INPUT_NAME,
                                    'INPUT_ID' => "licenses_register",
                                ]
                            ]);?>
                        <?endif;?>
                    </div>

                    <div class="form-footer mt mt--32">
                        <button class="btn btn-default btn-lg btn-wide" type="submit" name="register_submit_button1" value="<?=GetMessage('AUTH_REGISTER');?>">
                            <?=GetMessage('REGISTER_REGISTER');?>
                        </button>
                        <div class="clearboth"></div>

                        <div class="social_block mt mt--40">
                            <?$APPLICATION->IncludeComponent(
                                'bitrix:system.auth.form',
                                'popup',
                                [
                                    'TITLE' => '',
                                    'PROFILE_URL' => SITE_DIR.'auth/',
                                    'SHOW_ERRORS' => 'Y',
                                    'POPUP_AUTH' => 'Y',
                                ]
                            );?>
                        </div>
                    </div>
                </form>

                <script>
                    if (
                        typeof appAspro === 'object'
                        && appAspro
                        && appAspro.phone
                    ) {
                        appAspro.phone.init($('#registraion-page-form input.phone'));
                    }

                    BX.Aspro.Utils.readyDOM(() => {
                        (function dnkRegisterLoginFromPhone() {
                            const $phone = $('#input_PERSONAL_PHONE');
                            const $login = $('#input_LOGIN');
                            if (!$phone.length || !$login.length || $login.attr('type') !== 'hidden') {
                                return;
                            }
                            const sync = () => {
                                const digits = String($phone.val() || '').replace(/\D/g, '');
                                $login.val(digits);
                            };
                            $phone.on('input change blur paste', function() {
                                setTimeout(sync, 0);
                            });
                            $('#registraion-page-form').on('submit', sync);
                            sync();
                        })();

                        <?if ($bPhoneAuthSupported && $bPhoneAuthShow):?>
                            $('#registraion-page-form').submit(function() {
                                $(this).find('[name=PHONE_NUMBER]').remove();
                                const $phone = $('#input_PERSONAL_PHONE');
                                if ($phone.length) {
                                    const phone = $phone.val();
                                    if (phone.length) {
                                        $(this).append('<input type="hidden" name="REGISTER[PHONE_NUMBER]" value="' + phone + '" />');
                                    }
                                }
                            });
                        <?endif;?>

                        $('form#registraion-page-form').validate({
                            submitHandler: function(form) {
                                if ($(form).valid()) {
                                    var $button = $(form).find('button[type=submit]');
                                    if ($button.length) {
                                        if (!$button.hasClass('loadings')) {
                                            $button.addClass('loadings');

                                            var eventdata = {
                                                form,
                                                type: 'form_submit',
                                                form_name: 'REGISTER'
                                            };
                                            BX.onCustomEvent('onSubmitForm', [eventdata]);
                                        }
                                    }
                                }
                            },
                            messages: {
                                'captcha_word': {
                                    remote: '<?=GetMessage("VALIDATOR_CAPTCHA")?>'
                                },
                                <?=TSolution\Validation::LICENSE_INPUT_NAME;?>: {
                                    required: BX.message('JS_REQUIRED_LICENSES')
                                }
                            },
                        });

                        $('#input_LOGIN').rules("add", {
                            required: true,
                            minlength: 3,
                            messages: {
                                minlength: jQuery.validator.format(BX.message('JS_LOGIN_LENGTH'))
                            }
                        });

                        $("form[name=bx_auth_servicesform_inline]").validate();

                        setTimeout(function() {
                            $('#registraion-page-form').find('input:visible').eq(0).focus();
                        }, 50);
                    });
                </script>
            <?endif;?>
        </div>
    <?endif;?>
</div>

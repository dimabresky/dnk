<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$this->setFrameMode(false);

$arExtensions = ['profile', 'validate', 'phone_input', 'phone_mask'];

if (TSolution::GetFrontParametrValue('USE_INTL_PHONE') === 'Y') {
    $arExtensions[] = 'intl_phone_input';
}

TSolution\Extensions::init($arExtensions);

TSolution\PhoneAuth::modifyResult($arResult, $arParams);
if (($_REQUEST['USER_REMEMBER'] ?? '') === 'Y') {
    $arResult['USER_REMEMBER'] = 'Y';
}

echo CJSCore::Init('phone_auth', true);
TSolution\Extensions::init('phonecode');

$rand = '_'.rand(1, 99).($arParams['POPUP_AUTH'] === 'Y' ? 'popup' : '');
?>
<?/* <link rel="stylesheet" type="text/css" href="/bitrix/js/socialservices/css/ss.css"> */?>
<?if($arResult['FORM_TYPE'] === 'login'):?>
    <div id="ajax_auth<?=$rand; ?>" class="auth-page pk-page">
        <div class="auth form-block">
                <div class="form <?= $arParams['POPUP_AUTH'] === 'Y' ? 'popup' : ''; ?> <?= $arResult['SHOW_SMS_FIELD'] ? 'form--send-sms' : ''; ?>">
                    <?if(
                        !$arResult['ERROR']
                        && $arResult['SHOW_SMS_FIELD']):
                        ?>
                        <div class="form-header">
                            <div class="text">
                                <div class="title switcher-title font_24 color_222"><?=GetMessage('AUTH_SMS_SENDED_TITLE'); ?></div>
                                <div class="form_desc font_16"><?=GetMessage('auth_code_sent', ['#PHONE_NUMBER#' => $arResult['USER_PHONE_NUMBER']]); ?></div>
                            </div>
                        </div>
                    <?elseif ($arParams['POPUP_AUTH'] === 'Y'):?>
                        <div class="form-header">
                            <div class="text">
                                <div class="title switcher-title font_24 color_222"><?=GetMessage('AUTHORIZE_TITLE'); ?></div>
                            </div>
                        </div>
                    <?endif; ?>

                    <form id="auth-page-form<?=$rand; ?>" name="system_auth_form<?=$arResult['RND']; ?>" method="post" target="_top" action="<?=$arParams['AUTH_URL']; ?>?login=yes">
                        <?if($arResult['BACKURL'] != ''):?>
                            <input type="hidden" name="backurl" value="<?=htmlspecialcharsbx($arResult['BACKURL']); ?>" />
                        <?endif; ?>

                        <?/* foreach ($arResult["POST"] as $key => $value):?><input type="hidden" name="<?=$key?>" value="<?=$value?>" /><?endforeach */?>
                        <input type="hidden" name="AUTH_FORM" value="Y" />
                        <input type="hidden" name="TYPE" value="AUTH" />
                        <input type="hidden" name="POPUP_AUTH" value="<?= $arParams['POPUP_AUTH'] === 'Y' ? 'Y' : 'N'; ?>" />

                        <input type="hidden" name="SITE_ID" value="<?=SITE_ID;?>" />
                        <input type="hidden" name="SOLUTION_ID" value="<?=PREMIER_MODULE_ID;?>" />

                        <div class="form-body">
                            <?if($arResult['ERROR']):?>
                                <div class="alert alert-danger">
                                    <?if($arResult['ERROR_MESSAGE']['MESSAGE']):?>
                                        <?=$arResult['ERROR_MESSAGE']['MESSAGE']; ?>
                                    <?else:?>
                                        <?=GetMessage('AUTH_ERROR'); ?>
                                    <?endif; ?>
                                </div>
                            <?endif; ?>

                            <?if($arResult['SHOW_SMS_FIELD']):?>
                                <input type="hidden" name="USER_PHONE_NUMBER" value="<?=htmlspecialcharsbx($arResult['USER_PHONE_NUMBER']); ?>" />
                                <input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult['SIGNED_DATA']); ?>" />
                                <?if(($arResult['STORE_PASSWORD'] ?? '') === 'Y' && ($arResult['USER_REMEMBER'] ?? '') === 'Y'):?>
                                    <input type="hidden" name="USER_REMEMBER" value="Y" />
                                <?endif; ?>

                                <div class="form-group fill-animate phone_code">
                                    <label for="SMS_CODE_POPUP<?=$rand; ?>"><span><?=GetMessage('auth_sms_code'); ?>&nbsp;<span class="required-star">*</span></span></label>
                                    <div class="input">
                                        <input type="text" name="SMS_CODE" id="SMS_CODE_POPUP<?=$rand; ?>" class="form-control" maxlength="50" value="<?=htmlspecialcharsbx($arResult['SMS_CODE']); ?>" autocomplete="off" tabindex="1" required />
                                        <label class="error" for="SMS_CODE_POPUP<?=$rand; ?>" style="display:none;"></label>
                                    </div>
                                </div>
                            <?else:?>
                                <div class="form-group fill-animate phone">
                                    <label for="USER_PHONE_NUMBER<?=$rand; ?>" class="font_13"><span><?=GetMessage('auth_phone_number'); ?>&nbsp;<span class="star">*</span></span></label>
                                    <div class="input">
                                        <input id="USER_PHONE_NUMBER<?=$rand; ?>" class="form-control required phone" type="tel" name="USER_PHONE_NUMBER" maxlength="50" autocomplete="tel" value="<?=isset($arResult['USER_PHONE_NUMBER']) ? htmlspecialcharsbx($arResult['USER_PHONE_NUMBER']) : ''; ?>" tabindex="1" />
                                    </div>
                                </div>
                                <?if(($arResult['STORE_PASSWORD'] ?? '') === 'Y'):?>
                                    <div class="form-group auth-remember font_13">
                                        <label for="USER_REMEMBER<?=$rand; ?>">
                                            <input type="checkbox" name="USER_REMEMBER" id="USER_REMEMBER<?=$rand; ?>" value="Y" tabindex="2"<?= ($arResult['USER_REMEMBER'] ?? '') === 'Y' ? ' checked="checked"' : ''; ?> />
                                            <?=GetMessage('AUTH_REMEMBER_SHORT'); ?>
                                        </label>
                                    </div>
                                <?endif; ?>
                            <?endif; ?>

                            <?if($arResult['CAPTCHA_CODE']):?>
                                <?php
                                    /**
                                     * @var TSolution\Captcha\Service $captcha
                                     */
                                    $captcha = TSolution\Captcha::getInstance();
                                ?>
                                <div class="clearboth"></div>
                                <div class="captcha-row clearfix">
                                    <label for="FORGOTPASSWD_CAPTCHA<?=$rand; ?>" class="font_13"><span><?= $captcha->isService() && $captcha->isActive() ? GetMessage('FORM_GENERAL_RECAPTCHA') : GetMessage('CAPTCHA_PROMT'); ?>&nbsp;<span class="required-star">*</span></span></label>
                                    <div class="captcha_image">
                                        <img src="/bitrix/tools/captcha.php?captcha_sid=<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE']); ?>" class="captcha_img" border="0" />
                                        <input type="hidden" name="captcha_sid" class="captcha_sid" value="<?=htmlspecialcharsbx($arResult['CAPTCHA_CODE']); ?>" />
                                        <div class="captcha_reload"></div>
                                        <span class="refresh"><a href="javascript:;" rel="nofollow"><?=GetMessage('REFRESH'); ?></a></span>
                                    </div>
                                    <div class="captcha_input">
                                        <input id="FORGOTPASSWD_CAPTCHA<?=$rand; ?>" type="text" class="inputtext form-control captcha" name="captcha_word" size="30" maxlength="50" value="" required />
                                    </div>
                                </div>
                                <div class="clearboth"></div>
                            <?endif; ?>
                        </div>

                        <div class="form-footer auth__bottom <?= $arResult['SHOW_SMS_FIELD'] ? 'hidden' : ''; ?>">
                            <div class="auth__bottom-btns">
                                <div class="line-block line-block--align-normal line-block--16-vertical flexbox--direction-column flexbox--justify-between">
                                    <div class="line-block__item">
                                        <?if($arResult['SHOW_SMS_FIELD']):?>
                                            <button class="btn btn-default btn-lg btn-wide" type="submit" name="Login1" value="Y" tabindex="2"><span><?=GetMessage('AUTH_LOGIN_BUTTON'); ?></span></button>
                                        <?else:?>
                                            <button class="btn btn-default btn-lg btn-wide" type="submit" name="Login1" value="Y" tabindex="3"><span><?=GetMessage('auth_get_sms_code'); ?></span></button>
                                        <?endif; ?>
                                    </div>

                                    <?if(!$arResult['SHOW_SMS_FIELD']):?>
                                        <div class="line-block__item">
                                            <!--noindex--><a href="<?=$arResult['AUTH_REGISTER_URL']; ?>" rel="nofollow" class="btn btn-default btn-transparent btn-lg btn-wide auth__bottom-btn register" tabindex="6"><?=GetMessage('AUTH_REGISTER_NEW'); ?></a><!--/noindex-->
                                        </div>
                                    <?endif; ?>
                                </div>
                                <input type="hidden" name="Login" value="Y" />
                                <div class="clearboth"></div>
                            </div>

                            <?if(
                                $arResult['AUTH_SERVICES']
                                && !$arResult['SHOW_SMS_FIELD']
                            ):?>
                                <div class="social_block mt mt--40">
                                    <div class="auth__services">
                                        <?php
                                        $APPLICATION->IncludeComponent(
                                            'bitrix:socserv.auth.form',
                                            'auth',
                                            [
                                                'AUTH_SERVICES' => $arResult['AUTH_SERVICES'],
                                                'AUTH_URL' => SITE_DIR.'auth/?login=yes',
                                                'POST' => $arResult['POST'],
                                                'SUFFIX' => 'form',
                                            ],
                                            $component,
                                            ['HIDE_ICONS' => 'Y']
                                        );
                                ?>
                                    </div>
                                </div>
                            <?endif; ?>

                            <?if(!$arResult['SHOW_SMS_FIELD']):?>
                                <div class="licence_block"><label><?$APPLICATION->IncludeFile(SITE_DIR.'include/auth_phone_licenses_text.php', [], ['MODE' => 'html', 'NAME' => 'LICENSES']); ?></label></div>
                            <?endif; ?>
                        </div>
                    </form>
                    <?if($arResult['SHOW_SMS_FIELD']):?>
                        <div class="form-footer">
                            <div id="bx_auth_error<?=$rand; ?>" style="display:none;"></div>
                            <div id="bx_auth_resend<?=$rand; ?>"></div>
                            <script>
                            $(document).ready(function(){
                                var $smsCodeError = $('#auth-page-form<?=$rand; ?> .phone_code label.error');

                                $('#auth-page-form<?=$rand; ?> .phone_code input').on('input', function() {
                                    $smsCodeError.hide().text('');
                                });

                                $('#auth-page-form<?=$rand; ?> .phone_code input[type=text]').phonecode(
                                    <?=CUtil::PhpToJSObject(
                                        [
                                            'AUTH' => 'Y',
                                            'USER_PHONE_NUMBER' => $arResult['USER_PHONE_NUMBER'],
                                        ]
                                    ); ?>,
                                    function(input, data, response) {
                                        if (
                                            typeof response !== 'undefined' &&
                                            response === 'true'
                                        ) {
                                            $smsCodeError.hide().text('');

                                            let $form = $(input).closest('form');

                                            if (
                                                $form.length &&
                                                !$form.find('button[type=submit].loadings').length
                                            ) {
                                                $form.find('button[type=submit]').closest('.form-footer').removeClass('hidden');
                                                $form.find('button[type=submit]').closest('.form-footer').addClass('hide_on_submit');
                                                $form.find('button[type=submit]').eq(0).trigger('click');
                                            }
                                        } else if (response === 'false' && String($(input).val() || '').length >= 6) {
                                            $smsCodeError.text('<?=CUtil::JSEscape(GetMessage('AUTH_SMS_CODE_ERROR')); ?>').show();
                                        }
                                    }
                                );
                            });

                            new BX.PhoneAuth({
                                containerId: 'bx_auth_resend<?=$rand; ?>',
                                errorContainerId: 'bx_auth_error<?=$rand; ?>',
                                interval: <?=$arResult['PHONE_CODE_RESEND_INTERVAL']; ?>,
                                data:
                                    <?=CUtil::PhpToJSObject([
                                        'signedData' => $arResult['SIGNED_DATA'],
                                    ]); ?>,
                                onError:
                                    function(response)
                                    {
                                        var $smsCodeError = $('#auth-page-form<?=$rand; ?> .phone_code label.error');
                                        var errorMessage = '';

                                        for(var i = 0; i < response.errors.length; i++)
                                        {
                                            errorMessage += BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
                                        }

                                        $smsCodeError.html(errorMessage).show();
                                    }
                            });
                            </script>
                        </div>
                    <?endif; ?>


                </div>

                <script>
                $(document).ready(function(){
                    $('form[name=bx_auth_servicesform]').validate();
                    $('.auth_wrapp .form_body a').removeAttr('onclick');

                    BX.Aspro.Utils.readyDOM(() => {
                        if (
                            typeof appAspro === 'object'
                            && appAspro
                            && appAspro.phone
                        ) {
                            appAspro.phone.init($('#auth-page-form<?=$rand; ?> input.phone'));
                        }
                    });

                    $('#auth-page-form<?=$rand; ?>').validate({
                        rules: {
                            USER_PHONE_NUMBER: {
                                required: true
                            },
                            SMS_CODE: {
                                required: true
                            }
                        },
                        submitHandler: function(form){
                            var $form = $(form);
                            if($form.valid()){
                                (new Promise((resolve, reject) => {
                                    if (BX.Aspro?.Captcha) {
                                        BX.Aspro.Captcha.onSubmit({form}).then((result) => {
                                            resolve(result);
                                        }).catch((e) => {
                                            reject(e);
                                        });

                                        return;
                                    }

                                    resolve(true);
                                })).then((result) => {
                                    if (result) {
                                        var $button = $form.find('button[type=submit]:visible');
                                        if($button.length){
                                            $button.closest('.hide_on_submit').removeClass('hide_on_submit').addClass('hidden');

                                            if(!$button.hasClass('loadings')){
                                                $button.addClass('loadings');
                                                $form.closest('.form').addClass('sending');

                                                $.ajax({
                                                    type: 'POST',
                                                    url: $form.attr('action'),
                                                    data: $form.serializeArray()
                                                }).done(function(html){
                                                    if ($(html).find('.form--send-sms').length) {
                                                        $('#auth-page-form<?=$rand; ?>').closest('.form.popup').find('> .form-header').hide();
                                                    }

                                                    if(
                                                        $(html).find('.auth').length ||
                                                        $(html).find('.form--send-sms').length
                                                    ){
                                                        $('#ajax_auth<?=$rand; ?>').parent().html(html);
                                                    }
                                                    else{
                                                        const match = html.match(/location\.href\s*=\s*['"]([^'"]*)['"]/);

                                                        if(match){
                                                            location.href = match[1]
                                                        }else{
                                                            BX.reload(false);
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    }
                                });
                            }
                        },
                        errorPlacement: function(error, element){
                            $(error).attr('alt', $(error).text());
                            $(error).attr('title', $(error).text());
                            error.insertAfter(element);
                        },
                    });
                });

                setTimeout(function(){
                    $('#auth-page-form<?=$rand; ?>').find('input:visible').eq(0).focus();
                }, 50);

                <?// skip Bx.ajax.insertToNode when BX ajax mode is on?>
                BX.addCustomEvent('onAjaxInsertToNode', function(e) {
                    e.eventArgs.cancel = true;
                    location.href = BX.util.remove_url_param(e.url, ['bxajaxid']);
                });
                </script>
            </div>
        </div>
<?else:?>
    <script>
    BX.reload(true);
    </script>
<?endif; ?>

<?// need pageobject.js for BX.reload()?>
<script>
BX.loadScript(['<?=Bitrix\Main\Page\Asset::getInstance()->getFullAssetPath('/bitrix/js/main/pageobject/pageobject.js'); ?>']);
</script>

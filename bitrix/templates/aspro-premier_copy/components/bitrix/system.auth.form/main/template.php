<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$this->setFrameMode(false);

TSolution\Extensions::init(['profile', 'eye.password']);

TSolution\PhoneAuth::modifyResult($arResult, $arParams);

if($arResult['PHONE_AUTH_PARAMS']['USE']) {
    echo CJSCore::Init('phone_auth', true);
    TSolution\Extensions::init('phonecode');
    TSolution\Extensions::init('phoneorlogin');
}

$rand = '_'.rand(1, 99).($arParams['POPUP_AUTH'] === 'Y' ? 'popup' : '');
?>
<?/* <link rel="stylesheet" type="text/css" href="/bitrix/js/socialservices/css/ss.css"> */?>
<?if($arResult['FORM_TYPE'] === 'login'):?>
    <?if(
        $arResult['ERROR']
        && $arResult['ERROR_MESSAGE']['TYPE'] === 'ERROR'
        && $arResult['ERROR_MESSAGE']['ERROR_TYPE'] === 'CHANGE_PASSWORD'
        && $arParams['CHANGE_PASSWORD_URL']
    ):?>
        <?php
            $_SESSION['arAuthResult'] = $APPLICATION->arAuthResult;
        $_SESSION['lastLoginSave'] = $arResult['USER_LOGIN'];
        ?>
        <script>
        location.href = <?var_export($arParams['CHANGE_PASSWORD_URL'].(strlen($arResult['BACKURL']) ? (strpos($arParams['CHANGE_PASSWORD_URL'], '?') ? '&' : '?').'backurl='.$arResult['BACKURL'] : '')); ?>;
        </script>
    <?else:?>
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

                            <?if($arResult['PHONE_AUTH_PARAMS']['USE']):?>
                                <?if($arResult['SHOW_SMS_FIELD']):?>
                                    <input type="hidden" name="USER_PHONE_NUMBER" value="<?=htmlspecialcharsbx($arResult['USER_PHONE_NUMBER']); ?>" />
                                    <input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult['SIGNED_DATA']); ?>" />

                                    <div class="form-group fill-animate phone_code">
                                        <label for="SMS_CODE_POPUP<?=$rand; ?>"><span><?=GetMessage('auth_sms_code'); ?>&nbsp;<span class="required-star">*</span></span></label>
                                        <div class="input">
                                            <input type="text" name="SMS_CODE" id="SMS_CODE_POPUP<?=$rand; ?>" class="form-control" maxlength="50" value="<?=htmlspecialcharsbx($arResult['SMS_CODE']); ?>" autocomplete="off" tabindex="1" required />
                                        </div>
                                    </div>
                                <?else:?>
                                    <div class="form-group fill-animate phone_or_login">
                                        <label for="AUTH_PHONE_OR_LOGIN<?=$rand; ?>" class="font_13"><span><?=GetMessage('auth_phone_number_or_login'); ?>&nbsp;<span class="star">*</span></span></label>
                                        <label for="AUTH_PHONE_OR_LOGIN<?=$rand; ?>" class="font_13"><span><?=GetMessage('AUTH_LOGIN'); ?>&nbsp;<span class="star">*</span></span></label>
                                        <label for="AUTH_PHONE_OR_LOGIN<?=$rand; ?>" class="font_13"><span><?=GetMessage('auth_phone_number'); ?>&nbsp;<span class="star">*</span></span></label>
                                        <div class="input">
                                            <input id="AUTH_PHONE_OR_LOGIN<?=$rand; ?>" class="form-control required" type="text" name="AUTH_PHONE_OR_LOGIN" maxlength="50" autocomplete="off" value="" tabindex="1" />
                                            <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/phoneorlogin.svg#login-26-26', 'colored_theme_svg', ['WIDTH' => 26, 'HEIGHT' => 26]); ?>
                                            <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/phoneorlogin.svg#phone-26-26', 'colored_theme_svg', ['WIDTH' => 26, 'HEIGHT' => 26]); ?>
                                        </div>
                                    </div>
                                    <div class="form-group fill-animate">
                                        <label for="USER_PASSWORD_POPUP<?=$rand; ?>" class="font_13"><span><?=GetMessage('auth_password2'); ?>&nbsp;<span class="required-star">*</span></span></label>
                                        <div class="input">
                                            <input type="password" name="USER_PASSWORD" id="USER_PASSWORD_POPUP<?=$rand; ?>" class="form-control required password" maxlength="50" value="" autocomplete="off" tabindex="2"/>
                                        </div>
                                    </div>
                                <?endif; ?>
                            <?else:?>
                                <div class="form-group fill-animate" data-sid="USER_LOGIN_POPUP">
                                    <label class="font_13" for="USER_LOGIN_POPUP<?=$rand; ?>"><span><?=GetMessage('AUTH_LOGIN'); ?> <span class="required-star">*</span></span></label>
                                    <div class="input">
                                        <input type="text" name="USER_LOGIN" id="USER_LOGIN_POPUP<?=$rand; ?>" class="form-control required" maxlength="50" value="<?=$arResult['USER_LOGIN']; ?>" autocomplete="on" tabindex="1"/>
                                    </div>
                                </div>

                                <div class="form-group fill-animate" data-sid="USER_PASSWORD_POPUP">
                                    <label class="font_13" for="USER_PASSWORD_POPUP<?=$rand; ?>"><span><?=GetMessage('AUTH_PASSWORD'); ?> <span class="required-star">*</span></span></label>
                                    <div class="input">
                                        <input type="password" name="USER_PASSWORD" id="USER_PASSWORD_POPUP<?=$rand; ?>" class="form-control required" maxlength="50" value="" autocomplete="on" tabindex="2"/>
                                    </div>
                                </div>
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
                            <div class="auth__bottom-action">
                                <div class="line-block line-block--20 flexbox--wrap flexbox--justify-between">
                                    <div class="line-block__item">
                                        <div class="prompt remember pull-left form-checkbox">
                                            <input type="checkbox" class="form-checkbox__input" id="USER_REMEMBER_frm<?=$rand; ?>" name="USER_REMEMBER" value="Y" tabindex="5"/>
                                            <label for="USER_REMEMBER_frm<?=$rand; ?>" tabindex="5" class="form-checkbox__label form-checkbox__label--sm">
                                                <span><?= GetMessage('AUTH_REMEMBER_SHORT'); ?></span>
                                                <span class="form-checkbox__box"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <?if(!$arResult['SHOW_SMS_FIELD']):?>
                                        <div class="line-block__item font_13">
                                            <a class="forgot" href="<?=$arResult['AUTH_FORGOT_PASSWORD_URL']; ?>" tabindex="3"><?=GetMessage('AUTH_FORGOT_PASSWORD_2'); ?></a>
                                        </div>
                                    <?endif; ?>
                                </div>
                            </div>

                            <div class="auth__bottom-btns">
                                <div class="line-block line-block--align-normal line-block--16-vertical flexbox--direction-column flexbox--justify-between">
                                    <?if($arResult['PHONE_AUTH_PARAMS']['USE']):?>
                                        <div class="line-block__item">
                                            <?if($arResult['SHOW_SMS_FIELD']):?>
                                                <button class="btn btn-default btn-lg btn-wide" type="submit" name="Login1" value="Y" tabindex="2"><span><?=GetMessage('AUTH_LOGIN_BUTTON'); ?></span></button>
                                            <?else:?>
                                                <button class="btn btn-default btn-lg btn-wide" type="submit" name="Login1" value="Y" tabindex="2"><span><?=GetMessage('auth_password_continue'); ?></span></button>
                                                <button class="btn btn-default btn-lg btn-wide hidden" type="submit" name="Login1" value="Y" tabindex="2"><span><?=GetMessage('AUTH_LOGIN_BUTTON'); ?></span></button>
                                                <button class="btn btn-default btn-lg btn-wide hidden" type="submit" name="Login1" value="Y" tabindex="2"><span><?=GetMessage('auth_get_sms_code'); ?></span></button>
                                        </div>
                                        <div class="line-block__item">
                                                <!--noindex--><a href="<?=$arResult['AUTH_REGISTER_URL']; ?>" rel="nofollow" class="btn btn-default btn-transparent btn-lg btn-wide auth__bottom-btn register" tabindex="6"><?=GetMessage('AUTH_REGISTER_NEW'); ?></a><!--/noindex-->
                                            <?endif; ?>
                                        </div>
                                    <?else:?>
                                        <div class="line-block__item">
                                            <button type="submit" class="btn btn-default btn-lg btn-wide auth__bottom-btn" name="Login1" value="" tabindex="4">
                                                <span><?=GetMessage('AUTH_LOGIN_BUTTON'); ?></span>
                                            </button>
                                        </div>

                                        <?if(Bitrix\Main\Config\Option::get('main', 'new_user_registration', 'N', null) == 'Y'):?>
                                            <div class="line-block__item center-block font_15 color_222">
                                                <span><?=GetMessage('AUTH_REGISTER_ASK_ACCOUNT'); ?></span>
                                            <!--noindex-->
                                                <a href="<?=$arResult['AUTH_REGISTER_URL']; ?>" rel="nofollow" class="auth__bottom-btn pl pl--4" tabindex="6">
                                                    <?=GetMessage('AUTH_REGISTER_NEW'); ?>
                                                </a>
                                            <!--/noindex-->
                                            </div>
                                        <?endif; ?>
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

                            <?if(
                                $arResult['PHONE_AUTH_PARAMS']['USE']
                                && !$arResult['SHOW_SMS_FIELD']
                            ):?>
                                <div class="licence_block hidden"><label><?$APPLICATION->IncludeFile(SITE_DIR.'include/auth_phone_licenses_text.php', [], ['MODE' => 'html', 'NAME' => 'LICENSES']); ?></label></div>
                            <?endif; ?>
                        </div>
                    </form>
                    <?if($arResult['SHOW_SMS_FIELD']):?>
                        <div class="form-footer">
                            <div id="bx_auth_error<?=$rand; ?>" style="display:none;"><?ShowError('error'); ?></div>
                            <div id="bx_auth_resend<?=$rand; ?>"></div>
                            <script>
                            $(document).ready(function(){
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
                                            let $form = $(input).closest('form');

                                            if (
                                                $form.length &&
                                                !$form.find('button[type=submit].loadings').length
                                            ) {
                                                $form.find('button[type=submit]').closest('.form-footer').removeClass('hidden');
                                                $form.find('button[type=submit]').closest('.form-footer').addClass('hide_on_submit');
                                                $form.find('button[type=submit]').eq(0).trigger('click');
                                            }
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
                                        var errorDiv = BX('bx_auth_error<?=$rand; ?>');
                                        var errorNode = BX.findChildByClassName(errorDiv, 'errortext');
                                        errorNode.innerHTML = '';
                                        for(var i = 0; i < response.errors.length; i++)
                                        {
                                            errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
                                        }
                                        errorDiv.style.display = '';
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

                    $('#auth-page-form<?=$rand; ?>').validate({
                        rules: {
                            USER_LOGIN: {
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
                                        if(
                                            $form.find('input[name=AUTH_PHONE_OR_LOGIN]').length &&
                                            $form.find('input[name=USER_LOGIN]').length &&
                                            $form.find('input[name=USER_PASSWORD]').length &&
                                            !$form.find('input[name=USER_PASSWORD]').val().length
                                        ){
                                            $form.find('input[name=AUTH_PHONE_OR_LOGIN]').closest('.form-group').hide();
                                            $form.find('input[name=USER_PASSWORD]').closest('.form-group').fadeIn();
                                            $form.find('input[name=USER_PASSWORD]').focus();
                                            $form.find('.form-footer .auth__bottom-btns button[type="submit"]').addClass('hidden').eq(1).removeClass('hidden');
                                        }
                                        else{
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

                                                            // show password eye
                                                            $('#ajax_auth<?=$rand; ?>').find(".form-group:not(.eye-password-ignore) [type=password]").each(function (item) {
                                                                let inputBlock = $(item).closest(".input");
                                                                if (inputBlock.length) {
                                                                    inputBlock.addClass("eye-password");
                                                                } else {
                                                                    let passBlock = $(item).closest(".form-group");
                                                                    let labelBlock = passBlock.find(".label_block");
                                                                    if (labelBlock.length) {
                                                                        labelBlock.addClass("eye-password");
                                                                    } else {
                                                                        passBlock.addClass("eye-password");
                                                                    }
                                                                }
                                                            });
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

                <?if($arResult['PHONE_AUTH_PARAMS']['USE']):?>
                $('#auth-page-form<?=$rand; ?> .phone_or_login input').phoneOrLogin(function(input, test){
                    var $form = $(input).closest('form');

                    if(test.bPossiblePhone){
                        if(!$form.find('input[name=USER_PHONE_NUMBER]').length){
                            $form.find('input[name=USER_LOGIN]').remove();
                            $form.find('input[name=USER_PASSWORD]').val('');
                            $form.find('input[name=USER_PASSWORD]').prop('disabled', true);
                            $form.find('.licence_block').eq(0).removeClass('hidden');
                            $form.find('.licence_block').eq(1).addClass('hidden');
                            $form.find('.forgot').addClass('hidden');
                            $form.prepend('<input type="hidden" name="USER_PHONE_NUMBER" />');
                            $form.find('.form-footer .auth__bottom-btns button[type="submit"]').addClass('hidden').eq(2).removeClass('hidden');
                        }
                        $form.find('input[name=USER_PHONE_NUMBER]').val(test.value);
                    }
                    else{
                        if(!$form.find('input[name=USER_LOGIN]').length){
                            $form.find('input[name=USER_PHONE_NUMBER]').remove();
                            $form.find('input[name=USER_PASSWORD]').prop('disabled', false);
                            $form.find('.licence_block').eq(0).addClass('hidden');
                            $form.find('.licence_block').eq(1).removeClass('hidden');
                            $form.find('.forgot').removeClass('hidden');
                            $form.prepend('<input type="hidden" name="USER_LOGIN" />');
                            $form.find('.form-footer .auth__bottom-btns button[type="submit"]').addClass('hidden').eq(0).removeClass('hidden');
                        }
                        $form.find('input[name=USER_LOGIN]').val(test.value);
                    }
                });
                <?endif; ?>
                </script>
            </div>
        </div>
    <?endif; ?>
<?else:?>
    <script>
    BX.reload(true);
    </script>
<?endif; ?>

<?// need pageobject.js for BX.reload()?>
<script>
BX.loadScript(['<?=Bitrix\Main\Page\Asset::getInstance()->getFullAssetPath('/bitrix/js/main/pageobject/pageobject.js'); ?>']);
</script>

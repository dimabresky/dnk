<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

/** @var array $arResult */
$this->setFrameMode(false);

$this->addExternalJs(SITE_DIR . 'local/js/vendor/vue.global.prod.js');
$this->addExternalJs(SITE_DIR . 'local/js/imask.js');
CJSCore::Init(['phone_auth']);

$profile = is_array($arResult['PROFILE'] ?? null) ? $arResult['PROFILE'] : ['name' => '', 'phone' => '', 'isAuthorized' => false];
$nameVal = htmlspecialcharsbx((string)$profile['name']);
$phoneVal = htmlspecialcharsbx((string)$profile['phone']);
$isAuthorized = !empty($profile['isAuthorized']);
$phoneAuthEnabled = !empty($arResult['PHONE_AUTH_ENABLED']);
$orderConsentId = (int)($arResult['USER_CONSENT_ID'] ?? 0);
$registrationConsentOption = (string)($arResult['REGISTRATION_CONSENT_OPTION'] ?? 'AGREEMENT_REGISTRATION');
$licenseInputName = class_exists(\TSolution\Validation::class) ? (string)\TSolution\Validation::LICENSE_INPUT_NAME : 'licenses_register';
$phoneResendInterval = max(1, (int)($arResult['PHONE_CODE_RESEND_INTERVAL'] ?? 60));

$catalogJson = '{}';
$uiJson = '{}';
$cartSessionJson = '{}';
$pickupStoresJson = '[]';
$yandexApiKey = '';

if (!empty($arResult['ITEMS'])) {
    $catalogForJs = [];
    foreach ($arResult['ITEMS'] as $item) {
        $catalogForJs[] = [
            'id' => (int)($item['id'] ?? 0),
            'NAME' => (string)($item['NAME'] ?? ''),
            'NOMINAL_FORMATTED' => (string)($item['NOMINAL_FORMATTED'] ?? ''),
            'NOMINAL' => (float)($item['NOMINAL'] ?? 0),
            'PICTURE' => (string)($item['PICTURE'] ?? ''),
        ];
    }
    $catalogJson = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($catalogForJs, JSON_UNESCAPED_UNICODE));
    $uiJson = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode([
        'buy' => GetMessage('DNK_CERT_BUY_BTN_BUY'),
        'summaryTitle' => GetMessage('DNK_CERT_BUY_SUMMARY_TITLE'),
        'summaryTotal' => GetMessage('DNK_CERT_BUY_SUMMARY_TOTAL'),
        'summaryDelivery' => GetMessage('DNK_CERT_BUY_SUMMARY_DELIVERY'),
        'summaryPayment' => GetMessage('DNK_CERT_BUY_SUMMARY_PAYMENT'),
        'summaryPickup' => GetMessage('DNK_CERT_BUY_SUMMARY_PICKUP'),
        'imgAltFallback' => GetMessage('DNK_CERT_BUY_IMG_ALT'),
        'qtyAria' => GetMessage('DNK_CERT_BUY_QTY'),
        'deliveryTitle' => GetMessage('DNK_CERT_BUY_DELIVERY_TITLE'),
        'deliveryCourier' => GetMessage('DNK_CERT_BUY_DELIVERY_COURIER'),
        'deliveryPickup' => GetMessage('DNK_CERT_BUY_DELIVERY_PICKUP'),
        'pickupTitle' => GetMessage('DNK_CERT_BUY_PICKUP_TITLE'),
        'pickupEmpty' => GetMessage('DNK_CERT_BUY_PICKUP_EMPTY'),
        'pickupMapUnavailable' => GetMessage('DNK_CERT_BUY_PICKUP_MAP_UNAVAILABLE'),
        'pickupRequired' => GetMessage('DNK_CERT_BUY_PICKUP_REQUIRED'),
        'payTitle' => GetMessage('DNK_CERT_BUY_PAY_TITLE'),
        'payCod' => GetMessage('DNK_CERT_BUY_PAY_COD'),
    ], JSON_UNESCAPED_UNICODE));

    $cartSnap = isset($arResult['CART_SESSION']) && is_array($arResult['CART_SESSION']) ? $arResult['CART_SESSION'] : [];
    $cartSessionJson = $cartSnap !== []
        ? htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($cartSnap, JSON_UNESCAPED_UNICODE))
        : '{}';

    $pickupStores = isset($arResult['PICKUP_STORES']) && is_array($arResult['PICKUP_STORES'])
        ? $arResult['PICKUP_STORES']
        : [];
    $pickupStoresJson = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($pickupStores, JSON_UNESCAPED_UNICODE));
    $yandexApiKey = htmlspecialcharsbx((string)($arResult['YANDEX_MAP_API_KEY'] ?? ''));
}
?>

<div id="dnk-cert-buy-root"
     class="dnk-cert-buy"
     data-msg-success="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_JS_SUCCESS')); ?>"
     data-msg-error="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_JS_ERROR')); ?>"
     data-is-authorized="<?= $isAuthorized ? '1' : '0'; ?>"
     data-phone-auth-enabled="<?= $phoneAuthEnabled ? '1' : '0'; ?>"
     data-msg-phone-auth-off="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_JS_PHONE_AUTH_OFF')); ?>"
     data-license-input-name="<?= htmlspecialcharsbx($licenseInputName); ?>"
     data-phone-resend-interval="<?= $phoneResendInterval; ?>">
    <?php if (empty($arResult['ITEMS'])) { ?>
        <div class="dnk-cert-buy__empty muted"><?= GetMessage('DNK_CERT_BUY_EMPTY'); ?></div>
    <?php } else { ?>

        <div
            id="dnk-cert-buy-app"
            class="dnk-cert-buy-app"
            data-catalog="<?= $catalogJson; ?>"
            data-ui="<?= $uiJson; ?>"
            data-cart-session="<?= $cartSessionJson; ?>"
            data-pickup-stores="<?= $pickupStoresJson; ?>"
            data-yandex-api-key="<?= $yandexApiKey; ?>"
            data-max-qty="99"></div>

        <div id="dnk-cert-buy-contact-anchor" class="dnk-cert-buy__checkout-contact">
            <h3 class="dnk-cert-buy__section-title font_20"><?= GetMessage('DNK_CERT_BUY_CONTACT_TITLE'); ?></h3>
            <div class="dnk-cert-buy__checkout-layout">
                <div class="dnk-cert-buy__checkout-form">
                    <div class="dnk-cert-buy__section dnk-cert-buy__section--form-only">
                        <div class="dnk-cert-buy__form-row">
                            <label class="dnk-cert-buy__field">
                                <span class="dnk-cert-buy__field-label font_13"><?= GetMessage('DNK_CERT_BUY_NAME'); ?> *</span>
                                <input class="dnk-cert-buy__input form-control" type="text" name="dnk_cert_contact_name" value="<?= $nameVal; ?>" maxlength="200" autocomplete="name" required>
                            </label>
                            <label class="dnk-cert-buy__field">
                                <span class="dnk-cert-buy__field-label font_13"><?= GetMessage('DNK_CERT_BUY_PHONE'); ?> *</span>
                                <input class="dnk-cert-buy__input js-dnk-cert-phone form-control" type="text" name="dnk_cert_contact_phone" value="<?= $phoneVal; ?>" maxlength="40" autocomplete="tel" inputmode="tel" required placeholder="+375 (__) ___-__-__">
                            </label>
                        </div>

                        <label class="dnk-cert-buy__field dnk-cert-buy__field--full">
                            <span class="dnk-cert-buy__field-label font_13"><?= GetMessage('DNK_CERT_BUY_COMMENT'); ?></span>
                            <textarea class="dnk-cert-buy__textarea form-control" name="dnk_cert_comment" rows="3" maxlength="2000" placeholder="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_COMMENT_HINT')); ?>"></textarea>
                        </label>

                        <?php if (!$isAuthorized && $phoneAuthEnabled) { ?>
                            <div class="dnk-cert-buy__auth-consents" data-role="auth-consents">
                                <?php if ($orderConsentId > 0) {
                                    $APPLICATION->IncludeComponent(
                                        'bitrix:main.userconsent.request',
                                        'main',
                                        [
                                            'ID' => $orderConsentId,
                                            'IS_CHECKED' => 'N',
                                            'IS_LOADED' => 'N',
                                            'AUTO_SAVE' => 'Y',
                                            'INPUT_NAME' => 'orderConsent',
                                            'INPUT_REQUIRED' => 'Y',
                                            'BLOCK_NAME' => 'dnk-cert-buy__consent-order',
                                        ]
                                    );
                                } ?>

                                <div class="dnk-cert-buy__registration-consent" data-role="registration-consent">
                                    <?php TSolution\Functions::showBlockHtml([
                                        'FILE' => 'consent/dnk/userconsent.php',
                                        'PARAMS' => [
                                            'OPTION_CODE' => $registrationConsentOption,
                                            'SUBMIT_TEXT' => GetMessage('DNK_CERT_BUY_SUBMIT'),
                                            'REPLACE_FIELDS' => [],
                                            'INPUT_NAME' => $licenseInputName,
                                            'INPUT_ID' => 'dnk-cert-buy-register-consent',
                                        ],
                                    ]); ?>
                                </div>
                            </div>

                            <div class="dnk-cert-buy__sms" data-role="sms-box" hidden>
                                <div class="dnk-cert-buy__sms-title font_14"><?= GetMessage('DNK_CERT_BUY_SMS_TITLE'); ?></div>
                                <p class="dnk-cert-buy__sms-caption" data-role="sms-caption"><?= GetMessage('DNK_CERT_BUY_SMS_SENT'); ?></p>
                                <div class="dnk-cert-buy__sms-row">
                                    <input class="dnk-cert-buy__input form-control" type="text" name="dnk_cert_sms_code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_SMS_CODE')); ?>">
                                    <button type="button" class="btn btn-default" data-role="sms-confirm"><?= GetMessage('DNK_CERT_BUY_SMS_CONFIRM'); ?></button>
                                </div>
                                <button type="button" class="btn btn-link" data-role="sms-resend" disabled><?= GetMessage('DNK_CERT_BUY_SMS_RESEND'); ?></button>
                            </div>
                        <?php } ?>

                        <div class="dnk-cert-buy__submit-feedback"
                             data-role="submit-feedback"
                             role="status"
                             aria-live="polite"
                             aria-atomic="true"
                             hidden></div>

                        <button type="button" class="btn btn-lg btn-primary dnk-cert-buy__submit" data-role="submit">
                            <?= GetMessage('DNK_CERT_BUY_SUBMIT'); ?>
                        </button>
                    </div>
                </div>
                <aside class="dnk-cert-buy__checkout-summary" aria-label="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_SUMMARY_TITLE')); ?>">
                    <div id="dnk-cert-buy-summary-slot" class="dnk-cert-buy__summary-mount"></div>
                </aside>
            </div>
        </div>

    <?php } ?>
</div>

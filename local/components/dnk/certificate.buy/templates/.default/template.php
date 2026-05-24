<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

/** @var array $arResult */
$this->setFrameMode(false);

$this->addExternalJs(SITE_DIR . 'local/js/vendor/vue.global.prod.js');
$this->addExternalJs(SITE_DIR . 'local/js/imask.js');

$profile = is_array($arResult['PROFILE'] ?? null) ? $arResult['PROFILE'] : ['name' => '', 'phone' => '', 'isAuthorized' => false];
$nameVal = htmlspecialcharsbx((string)$profile['name']);
$phoneVal = htmlspecialcharsbx((string)$profile['phone']);

$catalogJson = '{}';
$uiJson = '{}';
$cartSessionJson = '{}';
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
        'imgAltFallback' => GetMessage('DNK_CERT_BUY_IMG_ALT'),
        'qtyAria' => GetMessage('DNK_CERT_BUY_QTY'),
    ], JSON_UNESCAPED_UNICODE));

    $cartSnap = isset($arResult['CART_SESSION']) && is_array($arResult['CART_SESSION']) ? $arResult['CART_SESSION'] : [];
    $cartSessionJson = $cartSnap !== []
        ? htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($cartSnap, JSON_UNESCAPED_UNICODE))
        : '{}';
}
?>

<div id="dnk-cert-buy-root" class="dnk-cert-buy" data-msg-success="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_JS_SUCCESS')); ?>" data-msg-error="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_JS_ERROR')); ?>">
    <?php if (empty($arResult['ITEMS'])) { ?>
        <div class="dnk-cert-buy__empty muted"><?= GetMessage('DNK_CERT_BUY_EMPTY'); ?></div>
    <?php } else { ?>

        <div
            id="dnk-cert-buy-app"
            class="dnk-cert-buy-app"
            data-catalog="<?= $catalogJson; ?>"
            data-ui="<?= $uiJson; ?>"
            data-cart-session="<?= $cartSessionJson; ?>"
            data-max-qty="99"></div>

        <div class="dnk-cert-buy__section">
            <h3 class="dnk-cert-buy__section-title font_20"><?= GetMessage('DNK_CERT_BUY_DELIVERY_TITLE'); ?></h3>
            <label class="dnk-cert-buy__inline">
                <input type="radio" name="dnk_cert_delivery" value="courier" checked disabled>
                <span><?= GetMessage('DNK_CERT_BUY_DELIVERY_COURIER'); ?></span>
            </label>
        </div>

        <div class="dnk-cert-buy__section">
            <h3 class="dnk-cert-buy__section-title font_20"><?= GetMessage('DNK_CERT_BUY_PAY_TITLE'); ?></h3>
            <label class="dnk-cert-buy__inline">
                <input type="radio" name="dnk_cert_payment" value="cash_on_delivery" checked disabled>
                <span><?= GetMessage('DNK_CERT_BUY_PAY_COD'); ?></span>
            </label>
        </div>

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
                        <div
                            class="dnk-cert-buy__submit-feedback"
                            data-role="submit-feedback"
                            role="status"
                            aria-live="polite"
                            aria-atomic="true"
                            hidden></div>
                        <button type="button" class="btn btn-lg btn-primary dnk-cert-buy__submit" data-role="submit"><?= GetMessage('DNK_CERT_BUY_SUBMIT'); ?></button>
                    </div>
                </div>
                <aside class="dnk-cert-buy__checkout-summary" aria-label="<?= htmlspecialcharsbx(GetMessage('DNK_CERT_BUY_SUMMARY_TITLE')); ?>">
                    <div id="dnk-cert-buy-summary-slot" class="dnk-cert-buy__summary-mount"></div>
                </aside>
            </div>
        </div>

    <?php } ?>
</div>

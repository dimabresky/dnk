<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var CBitrixComponent $component */

if (empty($arResult['available'])) {
    return;
}

$this->setFrameMode(true);
$applied = (float)($arResult['applied'] ?? 0);
$maxPay = (float)($arResult['max_pay'] ?? 0);
$messages = [
    'generic' => Loc::getMessage('DNK_BASKET_BONUS_ERROR_GENERIC'),
    'notAuthorized' => Loc::getMessage('DNK_BASKET_BONUS_ERROR_NOT_AUTHORIZED'),
    'emptyBasket' => Loc::getMessage('DNK_BASKET_BONUS_ERROR_EMPTY_BASKET'),
    'notApplicable' => Loc::getMessage('DNK_BASKET_BONUS_ERROR_NOT_APPLICABLE'),
    'applyFailed' => Loc::getMessage('DNK_BASKET_BONUS_ERROR_APPLY_FAILED'),
    'saveFailed' => Loc::getMessage('DNK_BASKET_BONUS_ERROR_SAVE_FAILED'),
];
$labels = [
    'balance' => Loc::getMessage('DNK_BASKET_BONUS_BALANCE'),
    'max' => Loc::getMessage('DNK_BASKET_BONUS_MAX'),
    'applied' => Loc::getMessage('DNK_BASKET_BONUS_APPLIED'),
    'minError' => Loc::getMessage('DNK_BASKET_BONUS_MIN_ERROR'),
];
?>
<div
    class="basket-bonus-section dnk-basket-bonus-apply"
    id="dnk-basket-bonus-apply"
    data-messages="<?= htmlspecialcharsbx(json_encode($messages, JSON_UNESCAPED_UNICODE)); ?>"
    data-labels="<?= htmlspecialcharsbx(json_encode($labels, JSON_UNESCAPED_UNICODE)); ?>"
>
    <div class="basket-bonus-section__loader" aria-hidden="true" data-role="dnk-bonus-loader">
        <span class="basket-bonus-section__loader-spinner" aria-hidden="true"></span>
    </div>

    <div class="basket-bonus-section__title font_15 font_short"><?= Loc::getMessage('DNK_BASKET_BONUS_WRITE_OFF'); ?></div>

    <div data-role="dnk-bonus-content">
        <div class="basket-bonus-section__hint font_13 color_999" data-role="dnk-bonus-hint"<?= !empty($arResult['error_min']) ? '' : ' style="display:none;"'; ?>>
            <?= Loc::getMessage('DNK_BASKET_BONUS_MIN_ERROR', ['#MIN#' => $arResult['min_pay_formatted']]); ?>
        </div>

        <div class="basket-bonus-section__meta font_13 color_999" data-role="dnk-bonus-meta"<?= !empty($arResult['error_min']) ? ' style="display:none;"' : ''; ?>>
            <div data-role="dnk-bonus-balance"><?= Loc::getMessage('DNK_BASKET_BONUS_BALANCE', ['#BALANCE#' => $arResult['balance_formatted']]); ?></div>
            <div data-role="dnk-bonus-max"><?= Loc::getMessage('DNK_BASKET_BONUS_MAX', ['#MAX#' => $arResult['max_pay_formatted']]); ?></div>
            <div class="basket-bonus-section__applied" data-role="dnk-bonus-applied"<?= $applied > 0 ? '' : ' style="display:none;"'; ?>><?= Loc::getMessage('DNK_BASKET_BONUS_APPLIED', ['#APPLIED#' => $arResult['applied_formatted']]); ?></div>
        </div>

        <div class="basket-bonus-section__controls" data-role="dnk-bonus-controls"<?= !empty($arResult['error_min']) ? ' style="display:none;"' : ''; ?>>
            <div class="form-group basket-bonus-section__input-wrap">
                <input
                    type="text"
                    class="form-control basket-bonus-section__input"
                    data-role="dnk-bonus-amount"
                    value="<?= $applied > 0 ? htmlspecialcharsbx((string)$applied) : ''; ?>"
                    placeholder="0"
                    inputmode="decimal"
                />
            </div>
            <button type="button" class="btn basket-bonus-section__btn" data-role="dnk-bonus-apply">
                <?= Loc::getMessage('DNK_BASKET_BONUS_APPLY'); ?>
            </button>
        </div>

        <div class="basket-bonus-section__links font_13" data-role="dnk-bonus-links"<?= !empty($arResult['error_min']) ? ' style="display:none;"' : ''; ?>>
            <button type="button" class="basket-bonus-section__link" data-role="dnk-bonus-apply-all" data-max="<?= htmlspecialcharsbx((string)$maxPay); ?>"<?= $maxPay > 0 ? '' : ' style="display:none;"'; ?>>
                <?= Loc::getMessage('DNK_BASKET_BONUS_APPLY_ALL'); ?>
            </button>
            <button type="button" class="basket-bonus-section__link" data-role="dnk-bonus-reset"<?= $applied > 0 ? '' : ' style="display:none;"'; ?>>
                <?= Loc::getMessage('DNK_BASKET_BONUS_RESET'); ?>
            </button>
        </div>

        <div class="basket-bonus-section__meta font_13 color_999 basket-bonus-section__meta--error" data-role="dnk-bonus-meta-error"<?= !empty($arResult['error_min']) && $applied > 0 ? '' : ' style="display:none;"'; ?>>
            <div class="basket-bonus-section__applied" data-role="dnk-bonus-applied-error"><?= Loc::getMessage('DNK_BASKET_BONUS_APPLIED', ['#APPLIED#' => $arResult['applied_formatted']]); ?></div>
        </div>
        <div class="basket-bonus-section__links font_13" data-role="dnk-bonus-links-error"<?= !empty($arResult['error_min']) && $applied > 0 ? '' : ' style="display:none;"'; ?>>
            <button type="button" class="basket-bonus-section__link" data-role="dnk-bonus-reset">
                <?= Loc::getMessage('DNK_BASKET_BONUS_RESET'); ?>
            </button>
        </div>
    </div>
</div>

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

/* @var array $arParams */
/* @var array $arResult */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__.'/user_consent.php');

if (!empty($arResult['CONSENT_ALREADY_GIVEN'])) {
    if (!class_exists('TSolution') || !class_exists('TSolution\Validation')) {
        include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php';
    }

    $inputName = $arParams['INPUT_NAME'] ?? 'licenses_popup';
    $agreementId = (int)($arParams['ID'] ?? 0);

    if ($agreementId > 0) {
        ?>
        <input type="hidden" name="<?= htmlspecialcharsbx($inputName); ?>" value="<?= $agreementId; ?>">
        <?= TSolution\Validation::getFormField(); ?>
        <?php
    }

    return;
}

if ($arParams['CODE']) {
    $arResult['CONFIG']['code'] = $arParams['CODE'];
}
$config = Bitrix\Main\Web\Json::encode($arResult['CONFIG']);

$linkClassName = 'main-user-consent-request-announce';
if ($arResult['URL']) {
    $url = htmlspecialcharsbx(CUtil::JSEscape($arResult['URL']));
    $label = htmlspecialcharsbx($arResult['LABEL']);
    $label = explode('%', $label);
    $label = implode('', array_merge(
        array_slice($label, 0, 1),
        ['<a href="'.$url.'" target="_blank">'],
        array_slice($label, 1, 1),
        ['</a>'],
        array_slice($label, 2)
    ));
} else {
    $label = htmlspecialcharsbx($arResult['INPUT_LABEL']);
    $linkClassName .= '-link';
    $linkClassName .= ' base-link';
}

$inputName = $arParams['INPUT_NAME'] ?? 'licenses_popup';
$inputRequired = !isset($arParams['INPUT_REQUIRED']) || $arParams['INPUT_REQUIRED'] !== 'N';
$blockName = $arParams['BLOCK_NAME'] ?? 'licence_block';
?>
<div class="form-checkbox form-checkbox--agreement relative <?=$blockName;?>">
    <?$unicId = 'consent_'.Bitrix\Main\Security\Random::getString(6);?>
    <div class="main-user-consent-request" data-bx-user-consent="<?=htmlspecialcharsbx($config);?>">
        <label for="<?=$unicId;?>" class="form-checkbox__label">
            <span class="<?=$linkClassName;?>"><?=$label;?></span>
            <span class="form-checkbox__box"></span>
        </label>
        <input id="<?=$unicId;?>" class="form-checkbox__input form-checkbox__input--visible" type="checkbox" <?=$inputRequired ? 'required' : '';?> value="<?=$arParams['ID']?>" <?=$arParams['IS_CHECKED'] ? 'checked' : '';?> name="<?=htmlspecialcharsbx($arParams['INPUT_NAME']);?>">
        <input type="hidden" name="aspro_<?=VENDOR_SOLUTION_NAME;?>_form_validate">
    </div>

    <?if (isset($arParams['HIDDEN_ERROR']) && $arParams['HIDDEN_ERROR'] === 'Y'):?>
        <label for="<?=$unicId;?>" class="hidden error"><?=GetMessage("ERROR_FORM_LICENSE");?></label>
    <?endif;?>

    <div data-bx-template="main-user-consent-request-loader" style="display: none;">
        <div class="main-user-consent-request-popup">
            <div class="main-user-consent-request-popup-cont line-block line-block--column line-block--gap line-block--gap-10 line-block--align-normal rounded-n">
                <div data-bx-head="" class="main-user-consent-request-popup-header no-shrinked title switcher-title font_24 color_333"></div>
                <div class="main-user-consent-request-popup-body flex-1 overflow-block">
                    <div data-bx-loader="" class="main-user-consent-request-loader">
                        <svg class="main-user-consent-request-circular" viewBox="25 25 50 50">
                            <circle class="main-user-consent-request-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"></circle>
                        </svg>
                    </div>

                    <div data-bx-content="" class="main-user-consent-request-popup-content overflow-block height-100">
                        <div class="main-user-consent-request-popup-textarea-block overflow-block height-100">
                            <div data-bx-textarea="" class="main-user-consent-request-popup-text height-100 bordered rounded-6"></div>
                            <div data-bx-link="" style="display: none;" class="main-user-consent-request-popup-link">
                                <div><?=Loc::getMessage('MAIN_USER_CONSENT_REQUEST_URL_CONFIRM');?></div>
                                <div><a target="_blank"></a></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-user-consent-request-popup-buttons no-shrinked">
                    <span data-bx-btn-accept="" class="main-user-consent-request-popup-button main-user-consent-request-popup-button-acc btn btn-lg btn-default">Y</span>
                    <span data-bx-btn-reject="" class="main-user-consent-request-popup-button main-user-consent-request-popup-button-rej btn btn-default btn-lg btn-secondary-black">N</span>
                </div>
            </div>
        </div>
    </div>
</div>

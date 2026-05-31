<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$arOptions = $arConfig['PARAMS'];

$solutionAgreementPath = TSolution\Validation::getSolutionLicensePath();
if (!$solutionAgreementPath) {
    return;
}

if (!TSolution\Validation::checkSolutionAgreement($solutionAgreementPath)) {
    return;
}
?>

<div class="form-checkbox form-checkbox--agreement relative licence_block">
    <label for="<?=$arOptions['INPUT_ID'];?>" class="form-checkbox__label">
        <span class="form-checbox__text"><?include $solutionAgreementPath;?></span>
        <span class="form-checkbox__box form-box"></span>
    </label>

    <input class="form-checkbox__input form-checkbox__input--visible"
        type="checkbox"
        id="<?=$arOptions['INPUT_ID'];?>"
        name="<?=$arOptions['INPUT_NAME'];?>"
        value="Y"
        required
        <?=TSolution::GetFrontParametrValue('LICENCE_CHECKED') === 'Y' ? 'checked' : '';?>
        >

    <?if (isset($arOptions['HIDDEN_ERROR']) && $arOptions['HIDDEN_ERROR'] === 'Y'):?>
        <label for="<?=$arOptions['INPUT_ID'];?>" class="hidden error"><?=GetMessage('ERROR_FORM_LICENSE');?></label>
    <?endif;?>

    <?=TSolution\Validation::getFormField();?>
</div>

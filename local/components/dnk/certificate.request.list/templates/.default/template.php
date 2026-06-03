<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $APPLICATION;

$this->setFrameMode(false);
$this->addExternalCss($templateFolder . '/style.css');

$items = is_array($arResult['ITEMS'] ?? null) ? $arResult['ITEMS'] : [];
$navObject = $arResult['NAV_OBJECT'] ?? null;
?>
<div class="personal__block personal__block--certificate-requests">
    <?php if ($items === []) { ?>
        <div class="alert alert-info"><?= Loc::getMessage('DNK_CERT_REQ_LIST_EMPTY'); ?></div>
    <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-certificate-requests">
                <thead>
                    <tr>
                        <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_ID'); ?></th>
                        <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_DATE'); ?></th>
                        <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_SUM'); ?></th>
                        <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_STATUS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) { ?>
                        <tr>
                            <td>#<?= (int)($item['id'] ?? 0); ?></td>
                            <td><?= htmlspecialcharsbx((string)($item['dateCreateFormatted'] ?? '')); ?></td>
                            <td><?= htmlspecialcharsbx((string)($item['totalSumFormatted'] ?? '')); ?></td>
                            <td>
                                <span class="dnk-cert-req-status dnk-cert-req-status--<?= htmlspecialcharsbx((string)($item['statusCss'] ?? 'accepted')); ?>">
                                    <?= htmlspecialcharsbx((string)($item['statusLabel'] ?? '')); ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($navObject instanceof \Bitrix\Main\UI\PageNavigation && $navObject->getPageCount() > 1) { ?>
            <div class="dnk-cert-req-list-nav">
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:main.pagenavigation',
                    '',
                    [
                        'NAV_OBJECT' => $navObject,
                        'SEF_MODE' => 'N',
                    ],
                    false
                ); ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>

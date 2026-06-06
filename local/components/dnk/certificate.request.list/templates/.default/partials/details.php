<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

/**
 * @var array<string, mixed> $details
 * @var list<array<string, mixed>> $lines
 * @var bool $hasStructuredDetails
 * @var string $detailTextPlain
 */
?>
<div class="dnk-cert-req-details">
    <h4 class="dnk-cert-req-details__title"><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_TITLE'); ?></h4>
    <?php if ($hasStructuredDetails) { ?>
        <?php if (($details['contactName'] ?? '') !== '' || ($details['contactPhone'] ?? '') !== '' || ($details['contactEmail'] ?? '') !== '') { ?>
            <dl class="dnk-cert-req-details__block">
                <dt><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_CONTACT'); ?></dt>
                <dd>
                    <?php if (($details['contactName'] ?? '') !== '') { ?>
                        <div><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_NAME'); ?>: <?= htmlspecialcharsbx((string)$details['contactName']); ?></div>
                    <?php } ?>
                    <?php if (($details['contactPhone'] ?? '') !== '') { ?>
                        <div><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_PHONE'); ?>: <?= htmlspecialcharsbx((string)$details['contactPhone']); ?></div>
                    <?php } ?>
                    <?php if (($details['contactEmail'] ?? '') !== '') { ?>
                        <div><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_EMAIL'); ?>: <?= htmlspecialcharsbx((string)$details['contactEmail']); ?></div>
                    <?php } ?>
                </dd>
            </dl>
        <?php } ?>
        <?php if (($details['deliveryLabel'] ?? '') !== '') { ?>
            <dl class="dnk-cert-req-details__block">
                <dt><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_DELIVERY'); ?></dt>
                <dd><?= htmlspecialcharsbx((string)$details['deliveryLabel']); ?></dd>
            </dl>
        <?php } ?>
        <?php if (($details['paymentLabel'] ?? '') !== '') { ?>
            <dl class="dnk-cert-req-details__block">
                <dt><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_PAYMENT'); ?></dt>
                <dd><?= htmlspecialcharsbx((string)$details['paymentLabel']); ?></dd>
            </dl>
        <?php } ?>
        <?php if ($lines !== []) { ?>
            <div class="dnk-cert-req-details__block">
                <div class="dnk-cert-req-details__label"><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_LINES'); ?></div>
                <ul class="dnk-cert-req-details__lines">
                    <?php foreach ($lines as $line) { ?>
                        <li>
                            <?= htmlspecialcharsbx((string)($line['name'] ?? '')); ?>
                            — <?= htmlspecialcharsbx((string)($line['nominalFormatted'] ?? '')); ?>
                            × <?= (int)($line['qty'] ?? 0); ?>
                            = <?= htmlspecialcharsbx((string)($line['lineSumFormatted'] ?? '')); ?>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
        <?php if (($details['comment'] ?? '') !== '') { ?>
            <dl class="dnk-cert-req-details__block">
                <dt><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_COMMENT'); ?></dt>
                <dd><?= nl2br(htmlspecialcharsbx((string)$details['comment']), false); ?></dd>
            </dl>
        <?php } ?>
    <?php } elseif ($detailTextPlain !== '') { ?>
        <pre class="dnk-cert-req-details__plain"><?= htmlspecialcharsbx($detailTextPlain); ?></pre>
    <?php } else { ?>
        <p class="dnk-cert-req-details__empty muted"><?= Loc::getMessage('DNK_CERT_REQ_LIST_DETAILS_EMPTY'); ?></p>
    <?php } ?>
</div>

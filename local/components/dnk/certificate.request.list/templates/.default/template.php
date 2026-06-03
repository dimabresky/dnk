<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$this->setFrameMode(false);
$this->addExternalCss($templateFolder . '/style.css');

$items = is_array($arResult['ITEMS'] ?? null) ? $arResult['ITEMS'] : [];
$detailsPartial = __DIR__ . '/partials/details.php';

/**
 * @param array<string, mixed> $item
 * @return array{
 *     itemId: int,
 *     details: array<string, mixed>,
 *     lines: list<array<string, mixed>>,
 *     hasStructuredDetails: bool,
 *     detailTextPlain: string
 * }
 */
$resolveItemContext = static function (array $item): array {
    $itemId = (int)($item['id'] ?? 0);
    $details = is_array($item['details'] ?? null) ? $item['details'] : [];
    $lines = is_array($details['lines'] ?? null) ? $details['lines'] : [];
    $hasStructuredDetails = ($details['contactName'] ?? '') !== ''
        || ($details['contactPhone'] ?? '') !== ''
        || ($details['contactEmail'] ?? '') !== ''
        || ($details['deliveryLabel'] ?? '') !== ''
        || ($details['paymentLabel'] ?? '') !== ''
        || ($details['comment'] ?? '') !== ''
        || $lines !== [];

    return [
        'itemId' => $itemId,
        'details' => $details,
        'lines' => $lines,
        'hasStructuredDetails' => $hasStructuredDetails,
        'detailTextPlain' => trim((string)($details['detailTextPlain'] ?? '')),
    ];
};
?>
<div class="personal__block personal__block--certificate-requests">
    <?php if ($items === []) { ?>
        <div class="alert alert-info"><?= Loc::getMessage('DNK_CERT_REQ_LIST_EMPTY'); ?></div>
    <?php } else { ?>
        <div class="dnk-cert-req-view dnk-cert-req-view--desktop">
            <div class="table-responsive">
                <table class="table table-certificate-requests">
                    <thead>
                        <tr>
                            <th class="table-certificate-requests__col-toggle" scope="col" aria-hidden="true"></th>
                            <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_ID'); ?></th>
                            <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_DATE'); ?></th>
                            <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_SUM'); ?></th>
                            <th><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_STATUS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item) {
                            $ctx = $resolveItemContext($item);
                            $itemId = $ctx['itemId'];
                            $detailsId = 'dnk-cert-req-details-desktop-' . $itemId;
                            ?>
                            <tr class="table-certificate-requests__row" data-request-id="<?= $itemId; ?>">
                                <td class="table-certificate-requests__toggle-cell">
                                    <button
                                        type="button"
                                        class="dnk-cert-req-toggle"
                                        aria-expanded="false"
                                        aria-controls="<?= htmlspecialcharsbx($detailsId); ?>"
                                        data-testid="cert-req-toggle-desktop-<?= $itemId; ?>"
                                    >
                                        <span class="dnk-cert-req-toggle__label"><?= Loc::getMessage('DNK_CERT_REQ_LIST_TOGGLE_SHOW'); ?></span>
                                    </button>
                                </td>
                                <td>#<?= $itemId; ?></td>
                                <td><?= htmlspecialcharsbx((string)($item['dateCreateFormatted'] ?? '')); ?></td>
                                <td><?= htmlspecialcharsbx((string)($item['totalSumFormatted'] ?? '')); ?></td>
                                <td>
                                    <span class="dnk-cert-req-status dnk-cert-req-status--<?= htmlspecialcharsbx((string)($item['statusCss'] ?? 'accepted')); ?>">
                                        <?= htmlspecialcharsbx((string)($item['statusLabel'] ?? '')); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr class="table-certificate-requests__details-row" id="<?= htmlspecialcharsbx($detailsId); ?>" hidden>
                                <td colspan="5">
                                    <?php
                                    $details = $ctx['details'];
                                    $lines = $ctx['lines'];
                                    $hasStructuredDetails = $ctx['hasStructuredDetails'];
                                    $detailTextPlain = $ctx['detailTextPlain'];
                                    include $detailsPartial;
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dnk-cert-req-view dnk-cert-req-view--mobile">
            <ul class="dnk-cert-req-mobile-list">
                <?php foreach ($items as $item) {
                    $ctx = $resolveItemContext($item);
                    $itemId = $ctx['itemId'];
                    $detailsId = 'dnk-cert-req-details-mobile-' . $itemId;
                    ?>
                    <li class="dnk-cert-req-mobile-card" data-request-id="<?= $itemId; ?>">
                        <div class="dnk-cert-req-mobile-card__head">
                            <div class="dnk-cert-req-mobile-card__order">
                                <span class="dnk-cert-req-mobile-card__label"><?= Loc::getMessage('DNK_CERT_REQ_LIST_MOBILE_ORDER'); ?></span>
                                <span class="dnk-cert-req-mobile-card__value">#<?= $itemId; ?></span>
                            </div>
                            <span class="dnk-cert-req-status dnk-cert-req-status--<?= htmlspecialcharsbx((string)($item['statusCss'] ?? 'accepted')); ?>">
                                <?= htmlspecialcharsbx((string)($item['statusLabel'] ?? '')); ?>
                            </span>
                        </div>
                        <dl class="dnk-cert-req-mobile-card__meta">
                            <div class="dnk-cert-req-mobile-card__meta-row">
                                <dt><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_DATE'); ?></dt>
                                <dd><?= htmlspecialcharsbx((string)($item['dateCreateFormatted'] ?? '')); ?></dd>
                            </div>
                            <div class="dnk-cert-req-mobile-card__meta-row">
                                <dt><?= Loc::getMessage('DNK_CERT_REQ_LIST_COL_SUM'); ?></dt>
                                <dd><?= htmlspecialcharsbx((string)($item['totalSumFormatted'] ?? '')); ?></dd>
                            </div>
                        </dl>
                        <button
                            type="button"
                            class="dnk-cert-req-toggle dnk-cert-req-mobile-card__toggle"
                            aria-expanded="false"
                            aria-controls="<?= htmlspecialcharsbx($detailsId); ?>"
                            data-testid="cert-req-toggle-mobile-<?= $itemId; ?>"
                        >
                            <span class="dnk-cert-req-toggle__label"><?= Loc::getMessage('DNK_CERT_REQ_LIST_TOGGLE_SHOW'); ?></span>
                        </button>
                        <div class="dnk-cert-req-mobile-card__details" id="<?= htmlspecialcharsbx($detailsId); ?>" hidden>
                            <?php
                            $details = $ctx['details'];
                            $lines = $ctx['lines'];
                            $hasStructuredDetails = $ctx['hasStructuredDetails'];
                            $detailTextPlain = $ctx['detailTextPlain'];
                            include $detailsPartial;
                            ?>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </div>

        <script>
        (function () {
            var showLabel = <?= \CUtil::PhpToJSObject(Loc::getMessage('DNK_CERT_REQ_LIST_TOGGLE_SHOW')); ?>;
            var hideLabel = <?= \CUtil::PhpToJSObject(Loc::getMessage('DNK_CERT_REQ_LIST_TOGGLE_HIDE')); ?>;

            document.querySelectorAll('.personal__block--certificate-requests .dnk-cert-req-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var controlsId = btn.getAttribute('aria-controls');
                    if (!controlsId) {
                        return;
                    }
                    var panel = document.getElementById(controlsId);
                    if (!panel) {
                        return;
                    }
                    var expanded = btn.getAttribute('aria-expanded') === 'true';
                    var nextExpanded = !expanded;
                    btn.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
                    panel.hidden = !nextExpanded;
                    var label = btn.querySelector('.dnk-cert-req-toggle__label');
                    if (label) {
                        label.textContent = nextExpanded ? hideLabel : showLabel;
                    }
                    btn.classList.toggle('dnk-cert-req-toggle--expanded', nextExpanded);
                });
            });
        })();
        </script>
    <?php } ?>
</div>

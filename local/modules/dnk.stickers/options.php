<?php

/**
 * Admin settings page for dnk.stickers.
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Dnk\Stickers\Config;
use Dnk\Stickers\StickerService;

global $APPLICATION;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$mid = Config::MODULE_ID;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($mid);
if ($MODULE_RIGHT < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($mid);

$actionMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $MODULE_RIGHT >= 'W'
    && check_bitrix_sessid()
) {
    if (!empty($_REQUEST['Update'])) {
        $enabled = !empty($_REQUEST['enabled']) ? 'Y' : 'N';
        Option::set($mid, 'enabled', $enabled);
        Option::set($mid, 'iblock_id', (string) max(0, (int) ($_REQUEST['iblock_id'] ?? 42)));
        Option::set($mid, 'hit_property_code', trim((string) ($_REQUEST['hit_property_code'] ?? 'HIT')) ?: 'HIT');
        Option::set($mid, 'batch_size', (string) max(1, (int) ($_REQUEST['batch_size'] ?? 100)));
        Option::set($mid, 'agent_interval', (string) max(60, (int) ($_REQUEST['agent_interval'] ?? 3600)));

        $ruleEnabled = !empty($_REQUEST['rule_new_enabled']);
        $autoOnCreate = !empty($_REQUEST['rule_new_auto_on_create']);
        $trackManual = !empty($_REQUEST['rule_new_track_manual']);
        $lifetimeDays = (float) str_replace(',', '.', (string) ($_REQUEST['rule_new_lifetime_days'] ?? '30'));
        if ($lifetimeDays < 0) {
            $lifetimeDays = 0.0;
        }

        Config::setRules([
            [
                'xml_id' => 'NEW',
                'enabled' => $ruleEnabled,
                'lifetime_days' => $lifetimeDays,
                'auto_on_create' => $autoOnCreate,
                'track_manual' => $trackManual,
            ],
        ]);

        \CAgent::RemoveModuleAgents($mid);
        \CAgent::AddAgent(
            '\\Dnk\\Stickers\\Agent::run();',
            $mid,
            'N',
            Config::getAgentInterval(),
            '',
            'Y',
            '',
            100
        );
    }

    if (!empty($_REQUEST['remember_stickers'])) {
        $results = StickerService::rememberAllEnabled();
        $scanned = 0;
        $tracked = 0;
        $skipped = 0;
        foreach ($results as $stats) {
            $scanned += (int) ($stats['scanned'] ?? 0);
            $tracked += (int) ($stats['tracked'] ?? 0);
            $skipped += (int) ($stats['skipped'] ?? 0);
        }
        $actionMessage = Loc::getMessage('DNK_STICKERS_OPT_RESULT_REMEMBER', [
            '#TRACKED#' => (string) $tracked,
            '#SKIPPED#' => (string) $skipped,
            '#SCANNED#' => (string) $scanned,
        ]);
    }

    if (!empty($_REQUEST['expire_stickers'])) {
        $results = StickerService::expireAll();
        $processed = 0;
        $removed = 0;
        $cleaned = 0;
        foreach ($results as $stats) {
            $processed += (int) ($stats['processed'] ?? 0);
            $removed += (int) ($stats['removed'] ?? 0);
            $cleaned += (int) ($stats['cleaned'] ?? 0);
        }
        $actionMessage = Loc::getMessage('DNK_STICKERS_OPT_RESULT_EXPIRE', [
            '#REMOVED#' => (string) $removed,
            '#CLEANED#' => (string) $cleaned,
            '#PROCESSED#' => (string) $processed,
        ]);
    }
}

$enabled = Option::get($mid, 'enabled', 'Y');
$iblockId = Option::get($mid, 'iblock_id', '42');
$hitPropertyCode = Option::get($mid, 'hit_property_code', 'HIT');
$batchSize = Option::get($mid, 'batch_size', '100');
$agentInterval = Option::get($mid, 'agent_interval', '3600');

$newRule = Config::getRuleByXmlId('NEW') ?? Config::defaultNewRule();

$APPLICATION->SetTitle(Loc::getMessage('DNK_STICKERS_OPT_TITLE'));
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if ($actionMessage !== '' && $actionMessage !== null) {
    CAdminMessage::ShowNote($actionMessage);
}

$tabs = [
    [
        'DIV' => 'dnk_stickers_main',
        'TAB' => Loc::getMessage('DNK_STICKERS_OPT_TAB_MAIN'),
        'TITLE' => Loc::getMessage('DNK_STICKERS_OPT_TAB_MAIN'),
    ],
    [
        'DIV' => 'dnk_stickers_actions',
        'TAB' => Loc::getMessage('DNK_STICKERS_OPT_TAB_ACTIONS'),
        'TITLE' => Loc::getMessage('DNK_STICKERS_OPT_TAB_ACTIONS'),
    ],
];
$tabControl = new CAdminTabControl('dnkStickersTab', $tabs);
$tabControl->Begin();
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= htmlspecialcharsbx($mid) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <div class="adm-info-message-wrap"><div class="adm-info-message"><?= Loc::getMessage('DNK_STICKERS_OPT_ASPRO_NOTE') ?></div></div>
        </td>
    </tr>
    <tr>
        <td width="40%"><?= Loc::getMessage('DNK_STICKERS_OPT_ENABLED') ?>:</td>
        <td><input type="checkbox" name="enabled" value="Y"<?= $enabled === 'Y' ? ' checked' : '' ?>></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_IBLOCK_ID') ?>:</td>
        <td><input type="text" name="iblock_id" value="<?= htmlspecialcharsbx($iblockId) ?>" size="10"></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_HIT_PROPERTY') ?>:</td>
        <td><input type="text" name="hit_property_code" value="<?= htmlspecialcharsbx($hitPropertyCode) ?>" size="20"></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_BATCH_SIZE') ?>:</td>
        <td><input type="text" name="batch_size" value="<?= htmlspecialcharsbx($batchSize) ?>" size="10"></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_AGENT_INTERVAL') ?>:</td>
        <td><input type="text" name="agent_interval" value="<?= htmlspecialcharsbx($agentInterval) ?>" size="10"></td>
    </tr>
    <tr class="heading">
        <td colspan="2"><?= Loc::getMessage('DNK_STICKERS_OPT_RULE_NEW') ?></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_RULE_ENABLED') ?>:</td>
        <td><input type="checkbox" name="rule_new_enabled" value="Y"<?= !empty($newRule['enabled']) ? ' checked' : '' ?>></td>
    </tr>
    <tr>
        <td class="adm-detail-valign-top"><?= Loc::getMessage('DNK_STICKERS_OPT_LIFETIME_DAYS') ?>:</td>
        <td>
            <input type="text" name="rule_new_lifetime_days" value="<?= htmlspecialcharsbx((string) $newRule['lifetime_days']) ?>" size="10">
            <div class="adm-info-message-wrap"><div class="adm-info-message"><?= Loc::getMessage('DNK_STICKERS_OPT_LIFETIME_HINT') ?></div></div>
        </td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_AUTO_ON_CREATE') ?>:</td>
        <td><input type="checkbox" name="rule_new_auto_on_create" value="Y"<?= !empty($newRule['auto_on_create']) ? ' checked' : '' ?>></td>
    </tr>
    <tr>
        <td><?= Loc::getMessage('DNK_STICKERS_OPT_TRACK_MANUAL') ?>:</td>
        <td><input type="checkbox" name="rule_new_track_manual" value="Y"<?= !empty($newRule['track_manual']) ? ' checked' : '' ?>></td>
    </tr>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td class="adm-detail-valign-top" width="40%"><?= Loc::getMessage('DNK_STICKERS_OPT_REMEMBER') ?>:</td>
        <td>
            <input type="submit" name="remember_stickers" value="<?= Loc::getMessage('DNK_STICKERS_OPT_REMEMBER') ?>"<?= $MODULE_RIGHT < 'W' ? ' disabled' : '' ?>>
            <div class="adm-info-message-wrap"><div class="adm-info-message"><?= Loc::getMessage('DNK_STICKERS_OPT_REMEMBER_HINT') ?></div></div>
        </td>
    </tr>
    <tr>
        <td class="adm-detail-valign-top"><?= Loc::getMessage('DNK_STICKERS_OPT_EXPIRE') ?>:</td>
        <td>
            <input type="submit" name="expire_stickers" value="<?= Loc::getMessage('DNK_STICKERS_OPT_EXPIRE') ?>"<?= $MODULE_RIGHT < 'W' ? ' disabled' : '' ?>>
            <div class="adm-info-message-wrap"><div class="adm-info-message"><?= Loc::getMessage('DNK_STICKERS_OPT_EXPIRE_HINT') ?></div></div>
        </td>
    </tr>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="Update" value="<?= Loc::getMessage('DNK_STICKERS_OPT_SAVE') ?>" class="adm-btn-save"<?= $MODULE_RIGHT < 'W' ? ' disabled' : '' ?>>
    <?php $tabControl->End(); ?>
</form>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

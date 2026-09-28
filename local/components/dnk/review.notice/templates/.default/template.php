<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$frame = $this->createFrame('dnk-review-notice')->begin('');

if (!empty($arResult['IMAGE'])) {
    $config = [
        'intervalHours' => (int) ($arResult['INTERVAL_HOURS'] ?? 12),
        'title' => (string) ($arResult['TITLE'] ?? ''),
        'detail' => (string) ($arResult['DETAIL'] ?? ''),
        'image' => (string) ($arResult['IMAGE'] ?? ''),
        'link' => (string) ($arResult['LINK'] ?? ''),
    ];
    ?>
<script>
  BX.ready(function () {
    if (typeof dnkReviewNotice === 'function') {
      dnkReviewNotice(<?= \CUtil::PhpToJSObject($config, false, true) ?>);
    }
  });
</script>
    <?php
}

$frame->end();

<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$this->setFrameMode(false);
?>
<div class="personal__block personal__block--consents">
    <?if (empty($arResult['AGREEMENTS'])):?>
        <div class="alert alert-info"><?=Loc::getMessage('DNK_UC_MANAGE_EMPTY')?></div>
    <?else:?>
        <div class="table-responsive">
            <table class="table table-consents">
                <thead>
                    <tr>
                        <th><?=Loc::getMessage('DNK_UC_MANAGE_COL_NAME')?></th>
                        <th><?=Loc::getMessage('DNK_UC_MANAGE_COL_STATUS')?></th>
                        <th><?=Loc::getMessage('DNK_UC_MANAGE_COL_ACTION')?></th>
                    </tr>
                </thead>
                <tbody>
                    <?foreach ($arResult['AGREEMENTS'] as $agreement):?>
                        <tr data-agreement-id="<?=(int)$agreement['id']?>">
                            <td><?=htmlspecialcharsbx($agreement['name'])?></td>
                            <td class="js-consent-status">
                                <?=Loc::getMessage($agreement['active'] ? 'DNK_UC_MANAGE_STATUS_ACTIVE' : 'DNK_UC_MANAGE_STATUS_REVOKED')?>
                            </td>
                            <td>
                                <?if ($agreement['active']):?>
                                    <button type="button"
                                        class="btn btn-default btn-sm js-consent-revoke"
                                        data-agreement-id="<?=(int)$agreement['id']?>">
                                        <?=Loc::getMessage('DNK_UC_MANAGE_REVOKE')?>
                                    </button>
                                <?endif;?>
                            </td>
                        </tr>
                    <?endforeach;?>
                </tbody>
            </table>
        </div>
    <?endif;?>
</div>

<script>
BX.ready(function () {
  var ajaxUrl = <?=CUtil::PhpToJSObject($arResult['AJAX_URL'])?>;
  var confirmText = <?=CUtil::PhpToJSObject(Loc::getMessage('DNK_UC_MANAGE_REVOKE_CONFIRM'))?>;
  var successText = <?=CUtil::PhpToJSObject(Loc::getMessage('DNK_UC_MANAGE_REVOKE_SUCCESS'))?>;
  var errorText = <?=CUtil::PhpToJSObject(Loc::getMessage('DNK_UC_MANAGE_REVOKE_ERROR'))?>;

  document.querySelectorAll('.js-consent-revoke').forEach(function (button) {
    BX.bind(button, 'click', function () {
      if (!confirm(confirmText)) {
        return;
      }

      var agreementId = button.getAttribute('data-agreement-id');
      BX.ajax({
        url: ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'revoke',
          agreement_id: agreementId,
          sessid: BX.bitrix_sessid(),
        },
        onsuccess: function (response) {
          if (response && response.success) {
            var row = button.closest('tr');
            if (row) {
              var statusCell = row.querySelector('.js-consent-status');
              if (statusCell) {
                statusCell.textContent = <?=CUtil::PhpToJSObject(Loc::getMessage('DNK_UC_MANAGE_STATUS_REVOKED'))?>;
              }
              button.remove();
            }
            alert(successText);
          } else {
            alert(errorText);
          }
        },
        onfailure: function () {
          alert(errorText);
        },
      });
    });
  });
});
</script>

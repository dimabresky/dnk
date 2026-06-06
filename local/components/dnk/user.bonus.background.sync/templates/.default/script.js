(function (window) {
  'use strict';

  function dnkUserBonusBackgroundSync() {
    BX.ajax.runComponentAction('dnk:user.bonus.background.sync', 'refresh', {
      mode: 'class',
      data: {},
    });
  }

  window.dnkUserBonusBackgroundSync = dnkUserBonusBackgroundSync;
})(window);

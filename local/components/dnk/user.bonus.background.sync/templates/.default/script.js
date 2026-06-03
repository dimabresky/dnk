(function () {
  'use strict';

  var COMPONENT_NAME = 'dnk:user.bonus.background.sync';
  var ACTION_NAME = 'refresh';

  function runRefresh() {
    if (typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runComponentAction) {
      return;
    }

    BX.ajax
      .runComponentAction(COMPONENT_NAME, ACTION_NAME, {
        mode: 'class',
        data: {},
      })
      .catch(function () {
        /* тихий отказ */
      });
  }

  function init() {
    var root = document.getElementById('dnk-bonus-background-sync');
    if (!root || root.getAttribute('data-dnk-bonus-bg-init') === '1') {
      return;
    }

    root.setAttribute('data-dnk-bonus-bg-init', '1');

    if (root.getAttribute('data-auto-refresh') !== 'Y') {
      return;
    }

    if (typeof BX !== 'undefined' && BX.ready) {
      BX.ready(runRefresh);
    } else if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', runRefresh);
    } else {
      runRefresh();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

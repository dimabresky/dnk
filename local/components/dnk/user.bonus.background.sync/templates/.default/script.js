(function () {
  'use strict';

  var COMPONENT_NAME = 'dnk:user.bonus.background.sync';
  var ACTION_NAME = 'refresh';

  function formatBalanceHtml(formatted, unit) {
    var amount = formatted != null ? String(formatted) : '';
    var suffix = unit != null && String(unit).trim() !== '' ? ' ' + String(unit).trim() : '';

    return amount + suffix;
  }

  function updateBalanceNodes(selector, formatted, unit) {
    if (!selector) {
      return;
    }

    var nodes;
    try {
      nodes = document.querySelectorAll(selector);
    } catch (e) {
      return;
    }

    if (!nodes || !nodes.length) {
      return;
    }

    var html = formatBalanceHtml(formatted, unit);
    nodes.forEach(function (node) {
      node.textContent = html;
    });
  }

  function runRefresh(root) {
    var selector = root.getAttribute('data-balance-selector') || '.js-dnk-bonus-balance';

    if (typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runComponentAction) {
      return;
    }

    BX.ajax
      .runComponentAction(COMPONENT_NAME, ACTION_NAME, {
        mode: 'class',
        data: {},
      })
      .then(function (response) {
        var data = response && response.data ? response.data : null;
        if (!data) {
          return;
        }

        updateBalanceNodes(selector, data.balanceFormatted, data.balanceUnit);
      })
      .catch(function () {
        /* тихий отказ: баланс на странице остаётся как при рендере */
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
      BX.ready(function () {
        runRefresh(root);
      });
    } else if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () {
        runRefresh(root);
      });
    } else {
      runRefresh(root);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

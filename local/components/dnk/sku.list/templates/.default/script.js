(function () {
    'use strict';

    function bindRoot(root) {
        if (root.getAttribute('data-dnk-sku-list-init') === '1') {
            return;
        }

        var label = root.querySelector('[data-dnk-sku-label]');
        var itemsWrap = root.querySelector('.dnk-sku-list__items');
        if (!label || !itemsWrap) {
            return;
        }

        root.setAttribute('data-dnk-sku-list-init', '1');

        var defaultName = label.getAttribute('data-default-name') || label.textContent;

        function restoreLabel() {
            label.textContent = defaultName;
        }

        itemsWrap.querySelectorAll('.dnk-sku-list__item').forEach(function (item) {
            item.addEventListener('mouseenter', function () {
                var name = item.getAttribute('data-sku-name');
                if (name) {
                    label.textContent = name;
                }
            });
        });

        itemsWrap.addEventListener('mouseleave', restoreLabel);
    }

    function scan() {
        document.querySelectorAll('[data-dnk-sku-list]').forEach(bindRoot);
    }

    function boot() {
        scan();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('load', boot);

    if (typeof BX !== 'undefined' && BX.addCustomEvent) {
        BX.addCustomEvent(window, 'onAjaxSuccess', boot);
    }
})();

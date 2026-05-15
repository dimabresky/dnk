(function () {
    'use strict';

    /** @type {{ root: Element, close: function(): void }[]} */
    var instances = [];
    var documentListenersBound = false;

    function bindDocumentListenersOnce() {
        if (documentListenersBound) {
            return;
        }
        documentListenersBound = true;

        document.addEventListener('click', function (e) {
            instances.forEach(function (inst) {
                if (!inst.root.contains(e.target)) {
                    inst.close();
                }
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                instances.forEach(function (inst) {
                    inst.close();
                });
            }
        });
    }

    function bindRoot(root) {
        if (root.getAttribute('data-dnk-sku-list-init') === '1') {
            return;
        }

        var trigger = root.querySelector('.dnk-sku-list__trigger');
        var menu = root.querySelector('.dnk-sku-list__menu');
        if (!trigger || !menu) {
            return;
        }

        root.setAttribute('data-dnk-sku-list-init', '1');

        function open() {
            root.classList.add('dnk-sku-list--open');
            menu.classList.add('dnk-sku-list__menu--open');
            menu.setAttribute('aria-hidden', 'false');
            trigger.setAttribute('aria-expanded', 'true');
        }

        function close() {
            root.classList.remove('dnk-sku-list--open');
            menu.classList.remove('dnk-sku-list__menu--open');
            menu.setAttribute('aria-hidden', 'true');
            trigger.setAttribute('aria-expanded', 'false');
        }

        function toggle() {
            if (menu.classList.contains('dnk-sku-list__menu--open')) {
                close();
            } else {
                open();
            }
        }

        var inst = { root: root, close: close };
        instances.push(inst);

        trigger.addEventListener('mousedown', function (e) {
            e.stopPropagation();
        });

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            instances.forEach(function (other) {
                if (other.root !== root) {
                    other.close();
                }
            });
            toggle();
        });
    }

    function scan() {
        bindDocumentListenersOnce();
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

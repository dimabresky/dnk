(function () {
    'use strict';

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function formatRemaining(sec) {
        if (sec < 0) {
            sec = 0;
        }
        var d = Math.floor(sec / 86400);
        var h = Math.floor((sec % 86400) / 3600);
        var m = Math.floor((sec % 3600) / 60);
        var s = Math.floor(sec % 60);
        if (d > 0) {
            return d + ':' + pad2(h) + ':' + pad2(m) + ':' + pad2(s);
        }
        return pad2(h) + ':' + pad2(m) + ':' + pad2(s);
    }

    function initBar(root) {
        var dismissKey = root.getAttribute('data-dismiss-key') || '';
        var elementId = root.getAttribute('data-element-id') || '';
        var storageKey = 'dnk_header_promo_hide_' + (dismissKey || elementId);
        var allowDismiss = root.getAttribute('data-allow-dismiss') === '1';

        if (allowDismiss && dismissKey) {
            try {
                if (window.localStorage && localStorage.getItem(storageKey) === '1') {
                    root.classList.add('dnk-header-promo-bar--hidden');
                    return;
                }
            } catch (e) {}
        }

        var closeBtn = root.querySelector('.dnk-header-promo-bar__close');
        if (closeBtn && allowDismiss) {
            closeBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                root.classList.add('dnk-header-promo-bar--hidden');
                try {
                    if (window.localStorage && dismissKey) {
                        localStorage.setItem(storageKey, '1');
                    }
                } catch (e) {}
            });
        }

        var showTimer = root.getAttribute('data-show-timer') === '1';
        var endTs = parseInt(root.getAttribute('data-timer-end') || '0', 10);
        var hideOnExpire = root.getAttribute('data-hide-on-expire') === '1';
        var timerEl = root.querySelector('[data-role="timer"]');

        if (!showTimer || !timerEl || !endTs) {
            return;
        }

        function tick() {
            var now = Math.floor(Date.now() / 1000);
            var left = endTs - now;
            if (left <= 0) {
                timerEl.textContent = formatRemaining(0);
                if (hideOnExpire) {
                    root.classList.add('dnk-header-promo-bar--hidden');
                }
                return false;
            }
            timerEl.textContent = formatRemaining(left);
            return true;
        }

        if (!tick()) {
            return;
        }
        setInterval(function () {
            if (!tick()) {
                /* stop interval not trivial without id; bar hidden is enough */
            }
        }, 1000);
    }

    function run() {
        var root = document.getElementById('dnk-header-promo-bar');
        if (!root) {
            return;
        }
        initBar(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();

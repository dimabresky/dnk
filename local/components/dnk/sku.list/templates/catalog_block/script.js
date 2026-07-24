(function () {
    'use strict';

    var navEvents = ['resize', 'fromEdge', 'toEdge', 'slideChange', 'update', 'setTranslate'];

    function waitForSwiper(sliderEl, callback) {
        if (sliderEl.swiper) {
            callback(sliderEl.swiper);
            return;
        }

        var observer = new MutationObserver(function () {
            if (sliderEl.classList.contains('swiper-initialized') && sliderEl.swiper) {
                observer.disconnect();
                callback(sliderEl.swiper);
            }
        });

        observer.observe(sliderEl, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    function bindNavButtons(sliderEl, prevBtn, nextBtn) {
        if (sliderEl.getAttribute('data-dnk-sku-nav-init') === '1') {
            return;
        }

        waitForSwiper(sliderEl, function (swiper) {
            if (sliderEl.getAttribute('data-dnk-sku-nav-init') === '1') {
                return;
            }

            sliderEl.setAttribute('data-dnk-sku-nav-init', '1');

            function updateNavVisibility() {
                prevBtn.hidden = swiper.isLocked || swiper.isBeginning;
                nextBtn.hidden = swiper.isLocked || swiper.isEnd;
            }

            prevBtn.addEventListener('click', function () {
                swiper.slidePrev();
            });

            nextBtn.addEventListener('click', function () {
                swiper.slideNext();
            });

            updateNavVisibility();

            navEvents.forEach(function (eventName) {
                swiper.on(eventName, updateNavVisibility);
            });

            window.addEventListener('resize', updateNavVisibility);
        });
    }

    function bindRoot(root) {
        if (root.getAttribute('data-dnk-sku-list-init') === '1') {
            return;
        }

        var label = root.querySelector('[data-dnk-sku-label]');
        var itemsWrap = root.querySelector('.dnk-sku-list__slider');
        var prevBtn = root.querySelector('[data-dnk-sku-prev]');
        var nextBtn = root.querySelector('[data-dnk-sku-next]');
        if (!label || !itemsWrap || !prevBtn || !nextBtn) {
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
        bindNavButtons(itemsWrap, prevBtn, nextBtn);
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

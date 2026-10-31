(function () {
  var seen = new WeakSet();
  var watched = new WeakMap();
  var pluginsReady = false;
  var pluginsLoading = false;
  var loadAttempts = 0;

  function parseEnd(value) {
    var ts = Number(value);
    if (!ts) {
      return null;
    }

    var date = new Date(ts);
    if (isNaN(date.getTime())) {
      return null;
    }

    return date;
  }

  function messageList(code) {
    var value = BX.message(code);
    return value && value.length ? value : [];
  }

  function isVisibleDuplicate(el) {
    var slide = el.closest('.swiper-slide');
    if (!slide || !slide.classList.contains('swiper-slide-duplicate')) {
      return true;
    }

    return slide.classList.contains('swiper-slide-active')
      || slide.classList.contains('swiper-slide-duplicate-active');
  }

  function arm(el) {
    if (seen.has(el)) {
      return true;
    }

    if (!window.FlipClock || !FlipClock.flipClock || !isVisibleDuplicate(el)) {
      return false;
    }

    var img = el.querySelector('img[data-src]');
    if (img) {
      img.src = img.getAttribute('data-src');
      img.removeAttribute('data-src');
    }

    var items = el.querySelector('.dnk-banner-countdown__items');
    var dateTo = parseEnd(el.getAttribute('data-end'));
    if (!items || !dateTo) {
      seen.add(el);
      return true;
    }

    var labels = messageList('DNK_BANNER_COUNTDOWN_LABELS');

    try {
      FlipClock.flipClock({
        parent: items,
        face: FlipClock.elapsedTime({
          to: dateTo,
          format: '[DD]:[hh]:[mm]:[ss]'
        }),
        theme: FlipClock.theme({
          dividers: ':',
          labels: [
            [labels[3] || ''],
            [labels[4] || ''],
            [labels[5] || ''],
            [labels[6] || '']
          ],
          css: FlipClock.css()
        })
      });
    } catch (e) {
      return false;
    }

    seen.add(el);

    return true;
  }

  function watch(el) {
    if (seen.has(el) || watched.has(el)) {
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        if (!arm(entry.target)) {
          observer.unobserve(entry.target);
          watched.delete(entry.target);
          return;
        }

        observer.unobserve(entry.target);
      });
    }, { root: null, rootMargin: '200px', threshold: 0 });

    watched.set(el, observer);
    observer.observe(el);
  }

  function scan() {
    if (!pluginsReady) {
      return;
    }

    document.querySelectorAll('.dnk-banner-countdown').forEach(watch);
  }

  function loadPlugins() {
    if (pluginsLoading || pluginsReady || loadAttempts >= 2) {
      return;
    }

    if (!window.BX || !BX.Aspro || !BX.Aspro.Loader) {
      return;
    }

    var src = BX.message('DNK_BANNER_COUNTDOWN_LIB');
    if (!src) {
      return;
    }

    pluginsLoading = true;
    loadAttempts += 1;

    BX.Aspro.Loader.once({
      appear: ['.dnk-banner-countdown'],
      add: {
        js: [src]
      }
    }).then(function () {
      pluginsReady = true;
      scan();
    }).catch(function () {
      pluginsLoading = false;
      window.setTimeout(loadPlugins, 1000);
    });
  }

  BX.addCustomEvent('onInitSlider', function () {
    loadPlugins();
    scan();
  });
  BX.addCustomEvent('onSlideChanges', function () {
    loadPlugins();
    scan();
  });
  BX.ready(loadPlugins);
})();

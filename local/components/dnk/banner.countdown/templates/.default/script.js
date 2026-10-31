(function () {
  var seen = new WeakSet();
  var watched = new WeakMap();
  var pluginsReady = false;
  var pluginsLoading = false;

  var loadAttempts = 0;

  var layout = ''
    + '<span class="dnk-banner-countdown__unit">'
    + '<span class="dnk-banner-countdown__value font_48">{dnn}</span>'
    + '<span class="dnk-banner-countdown__label font_13">{dl}</span>'
    + '</span>'
    + '<span class="dnk-banner-countdown__unit">'
    + '<span class="dnk-banner-countdown__value font_48">{hnn}</span>'
    + '<span class="dnk-banner-countdown__label font_13">{hl}</span>'
    + '</span>'
    + '<span class="dnk-banner-countdown__unit">'
    + '<span class="dnk-banner-countdown__value font_48">{mnn}</span>'
    + '<span class="dnk-banner-countdown__label font_13">{ml}</span>'
    + '</span>'
    + '<span class="dnk-banner-countdown__unit">'
    + '<span class="dnk-banner-countdown__value font_48">{snn}</span>'
    + '<span class="dnk-banner-countdown__label font_13">{sl}</span>'
    + '</span>';

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

  function whichLabels(amount) {
    var units = amount % 10;
    var tens = Math.floor((amount % 100) / 10);
    if (amount === 1) {
      return 1;
    }
    if (units >= 2 && units <= 4 && tens !== 1) {
      return 2;
    }
    if (units === 1 && tens !== 1) {
      return 1;
    }

    return 0;
  }

  function regionalOptions() {
    var base = ($.countdown.regionalOptions && $.countdown.regionalOptions.ru) || {};
    var regional = $.extend({}, base);
    regional.labels = messageList('DNK_BANNER_COUNTDOWN_LABELS');
    regional.labels1 = messageList('DNK_BANNER_COUNTDOWN_LABELS1');
    regional.labels2 = messageList('DNK_BANNER_COUNTDOWN_LABELS2');
    regional.whichLabels = whichLabels;

    return regional;
  }

  function arm(el) {
    if (seen.has(el)) {
      return true;
    }

    if (!window.jQuery || !$.fn.countdown || !isVisibleDuplicate(el)) {
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

    try {
      $(items).countdown($.extend({
        until: dateTo,
        format: 'dHMS',
        padZeroes: true,
        layout: layout
      }, regionalOptions()));
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

    if (!window.BX || !BX.Aspro || !BX.Aspro.Loader || !window.arAsproOptions) {
      return;
    }

    pluginsLoading = true;
    loadAttempts += 1;

    BX.Aspro.Loader.once({
      appear: ['.dnk-banner-countdown'],
      add: {
        js: [
          arAsproOptions.SITE_TEMPLATE_PATH + '/js/jquery.plugin.min.js',
          arAsproOptions.SITE_TEMPLATE_PATH + '/js/jquery.countdown.min.js'
        ]
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

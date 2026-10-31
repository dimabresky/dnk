(function () {
  var seen = new WeakSet();
  var watched = new WeakMap();
  var pluginsReady = false;
  var pluginsLoading = false;

  var labels = ['лет', 'месяцев', 'недель', 'дней', 'часов', 'минут', 'секунд'];
  var labels1 = ['год', 'месяц', 'неделя', 'день', 'час', 'минута', 'секунда'];
  var labels2 = ['года', 'месяца', 'недели', 'дня', 'часа', 'минуты', 'секунды'];

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
    if (!value) {
      return null;
    }

    var date = new Date(String(value).replace(/(\d+)\.(\d+)\.(\d+)/, '$3/$2/$1'));
    if (isNaN(date.getTime())) {
      return null;
    }

    return date;
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
    regional.labels = labels;
    regional.labels1 = labels1;
    regional.labels2 = labels2;
    if (typeof regional.whichLabels !== 'function') {
      regional.whichLabels = whichLabels;
    }

    return regional;
  }

  function arm(el) {
    if (seen.has(el)) {
      return;
    }

    seen.add(el);

    var img = el.querySelector('img[data-src]');
    if (img) {
      img.src = img.getAttribute('data-src');
      img.removeAttribute('data-src');
    }

    var items = el.querySelector('.dnk-banner-countdown__items');
    var dateTo = parseEnd(el.getAttribute('data-end'));
    if (!items || !dateTo || !$.fn.countdown) {
      return;
    }

    $(items).countdown({
      until: dateTo,
      format: 'dHMS',
      padZeroes: true,
      layout: layout
    }, regionalOptions());
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

        observer.unobserve(entry.target);
        arm(entry.target);
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
    if (pluginsLoading || pluginsReady) {
      return;
    }

    if (!window.BX || !BX.Aspro || !BX.Aspro.Loader || !window.arAsproOptions) {
      return;
    }

    pluginsLoading = true;

    BX.Aspro.Loader.once({
      appear: ['.dnk-banner-countdown'],
      add: {
        js: [
          arAsproOptions.SITE_TEMPLATE_PATH + '/js/jquery.plugin.min.js',
          arAsproOptions.SITE_TEMPLATE_PATH + '/js/jquery.countdown.min.js',
          arAsproOptions.SITE_TEMPLATE_PATH + '/js/jquery.countdown-ru.min.js'
        ]
      }
    }).then(function () {
      pluginsReady = true;
      scan();
    }).catch(function () {
      pluginsLoading = false;
    });
  }

  BX.addCustomEvent('onInitSlider', function () {
    loadPlugins();
    scan();
  });
  BX.addCustomEvent('onSlideChanges', scan);
  BX.ready(loadPlugins);
})();

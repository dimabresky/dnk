(function (window) {
  'use strict';

  var STORAGE_KEY = 'dnkReviewNoticeAt';

  function dnkReviewNotice(config) {
    if (!config || typeof window.JNoticeSurface !== 'function') {
      return;
    }

    var hours = parseInt(config.intervalHours, 10);
    if (!hours || hours < 1) {
      hours = 12;
    }

    var now = Date.now();
    var last = 0;

    try {
      last = parseInt(window.localStorage.getItem(STORAGE_KEY) || '0', 10) || 0;
    } catch (e) {
      last = 0;
    }

    if (last > 0 && now - last < hours * 3600 * 1000) {
      return;
    }

    var notice = window.JNoticeSurface.get().create({
      closeable: true,
      autoclose: 0,
      type: 'review',
      link: config.link || '',
      content: {
        title: config.title || '',
        detail: config.detail || '',
        image: config.image || '',
      },
    });

    if (!notice) {
      return;
    }

    try {
      window.localStorage.setItem(STORAGE_KEY, String(now));
    } catch (e) {
      // localStorage может быть недоступен
    }
  }

  window.dnkReviewNotice = dnkReviewNotice;
})(window);

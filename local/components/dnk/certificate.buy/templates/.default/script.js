(function () {
  'use strict';

  var TELEPORT_TO = '#dnk-cert-buy-summary-slot';
  var DELIVERY_ANCHOR = 'dnk-cert-buy-delivery';
  var DELIVERY_COURIER = 'courier';
  var DELIVERY_COURIER_RB = 'courier_rb';
  var DELIVERY_PICKUP = 'pickup';
  var PAYMENT_CASH = 'cash_on_delivery';
  var PAYMENT_CARD = 'card_on_delivery';
  var PAYMENT_DEFAULT = PAYMENT_CARD;
  var DELIVERY_FREE_THRESHOLD = 55;
  var DELIVERY_PRICE_COURIER_MINSK = 5;
  var DELIVERY_PRICE_COURIER_RB = 8;
  var SUCCESS_STORAGE_KEY = 'dnk_cert_buy_success_request_id';

  var pickupMapRuntime = {
    map: null,
    placemarks: {},
    initToken: 0,
  };

  /** @type {{promise: Promise<void>|null, status: 'idle'|'pending'|'ok'|'error'}} */
  var yandexMapsLoader = {
    promise: null,
    status: 'idle',
  };

  function qs(root, selector) {
    return root.querySelector(selector);
  }

  function escapeHtmlText(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escapeHtmlAttr(value) {
    return escapeHtmlText(value).replace(/'/g, '&#39;');
  }

  function buildSuccessMessageHtml(root, requestId) {
    var msgTpl = root.getAttribute('data-msg-success') || '';
    var linkHref =
      root.getAttribute('data-msg-success-link-href') || '/personal/certificate_requests/';
    var linkText = root.getAttribute('data-msg-success-link-text') || 'персональную страницу';
    var linkHtml =
      '<a class="dnk-cert-buy__submit-feedback-link" href="' +
      escapeHtmlAttr(linkHref) +
      '">' +
      escapeHtmlText(linkText) +
      '</a>';

    return msgTpl
      .replace(/#REQUEST_ID#/g, escapeHtmlText(String(requestId)))
      .replace(/#LINK#/g, linkHtml);
  }

  function setOrderSubmitVisible(root, visible) {
    var submitBtn = qs(root, '[data-role="submit"]');
    if (!submitBtn) {
      return;
    }
    if (visible) {
      submitBtn.removeAttribute('hidden');
      submitBtn.removeAttribute('aria-hidden');
    } else {
      submitBtn.setAttribute('hidden', 'hidden');
      submitBtn.setAttribute('aria-hidden', 'true');
    }
  }

  function focusSuccessFeedback(root) {
    var el =
      document.getElementById('dnk-cert-buy-success-feedback') ||
      qs(root, '[data-role="submit-feedback"]');
    if (!el || typeof el.scrollIntoView !== 'function') {
      return;
    }
    window.setTimeout(function () {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
  }

  function resetCertificateQuantitiesOnRoot(root, vueVm) {
    var vmOk = vueVm || root.__dnkCertBuyVue;
    if (
      vmOk &&
      vmOk.resetCertificateQuantities &&
      typeof vmOk.resetCertificateQuantities === 'function'
    ) {
      vmOk.resetCertificateQuantities();
    }
  }

  function stashSuccessAndReload(root, requestId, vueVm) {
    try {
      sessionStorage.setItem(SUCCESS_STORAGE_KEY, String(requestId));
    } catch (e) {
      /* ignore */
    }
    resetCertificateQuantitiesOnRoot(root, vueVm);
    window.location.reload();
  }

  function restoreSuccessAfterReload(root, vueVm) {
    var requestId = '';
    try {
      requestId = sessionStorage.getItem(SUCCESS_STORAGE_KEY) || '';
      if (requestId) {
        sessionStorage.removeItem(SUCCESS_STORAGE_KEY);
      }
    } catch (e) {
      /* ignore */
    }
    if (!requestId) {
      return;
    }
    submitFeedback(root, 'success', buildSuccessMessageHtml(root, requestId), {
      html: true,
      skipScroll: true,
    });
    focusSuccessFeedback(root);
    resetCertificateQuantitiesOnRoot(root, vueVm);
  }

  function submitFeedback(root, kind, message, options) {
    var el = qs(root, '[data-role="submit-feedback"]');
    if (!el) {
      return;
    }
    options = options || {};
    el.classList.remove(
      'dnk-cert-buy__submit-feedback--success',
      'dnk-cert-buy__submit-feedback--error'
    );
    var text = typeof message === 'string' ? message.trim() : '';
    if (!text) {
      el.textContent = '';
      el.innerHTML = '';
      el.setAttribute('hidden', 'hidden');
      return;
    }
    el.removeAttribute('hidden');
    if (options.html) {
      el.innerHTML = message;
    } else {
      el.textContent = text;
    }
    if (kind === 'success') {
      el.classList.add('dnk-cert-buy__submit-feedback--success');
    } else {
      el.classList.add('dnk-cert-buy__submit-feedback--error');
    }
    if (!options.skipScroll && typeof el.scrollIntoView === 'function') {
      el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function isCourierDelivery(xmlId) {
    return xmlId === DELIVERY_COURIER || xmlId === DELIVERY_COURIER_RB;
  }

  function calculateDeliveryPrice(deliveryXmlId, subtotal) {
    if (deliveryXmlId === DELIVERY_PICKUP) {
      return 0;
    }
    if (subtotal >= DELIVERY_FREE_THRESHOLD) {
      return 0;
    }
    if (deliveryXmlId === DELIVERY_COURIER_RB) {
      return DELIVERY_PRICE_COURIER_RB;
    }
    if (deliveryXmlId === DELIVERY_COURIER) {
      return DELIVERY_PRICE_COURIER_MINSK;
    }
    return 0;
  }

  function syncAddressFieldVisibility(root, deliveryXmlId) {
    var addressField = qs(root, '[data-role="address-field"]');
    var addressInput = qs(root, 'textarea[name="dnk_cert_address"]');
    var requiredMark = addressField ? qs(addressField, '[data-role="address-required-mark"]') : null;
    if (!addressField) {
      return;
    }
    var showAddress = isCourierDelivery(deliveryXmlId);
    if (showAddress) {
      addressField.removeAttribute('hidden');
      addressField.classList.remove('is-hidden');
      addressField.removeAttribute('aria-hidden');
      if (addressInput) {
        addressInput.required = true;
        addressInput.setAttribute('aria-required', 'true');
      }
      if (requiredMark) {
        requiredMark.removeAttribute('hidden');
        requiredMark.removeAttribute('aria-hidden');
      }
    } else {
      addressField.setAttribute('hidden', 'hidden');
      addressField.classList.add('is-hidden');
      addressField.setAttribute('aria-hidden', 'true');
      if (addressInput) {
        addressInput.required = false;
        addressInput.removeAttribute('aria-required');
        addressInput.value = '';
      }
      if (requiredMark) {
        requiredMark.setAttribute('hidden', 'hidden');
        requiredMark.setAttribute('aria-hidden', 'true');
      }
    }
    var vm = root.__dnkCertBuyVue;
    if (vm) {
      vm.deliveryAddress = '';
    }
  }

  function digits(s) {
    return String(s).replace(/\D+/g, '');
  }

  function formatMoney(amount) {
    var n = Math.round(Number(amount) * 100) / 100;
    return (
      n.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '\u00A0BYN'
    );
  }

  function bootPhoneMask(input) {
    if (!input) {
      return;
    }

    function install() {
      if (input.dataset.dnkCertImask === '1') {
        return true;
      }
      if (typeof IMask === 'undefined') {
        return false;
      }

      IMask(input, {
        mask: '+375 (00) 000-00-00',
        lazy: true,
        placeholderChar: '_',
      });
      input.dataset.dnkCertImask = '1';
      return true;
    }

    if (install()) {
      return;
    }

    var n = 0;
    var t = setInterval(function () {
      n += 1;
      if (install() || n > 80) {
        clearInterval(t);
      }
    }, 40);
  }

  function coerceItemId(raw) {
    var n = parseInt(String(raw), 10);
    return isNaN(n) || n <= 0 ? null : n;
  }

  function storesWithCoords(stores) {
    var out = [];
    for (var i = 0; i < stores.length; i += 1) {
      var s = stores[i];
      if (s && s.lat != null && s.lon != null) {
        out.push(s);
      }
    }
    return out;
  }

  /**
   * Начальный центр и zoom по координатам точек (без захардкоженного города).
   *
   * @param {Array<{lat: number, lon: number}>} geoStores
   * @return {{center: number[], zoom: number}|null}
   */
  function computePickupMapViewport(geoStores) {
    if (!geoStores.length) {
      return null;
    }

    if (geoStores.length === 1) {
      return {
        center: [geoStores[0].lat, geoStores[0].lon],
        zoom: 15,
      };
    }

    var minLat = geoStores[0].lat;
    var maxLat = geoStores[0].lat;
    var minLon = geoStores[0].lon;
    var maxLon = geoStores[0].lon;

    for (var i = 1; i < geoStores.length; i += 1) {
      var s = geoStores[i];
      minLat = Math.min(minLat, s.lat);
      maxLat = Math.max(maxLat, s.lat);
      minLon = Math.min(minLon, s.lon);
      maxLon = Math.max(maxLon, s.lon);
    }

    return {
      center: [(minLat + maxLat) / 2, (minLon + maxLon) / 2],
      zoom: 11,
    };
  }

  /**
   * Подогнать карту так, чтобы были видны все метки.
   *
   * @param {object} map
   * @param {number} geoCount
   */
  function fitPickupMapToStores(map, geoCount) {
    if (!map || geoCount < 1) {
      return;
    }

    if (geoCount === 1) {
      return;
    }

    var bounds = map.geoObjects.getBounds();
    if (!bounds) {
      return;
    }

    map.setBounds(bounds, {
      checkZoomRange: true,
      zoomMargin: 48,
      duration: 0,
    });
  }

  function destroyPickupMap() {
    pickupMapRuntime.placemarks = {};
    if (pickupMapRuntime.map) {
      try {
        pickupMapRuntime.map.destroy();
      } catch (eDestroy) {
        /* ignore */
      }
      pickupMapRuntime.map = null;
    }
    var mapEl = document.getElementById('dnk-cert-buy-pickup-map');
    if (mapEl) {
      mapEl.innerHTML = '';
    }
  }

  function loadYandexMaps(apiKey) {
    if (!apiKey) {
      return Promise.reject(new Error('no api key'));
    }

    if (typeof window.ymaps !== 'undefined' && window.ymaps.ready) {
      return new Promise(function (resolve) {
        window.ymaps.ready(resolve);
      });
    }

    if (yandexMapsLoader.status === 'error') {
      return Promise.reject(new Error('ymaps load error'));
    }

    if (yandexMapsLoader.promise) {
      return yandexMapsLoader.promise;
    }

    yandexMapsLoader.status = 'pending';
    yandexMapsLoader.promise = new Promise(function (resolve, reject) {
      var markFailed = function () {
        var failedScript = document.querySelector('script[data-dnk-yandex-maps="1"]');
        if (failedScript) {
          failedScript.dataset.dnkYandexMapsFailed = '1';
        }
      };

      var failLoad = function (err) {
        yandexMapsLoader.status = 'error';
        yandexMapsLoader.promise = null;
        markFailed();
        reject(err || new Error('ymaps load error'));
      };

      var succeedLoad = function () {
        if (window.ymaps && window.ymaps.ready) {
          window.ymaps.ready(function () {
            yandexMapsLoader.status = 'ok';
            resolve();
          });
        } else {
          failLoad(new Error('ymaps failed'));
        }
      };

      var script = document.querySelector('script[data-dnk-yandex-maps="1"]');
      if (script && script.dataset.dnkYandexMapsFailed === '1') {
        failLoad(new Error('ymaps load error'));
        return;
      }

      if (script && typeof window.ymaps !== 'undefined' && window.ymaps.ready) {
        succeedLoad();
        return;
      }

      if (
        script &&
        (script.readyState === 'complete' || script.readyState === 'loaded') &&
        (typeof window.ymaps === 'undefined' || !window.ymaps.ready)
      ) {
        failLoad(new Error('ymaps load error'));
        return;
      }

      if (!script) {
        script = document.createElement('script');
        script.src =
          'https://api-maps.yandex.ru/2.1/?apikey=' +
          encodeURIComponent(apiKey) +
          '&lang=ru_RU';
        script.async = true;
        script.dataset.dnkYandexMaps = '1';
        document.head.appendChild(script);
      }

      script.addEventListener('load', succeedLoad, { once: true });
      script.addEventListener(
        'error',
        function () {
          failLoad(new Error('ymaps load error'));
        },
        { once: true }
      );
    });

    return yandexMapsLoader.promise;
  }

  function initPickupMap(vm) {
    var mapEl = document.getElementById('dnk-cert-buy-pickup-map');
    if (!mapEl || !vm || vm.deliveryXmlId !== DELIVERY_PICKUP) {
      return;
    }

    pickupMapRuntime.initToken += 1;
    var token = pickupMapRuntime.initToken;

    if (!vm.yandexApiKey) {
      mapEl.innerHTML =
        '<div class="dnk-cert-buy__pickup-map-fallback muted">' +
        (vm.msgs.pickupMapUnavailable || '') +
        '</div>';
      return;
    }

    loadYandexMaps(vm.yandexApiKey)
      .then(function () {
        if (pickupMapRuntime.initToken !== token || vm.deliveryXmlId !== DELIVERY_PICKUP) {
          return;
        }

        destroyPickupMap();

        var geoStores = storesWithCoords(vm.pickupStores);
        if (!geoStores.length) {
          mapEl.innerHTML =
            '<div class="dnk-cert-buy__pickup-map-fallback muted">' +
            (vm.msgs.pickupMapUnavailable || '') +
            '</div>';
          return;
        }

        var viewport = computePickupMapViewport(geoStores);
        if (!viewport) {
          return;
        }

        pickupMapRuntime.map = new window.ymaps.Map(mapEl, {
          center: viewport.center,
          zoom: viewport.zoom,
          controls: ['zoomControl'],
        });

        pickupMapRuntime.placemarks = {};
        for (var i = 0; i < geoStores.length; i += 1) {
          (function (store) {
            var placemark = new window.ymaps.Placemark(
              [store.lat, store.lon],
              {
                balloonContentHeader: store.name || '',
                balloonContentBody:
                  (store.address ? store.address + '<br>' : '') +
                  (store.phone ? store.phone : ''),
              },
              {
                preset: 'islands#redDotIcon',
              }
            );
            placemark.events.add('click', function () {
              vm.selectPickupStore(store.id);
            });
            pickupMapRuntime.map.geoObjects.add(placemark);
            pickupMapRuntime.placemarks[store.id] = placemark;
          })(geoStores[i]);
        }

        fitPickupMapToStores(pickupMapRuntime.map, geoStores.length);
        vm.refreshPickupMapSelection();
      })
      .catch(function () {
        if (pickupMapRuntime.initToken !== token) {
          return;
        }
        mapEl.innerHTML =
          '<div class="dnk-cert-buy__pickup-map-fallback muted">' +
          (vm.msgs.pickupMapUnavailable || '') +
          '</div>';
      });
  }

  var cartPersistTimer = null;
  var CART_PERSIST_DEBOUNCE_MS = 420;

  function clearCertCartPersistSchedule() {
    if (cartPersistTimer) {
      clearTimeout(cartPersistTimer);
      cartPersistTimer = null;
    }
  }

  function persistCartAjax(vm) {
    if (
      typeof BX === 'undefined' ||
      !BX.ajax ||
      !BX.ajax.runComponentAction ||
      !vm ||
      typeof vm.collectSubmitItems !== 'function'
    ) {
      return Promise.resolve(null);
    }
    clearCertCartPersistSchedule();
    var sparse = vm.collectSubmitItems();
    return BX.ajax.runComponentAction('dnk:certificate.buy', 'saveCart', {
      mode: 'class',
      data: {
        payload: JSON.stringify({ items: sparse }),
      },
    });
  }

  function scheduleCertCartPersist(vm) {
    if (
      typeof BX === 'undefined' ||
      !BX.ajax ||
      !BX.ajax.runComponentAction ||
      !vm ||
      typeof vm.collectSubmitItems !== 'function'
    ) {
      return;
    }
    clearCertCartPersistSchedule();
    cartPersistTimer = setTimeout(function () {
      cartPersistTimer = null;
      var sparse = vm.collectSubmitItems();
      BX.ajax
        .runComponentAction('dnk:certificate.buy', 'saveCart', {
          mode: 'class',
          data: {
            payload: JSON.stringify({ items: sparse }),
          },
        })
        .catch(function () {});
    }, CART_PERSIST_DEBOUNCE_MS);
  }

  function collectSubmitContext(root, vueVm) {
    var nameInput = qs(root, 'input[name="dnk_cert_contact_name"]');
    var phoneInput = qs(root, 'input[name="dnk_cert_contact_phone"]');
    var commentTa = qs(root, 'textarea[name="dnk_cert_comment"]');
    var addressTa = qs(root, 'textarea[name="dnk_cert_address"]');
    var contactName = nameInput ? nameInput.value.trim() : '';
    var contactPhone = phoneInput ? phoneInput.value.trim() : '';
    var comment = commentTa ? commentTa.value.trim() : '';
    var address = addressTa ? addressTa.value.trim() : '';
    var vmSubmit = root.__dnkCertBuyVue;
    var collect =
      vmSubmit &&
      vmSubmit.collectSubmitItems &&
      typeof vmSubmit.collectSubmitItems === 'function'
        ? vmSubmit
        : vueVm && vueVm.collectSubmitItems && typeof vueVm.collectSubmitItems === 'function'
          ? vueVm
          : null;
    var items =
      collect && typeof collect.collectSubmitItems === 'function' ? collect.collectSubmitItems() : [];
    var deliveryXmlId = collect && collect.deliveryXmlId ? collect.deliveryXmlId : DELIVERY_COURIER;
    var paymentXmlId = collect && collect.paymentXmlId ? collect.paymentXmlId : PAYMENT_DEFAULT;
    var pickupStoreId = collect && collect.selectedPickupId ? collect.selectedPickupId : null;

    return {
      nameInput: nameInput,
      phoneInput: phoneInput,
      addressInput: addressTa,
      contactName: contactName,
      contactPhone: contactPhone,
      comment: comment,
      address: address,
      collect: collect,
      items: items,
      deliveryXmlId: deliveryXmlId,
      paymentXmlId: paymentXmlId,
      pickupStoreId: pickupStoreId,
    };
  }

  function collectResponseErrors(response) {
    var data = response && response.data ? response.data : {};
    var errors = [];
    if (response && response.errors && response.errors.length) {
      for (var e = 0; e < response.errors.length; e += 1) {
        if (response.errors[e] && response.errors[e].message) {
          errors.push(String(response.errors[e].message));
        }
      }
    }
    if (data.errors && data.errors.length) {
      for (var j = 0; j < data.errors.length; j += 1) {
        errors.push(String(data.errors[j]));
      }
    }
    return errors.filter(Boolean);
  }

  function getRegistrationConsentInput(root) {
    var byId = qs(root, '#dnk-cert-buy-register-consent');
    if (byId) {
      return byId;
    }
    var block = qs(root, '[data-role="registration-consent"]');
    if (!block) {
      return null;
    }
    var licenseName = root.getAttribute('data-license-input-name') || 'licenses_register';
    var byName = qs(block, 'input[name="' + licenseName + '"]');
    if (byName) {
      return byName;
    }
    return qs(block, 'input[type="checkbox"]');
  }

  function isRegistrationConsentChecked(root) {
    var input = getRegistrationConsentInput(root);
    return !!(input && input.checked);
  }

  function getRegistrationConsentErrorMessage(root) {
    return (
      root.getAttribute('data-msg-reg-consent') ||
      'Необходимо согласие с условиями регистрации.'
    );
  }

  function buildRegistrationConsentPost(root) {
    var checked = isRegistrationConsentChecked(root);
    var licenseName = root.getAttribute('data-license-input-name') || 'licenses_register';
    var post = { registrationConsent: checked ? 'Y' : 'N' };
    post[licenseName] = checked ? 'Y' : 'N';
    return post;
  }

  function setRegistrationConsentVisible(root, visible) {
    var block = qs(root, '[data-role="registration-consent"]');
    if (!block) {
      return;
    }
    if (visible) {
      block.removeAttribute('hidden');
    } else {
      block.setAttribute('hidden', 'hidden');
    }
  }

  function setSmsState(root, isOpen, text) {
    var smsBox = qs(root, '[data-role="sms-box"]');
    if (!smsBox) {
      return;
    }
    if (isOpen) {
      smsBox.removeAttribute('hidden');
      setOrderSubmitVisible(root, false);
    } else {
      smsBox.setAttribute('hidden', 'hidden');
      setOrderSubmitVisible(root, true);
    }
    var caption = qs(root, '[data-role="sms-caption"]');
    if (caption && text) {
      caption.textContent = text;
    }
  }

  function bindSubmit(root, vueVm) {
    var btn = qs(root, '[data-role="submit"]');
    if (!btn || !btn.addEventListener) {
      return;
    }
    if (btn.getAttribute('data-dnk-submit-bound') === '1') {
      return;
    }
    btn.setAttribute('data-dnk-submit-bound', '1');

    var pendingAuth = {
      active: false,
      payload: '',
      signedData: '',
      scenario: '',
      phone: '',
      collectVm: null,
    };
    var smsCodeInput = qs(root, 'input[name="dnk_cert_sms_code"]');
    var smsConfirmBtn = qs(root, '[data-role="sms-confirm"]');
    var smsResendBtn = qs(root, '[data-role="sms-resend"]');
    var smsResendTimerId = null;
    var smsResendDefaultLabel = smsResendBtn ? smsResendBtn.textContent : '';

    function getResendIntervalSeconds(fallback) {
      var fromRoot = parseInt(root.getAttribute('data-phone-resend-interval') || '', 10);
      if (!isNaN(fromRoot) && fromRoot > 0) {
        return fromRoot;
      }
      return fallback > 0 ? fallback : 60;
    }

    function startSmsResendCountdown(seconds) {
      if (!smsResendBtn) {
        return;
      }
      var sec = parseInt(seconds, 10);
      if (isNaN(sec) || sec <= 0) {
        smsResendBtn.disabled = false;
        if (smsResendDefaultLabel) {
          smsResendBtn.textContent = smsResendDefaultLabel;
        }
        return;
      }
      if (smsResendTimerId) {
        clearInterval(smsResendTimerId);
        smsResendTimerId = null;
      }
      smsResendBtn.disabled = true;
      var left = sec;
      smsResendBtn.textContent = 'Повторная отправка через ' + left + ' сек.';
      smsResendTimerId = setInterval(function () {
        left -= 1;
        if (left <= 0) {
          clearInterval(smsResendTimerId);
          smsResendTimerId = null;
          smsResendBtn.disabled = false;
          smsResendBtn.textContent = smsResendDefaultLabel;
          return;
        }
        smsResendBtn.textContent = 'Повторная отправка через ' + left + ' сек.';
      }, 1000);
    }

    function syncAuthorizedSession() {
      root.setAttribute('data-is-authorized', '1');
      var authConsents = qs(root, '[data-role="auth-consents"]');
      if (authConsents) {
        authConsents.hidden = true;
      }
      pendingAuth.active = false;
      pendingAuth.payload = '';
      pendingAuth.signedData = '';
      pendingAuth.scenario = '';
      pendingAuth.phone = '';
      setSmsState(root, false);
      if (smsCodeInput) {
        smsCodeInput.value = '';
      }
    }

    function finalizeSuccess(data) {
      syncAuthorizedSession();
      submitFeedback(root, 'success', buildSuccessMessageHtml(root, data.requestId), { html: true });
      resetCertificateQuantitiesOnRoot(root, vueVm);
    }

    function runSubmit(payload, collect) {
      return persistCartAjax(collect)
        .catch(function () {
          return null;
        })
        .then(function () {
          return BX.ajax.runComponentAction('dnk:certificate.buy', 'submit', {
            mode: 'class',
            data: { payload: payload },
          });
        });
    }

    function confirmBySms() {
      if (!pendingAuth.active || !pendingAuth.payload) {
        submitFeedback(root, 'error', 'Сначала запросите SMS-код.');
        return;
      }
      var smsCode = smsCodeInput ? smsCodeInput.value.trim() : '';
      if (!smsCode) {
        submitFeedback(root, 'error', 'Введите код из SMS.');
        if (smsCodeInput) {
          smsCodeInput.focus();
        }
        return;
      }
      if (typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runComponentAction) {
        submitFeedback(root, 'error', 'Не загружены скрипты Битрикс.');
        return;
      }
      if (pendingAuth.scenario === 'register') {
        var regConsentInput = getRegistrationConsentInput(root);
        if (regConsentInput && !regConsentInput.checked) {
          submitFeedback(root, 'error', getRegistrationConsentErrorMessage(root));
          return;
        }
      }

      btn.disabled = true;
      if (smsConfirmBtn) {
        smsConfirmBtn.disabled = true;
      }

      var confirmData = {
        payload: pendingAuth.payload,
        smsCode: smsCode,
        signedData: pendingAuth.signedData,
        scenario: pendingAuth.scenario,
        contactPhone: pendingAuth.phone,
        orderConsent:
          qs(root, 'input[name="orderConsent"]') && qs(root, 'input[name="orderConsent"]').checked
            ? 'Y'
            : 'N',
      };
      var confirmRegConsent = buildRegistrationConsentPost(root);
      for (var confirmKey in confirmRegConsent) {
        if (Object.prototype.hasOwnProperty.call(confirmRegConsent, confirmKey)) {
          confirmData[confirmKey] = confirmRegConsent[confirmKey];
        }
      }

      BX.ajax
        .runComponentAction('dnk:certificate.buy', 'phoneAuthConfirm', {
          mode: 'class',
          data: confirmData,
        })
        .then(
          function (response) {
            btn.disabled = false;
            if (smsConfirmBtn) {
              smsConfirmBtn.disabled = false;
            }
            var data = response && response.data ? response.data : {};
            if (data.success && data.requestId) {
              stashSuccessAndReload(root, data.requestId, pendingAuth.collectVm);
              return;
            }
            if (data.authenticated) {
              runSubmit(pendingAuth.payload, pendingAuth.collectVm)
                .then(function (submitResponse) {
                  btn.disabled = false;
                  if (smsConfirmBtn) {
                    smsConfirmBtn.disabled = false;
                  }
                  var submitData =
                    submitResponse && submitResponse.data ? submitResponse.data : {};
                  if (submitData.success && submitData.requestId) {
                    stashSuccessAndReload(root, submitData.requestId, pendingAuth.collectVm);
                    return;
                  }
                  syncAuthorizedSession();
                  var submitErrors = collectResponseErrors(submitResponse);
                  submitFeedback(
                    root,
                    'error',
                    submitErrors.join(' ') || (root.getAttribute('data-msg-error') || '')
                  );
                })
                .catch(function () {
                  btn.disabled = false;
                  if (smsConfirmBtn) {
                    smsConfirmBtn.disabled = false;
                  }
                  syncAuthorizedSession();
                  submitFeedback(
                    root,
                    'error',
                    root.getAttribute('data-msg-error') || 'Ошибка запроса.'
                  );
                });
              return;
            }
            btn.disabled = false;
            if (smsConfirmBtn) {
              smsConfirmBtn.disabled = false;
            }
            var errors = collectResponseErrors(response);
            submitFeedback(root, 'error', errors.join(' ') || (root.getAttribute('data-msg-error') || ''));
          },
          function () {
            btn.disabled = false;
            if (smsConfirmBtn) {
              smsConfirmBtn.disabled = false;
            }
            submitFeedback(root, 'error', root.getAttribute('data-msg-error') || 'Ошибка запроса.');
          }
        );
    }

    if (smsConfirmBtn && smsConfirmBtn.addEventListener) {
      smsConfirmBtn.addEventListener('click', confirmBySms);
    }

    if (smsResendBtn && smsResendBtn.addEventListener) {
      smsResendBtn.addEventListener('click', function () {
        if (
          !pendingAuth.active ||
          !pendingAuth.signedData ||
          typeof BX === 'undefined' ||
          !BX.ajax ||
          !BX.ajax.runAction
        ) {
          return;
        }
        smsResendBtn.disabled = true;
        BX.ajax
          .runAction('main.phoneAuth.resendCode', { data: { signedData: pendingAuth.signedData } })
          .then(
            function (res) {
              var data = res && res.data ? res.data : {};
              if (data.DATA_SIGN) {
                pendingAuth.signedData = data.DATA_SIGN;
              }
              setSmsState(root, true, 'Код отправлен повторно.');
              var waitSec = parseInt(data.DATE_SEND, 10);
              if (isNaN(waitSec) || waitSec <= 0) {
                waitSec = getResendIntervalSeconds(60);
              }
              startSmsResendCountdown(waitSec);
            },
            function () {
              submitFeedback(root, 'error', 'Не удалось отправить код повторно.');
              startSmsResendCountdown(getResendIntervalSeconds(60));
            }
          );
      });
    }

    btn.addEventListener('click', function () {
      submitFeedback(root, 'error', '');
      clearCertCartPersistSchedule();
      var context = collectSubmitContext(root, vueVm);
      var contactName = context.contactName;
      var contactPhone = context.contactPhone;
      var comment = context.comment;

      if (!contactName.length) {
        submitFeedback(root, 'error', 'Укажите имя.');
        if (context.nameInput) {
          context.nameInput.focus();
        }
        return;
      }

      if (!contactPhone.length || digits(contactPhone).length < 9) {
        submitFeedback(root, 'error', 'Укажите корректный телефон.');
        if (context.phoneInput) {
          context.phoneInput.focus();
        }
        return;
      }

      var collect = context.collect;
      var items = context.items;

      if (!items.length) {
        submitFeedback(root, 'error', 'Выберите количество хотя бы у одного сертификата.');
        return;
      }

      var deliveryXmlId = context.deliveryXmlId;
      var pickupStoreId = context.pickupStoreId;

      if (deliveryXmlId === DELIVERY_PICKUP && !pickupStoreId) {
        submitFeedback(
          root,
          'error',
          (collect && collect.msgs && collect.msgs.pickupRequired) ||
            'Выберите пункт самовывоза.'
        );
        return;
      }

      var address = context.address;
      if (!isCourierDelivery(deliveryXmlId)) {
        address = '';
        if (context.addressInput) {
          context.addressInput.value = '';
        }
      }
      if (isCourierDelivery(deliveryXmlId) && !address.length) {
        submitFeedback(
          root,
          'error',
          (collect && collect.msgs && collect.msgs.addressRequired) ||
            'Укажите адрес доставки.'
        );
        if (context.addressInput) {
          context.addressInput.focus();
        }
        return;
      }

      if (typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runComponentAction) {
        submitFeedback(root, 'error', 'Не загружены скрипты Битрикс.');
        return;
      }

      var payloadObj = {
        items: items,
        contactName: contactName,
        contactPhone: contactPhone,
        comment: comment,
        deliveryXmlId: deliveryXmlId,
        paymentXmlId: context.paymentXmlId || PAYMENT_DEFAULT,
      };
      if (isCourierDelivery(deliveryXmlId) && address) {
        payloadObj.address = address;
      }
      if (deliveryXmlId === DELIVERY_PICKUP && pickupStoreId) {
        payloadObj.pickupStoreId = pickupStoreId;
      }

      var payload = JSON.stringify(payloadObj);
      var isAuthorized = root.getAttribute('data-is-authorized') === '1';
      var phoneAuthEnabled = root.getAttribute('data-phone-auth-enabled') === '1';

      btn.disabled = true;
      clearCertCartPersistSchedule();

      if (isAuthorized) {
        runSubmit(payload, collect)
        .then(
          function (response) {
            btn.disabled = false;
            var data = response && response.data ? response.data : {};
            if (data.success && data.requestId) {
              finalizeSuccess(data);
              return;
            }
            var extra = collectResponseErrors(response);
            submitFeedback(root, 'error', extra.join(' ') || (root.getAttribute('data-msg-error') || ''));
          },
          function () {
            btn.disabled = false;
            submitFeedback(
              root,
              'error',
              root.getAttribute('data-msg-error') || 'Ошибка запроса.'
            );
          }
        );
        return;
      }

      if (!phoneAuthEnabled) {
        btn.disabled = false;
        submitFeedback(
          root,
          'error',
          root.getAttribute('data-msg-phone-auth-off') ||
            'Авторизация по телефону временно недоступна.'
        );
        return;
      }

      if (qs(root, 'input[name="orderConsent"]') && !qs(root, 'input[name="orderConsent"]').checked) {
        btn.disabled = false;
        submitFeedback(root, 'error', 'Необходимо согласие на обработку персональных данных.');
        return;
      }

      persistCartAjax(collect)
        .catch(function () {
          return null;
        })
        .then(function () {
          var startData = {
            contactName: contactName,
            contactPhone: contactPhone,
            payload: payload,
            orderConsent:
              qs(root, 'input[name="orderConsent"]') && qs(root, 'input[name="orderConsent"]').checked
                ? 'Y'
                : 'N',
          };
          var startRegConsent = buildRegistrationConsentPost(root);
          for (var startKey in startRegConsent) {
            if (Object.prototype.hasOwnProperty.call(startRegConsent, startKey)) {
              startData[startKey] = startRegConsent[startKey];
            }
          }
          return BX.ajax.runComponentAction('dnk:certificate.buy', 'phoneAuthStart', {
            mode: 'class',
            data: startData,
          });
        })
        .then(
          function (response) {
            btn.disabled = false;
            var data = response && response.data ? response.data : {};
            if (data.success && data.signedData) {
              pendingAuth.active = true;
              pendingAuth.payload = payload;
              pendingAuth.signedData = String(data.signedData || '');
              pendingAuth.scenario = String(data.scenario || '');
              pendingAuth.phone = contactPhone;
              pendingAuth.collectVm = collect;
              setRegistrationConsentVisible(root, pendingAuth.scenario === 'register');
              setSmsState(root, true, data.phoneMasked ? 'Код отправлен на ' + data.phoneMasked : '');
              var intervalSec = parseInt(data.resendInterval, 10);
              if (isNaN(intervalSec) || intervalSec <= 0) {
                intervalSec = getResendIntervalSeconds(60);
              }
              startSmsResendCountdown(intervalSec);
              if (smsCodeInput) {
                smsCodeInput.focus();
              }
              return;
            }
            var extra = collectResponseErrors(response);
            submitFeedback(root, 'error', extra.join(' ') || (root.getAttribute('data-msg-error') || ''));
          },
          function () {
            btn.disabled = false;
            submitFeedback(root, 'error', root.getAttribute('data-msg-error') || 'Ошибка запроса.');
          }
        );
    });
  }

  function vueTemplate() {
    return (
      '<div>' +
      '  <div class="dnk-cert-buy__grid">' +
      '    <article v-for="item in items" :key="item.id" class="dnk-cert-buy__card">' +
      '      <div class="dnk-cert-buy__card-image-wrap">' +
      '        <img v-if="item.PICTURE" class="dnk-cert-buy__card-image" :src="item.PICTURE" loading="lazy" width="360" height="220" :alt="item.NAME || msgs.imgAltFallback">' +
      '        <div v-else class="dnk-cert-buy__card-image dnk-cert-buy__card-image--stub" aria-hidden="true"></div>' +
      '      </div>' +
      '      <div class="dnk-cert-buy__card-caption font_17">{{ item.NOMINAL_FORMATTED }}</div>' +
      '      <div class="dnk-cert-buy__qty-row">' +
      '        <button type="button" class="dnk-cert-buy__qty-btn btn btn-default" @click.prevent="dec(item.id)" :disabled="qtyOf(item.id) <= 0" :aria-label="ariaDec(item)" tabindex="0">&minus;</button>' +
      '        <input class="dnk-cert-buy__qty-input form-control" type="number" min="0" :max="maxQty" step="1" inputmode="numeric" autocomplete="off"' +
      '          :aria-label="ariaQty(item)"' +
      '          :value="qtyOf(item.id)" @input="onQtyInput(item.id, $event)">' +
      '        <button type="button" class="dnk-cert-buy__qty-btn btn btn-default" @click.prevent="inc(item.id)" :disabled="qtyOf(item.id) >= maxQty" :aria-label="ariaInc(item)" tabindex="0">+</button>' +
      '      </div>' +
      '      <button type="button" class="btn btn-secondary dnk-cert-buy__card-buy" @click.prevent="scrollToCheckout" :disabled="qtyOf(item.id) < 1">{{ msgs.buy }}</button>' +
      '    </article>' +
      '  </div>' +
      '  <div id="' +
      DELIVERY_ANCHOR +
      '" class="dnk-cert-buy__section dnk-cert-buy__section--delivery">' +
      '    <h3 class="dnk-cert-buy__section-title font_20">{{ msgs.deliveryTitle }}</h3>' +
      '    <label class="dnk-cert-buy__inline">' +
      '      <input type="radio" name="dnk_cert_delivery" value="courier" v-model="deliveryXmlId">' +
      '      <span>{{ msgs.deliveryCourier }}</span>' +
      '    </label>' +
      '    <label class="dnk-cert-buy__inline dnk-cert-buy__inline--spaced">' +
      '      <input type="radio" name="dnk_cert_delivery" value="courier_rb" v-model="deliveryXmlId">' +
      '      <span>{{ msgs.deliveryCourierRb }}</span>' +
      '    </label>' +
      '    <label v-if="pickupStores.length" class="dnk-cert-buy__inline dnk-cert-buy__inline--spaced">' +
      '      <input type="radio" name="dnk_cert_delivery" value="pickup" v-model="deliveryXmlId">' +
      '      <span>{{ msgs.deliveryPickup }}</span>' +
      '    </label>' +
      '    <div class="dnk-cert-buy__delivery-notice" role="status" aria-live="polite">' +
      '      <p class="dnk-cert-buy__delivery-notice-tariff font_13 muted">{{ deliveryTariffNotice }}</p>' +
      '      <p v-if="deliveryCurrentNotice" class="dnk-cert-buy__delivery-notice-current font_13">{{ deliveryCurrentNotice }}</p>' +
      '    </div>' +
      '  </div>' +
      '  <div v-show="deliveryXmlId === \'pickup\'" id="dnk-cert-buy-pickup" class="dnk-cert-buy__section dnk-cert-buy__pickup">' +
      '    <h3 class="dnk-cert-buy__section-title font_20">{{ msgs.pickupTitle }}</h3>' +
      '    <p v-if="!pickupStores.length" class="muted">{{ msgs.pickupEmpty }}</p>' +
      '    <div v-else class="dnk-cert-buy__pickup-layout">' +
      '      <div class="dnk-cert-buy__pickup-list" role="list">' +
      '        <button v-for="store in pickupStores" :key="store.id" type="button"' +
      '          class="dnk-cert-buy__pickup-item"' +
      '          :class="{ \'dnk-cert-buy__pickup-item--active\': selectedPickupId === store.id }"' +
      '          role="listitem"' +
      '          :data-pickup-id="store.id"' +
      '          @click.prevent="selectPickupStore(store.id)">' +
      '          <img v-if="store.picture" class="dnk-cert-buy__pickup-thumb" :src="store.picture" loading="lazy" width="80" height="80" :alt="store.name">' +
      '          <div class="dnk-cert-buy__pickup-item-body">' +
      '            <div class="dnk-cert-buy__pickup-name font_15">{{ store.name }}</div>' +
      '            <div v-if="store.address" class="dnk-cert-buy__pickup-meta">{{ store.address }}</div>' +
      '            <div v-if="store.phone" class="dnk-cert-buy__pickup-meta">{{ store.phone }}</div>' +
      '            <div v-if="store.schedule" class="dnk-cert-buy__pickup-meta">{{ store.schedule }}</div>' +
      '          </div>' +
      '        </button>' +
      '      </div>' +
      '      <div id="dnk-cert-buy-pickup-map" class="dnk-cert-buy__pickup-map" aria-hidden="false"></div>' +
      '    </div>' +
      '  </div>' +
      '  <div class="dnk-cert-buy__section">' +
      '    <h3 class="dnk-cert-buy__section-title font_20">{{ msgs.payTitle }}</h3>' +
      '    <label class="dnk-cert-buy__inline">' +
      '      <input type="radio" name="dnk_cert_payment" :value="PAYMENT_CARD" v-model="paymentXmlId">' +
      '      <span>{{ msgs.payCardOnDelivery }}</span>' +
      '    </label>' +
      '    <label class="dnk-cert-buy__inline dnk-cert-buy__inline--spaced">' +
      '      <input type="radio" name="dnk_cert_payment" :value="PAYMENT_CASH" v-model="paymentXmlId">' +
      '      <span>{{ msgs.payCod }}</span>' +
      '    </label>' +
      '  </div>' +
      '  <Teleport to="' +
      TELEPORT_TO +
      '">' +
      '    <div v-if="selectedLines.length" class="dnk-cert-buy__summary muted">' +
      '      <h4 class="dnk-cert-buy__summary-title font_18">{{ msgs.summaryTitle }}</h4>' +
      '      <div class="dnk-cert-buy__summary-list" role="list">' +
      '        <div v-for="row in selectedLines" :key="row.id" class="dnk-cert-buy__summary-line" role="listitem">' +
      '          {{ summaryLineTitle(row.NAME) }} — {{ row.NOMINAL_FORMATTED }} \u00D7 {{ row.qty }} = {{ formatLineSum(row) }}' +
      '        </div>' +
      '      </div>' +
      '      <div class="dnk-cert-buy__summary-meta font_13">' +
      '        <div>{{ msgs.summaryDelivery }}: <strong>{{ currentDeliveryLabel }}</strong></div>' +
      '        <div v-if="summaryAddress">{{ msgs.summaryAddress }}: <strong>{{ summaryAddress }}</strong></div>' +
      '        <div>{{ msgs.summaryPayment }}: <strong>{{ currentPaymentLabel }}</strong></div>' +
      '        <div v-if="deliveryXmlId === \'pickup\' && selectedPickupSummary">{{ msgs.summaryPickup }}: <strong>{{ selectedPickupSummary }}</strong></div>' +
      '      </div>' +
      '      <div class="dnk-cert-buy__summary-totals font_13">' +
      '        <div>{{ msgs.summarySubtotal }}: <strong>{{ formatMoney(subtotal) }}</strong></div>' +
      '        <div>{{ msgs.summaryDeliveryPrice }}: <strong>{{ formattedDeliveryPrice }}</strong></div>' +
      '        <div v-if="deliveryCurrentNotice" class="dnk-cert-buy__summary-delivery-hint muted">{{ deliveryCurrentNotice }}</div>' +
      '      </div>' +
      '      <div class="dnk-cert-buy__summary-total font_15">{{ msgs.summaryTotal }}: <strong>{{ formatMoney(grandTotal) }}</strong></div>' +
      '    </div>' +
      '  </Teleport>' +
      '</div>'
    );
  }

  function mountCatalog(root) {
    var mountEl = document.getElementById('dnk-cert-buy-app');
    if (!mountEl) {
      return null;
    }
    if (typeof Vue === 'undefined' || typeof Vue.createApp !== 'function') {
      console.error('[dnk-cert-buy] Vue не загрузился');
      return null;
    }

    var items = [];
    var msgs = {
      buy: '',
      summaryTitle: '',
      summaryTotal: '',
      imgAltFallback: '',
      qtyAria: '',
    };
    var pickupStores = [];
    var yandexApiKey = '';

    try {
      items = JSON.parse(mountEl.getAttribute('data-catalog') || '[]');
      if (!Array.isArray(items)) {
        items = [];
      }
      var j;
      for (j = 0; j < items.length; j += 1) {
        items[j].id = coerceItemId(items[j].id);
      }
      items = items.filter(function (it) {
        return it.id !== null && it.id !== undefined;
      });
      Object.assign(msgs, JSON.parse(mountEl.getAttribute('data-ui') || '{}'));

      pickupStores = JSON.parse(mountEl.getAttribute('data-pickup-stores') || '[]');
      if (!Array.isArray(pickupStores)) {
        pickupStores = [];
      }
      for (var ps = 0; ps < pickupStores.length; ps += 1) {
        pickupStores[ps].id = coerceItemId(pickupStores[ps].id);
      }
      pickupStores = pickupStores.filter(function (st) {
        return st.id !== null && st.id !== undefined;
      });

      yandexApiKey = String(mountEl.getAttribute('data-yandex-api-key') || '').trim();
    } catch (e) {
      console.error('[dnk-cert-buy]: неверный JSON каталога', e);
      return null;
    }

    var maxQty = parseInt(mountEl.getAttribute('data-max-qty') || '99', 10);
    if (isNaN(maxQty) || maxQty < 1) {
      maxQty = 99;
    }

    var sessQty = {};
    try {
      var parsedSess = JSON.parse(mountEl.getAttribute('data-cart-session') || '{}');
      if (parsedSess && typeof parsedSess === 'object' && !Array.isArray(parsedSess)) {
        sessQty = parsedSess;
      }
    } catch (eParseSess) {
      sessQty = {};
    }

    var initialQty = {};
    var si;
    for (si = 0; si < items.length; si += 1) {
      var k = items[si].id;
      var fromS =
        sessQty[k] !== undefined && sessQty[k] !== null
          ? sessQty[k]
          : sessQty[String(k)] !== undefined && sessQty[String(k)] !== null
            ? sessQty[String(k)]
            : 0;
      var qInit = parseInt(String(fromS), 10);
      if (isNaN(qInit)) {
        qInit = 0;
      }
      qInit = Math.max(0, Math.min(maxQty, qInit));
      initialQty[k] = qInit;
    }

    var app = Vue.createApp({
      data: function () {
        return {
          items: items,
          quantities: initialQty,
          msgs: msgs,
          maxQty: maxQty,
          deliveryXmlId: DELIVERY_COURIER,
          paymentXmlId: PAYMENT_DEFAULT,
          PAYMENT_CASH: PAYMENT_CASH,
          PAYMENT_CARD: PAYMENT_CARD,
          pickupStores: pickupStores,
          selectedPickupId: null,
          yandexApiKey: yandexApiKey,
          deliveryAddress: '',
        };
      },
      computed: {
        selectedLines: function () {
          var self = this;
          var lines = [];
          this.items.forEach(function (it) {
            var q = self.qtyOf(it.id);
            if (q > 0) {
              lines.push({
                id: it.id,
                NAME: it.NAME,
                NOMINAL_FORMATTED: it.NOMINAL_FORMATTED,
                NOMINAL: it.NOMINAL,
                qty: q,
              });
            }
          });
          return lines;
        },
        subtotal: function () {
          return Math.round(
            this.selectedLines.reduce(function (acc, row) {
              return acc + row.NOMINAL * row.qty;
            }, 0) * 100
          ) / 100;
        },
        deliveryPrice: function () {
          return calculateDeliveryPrice(this.deliveryXmlId, this.subtotal);
        },
        grandTotal: function () {
          return Math.round((this.subtotal + this.deliveryPrice) * 100) / 100;
        },
        formattedDeliveryPrice: function () {
          if (this.deliveryPrice <= 0) {
            return this.msgs.deliveryFree || 'бесплатно';
          }
          return this.formatMoney(this.deliveryPrice);
        },
        deliveryTariffNotice: function () {
          if (this.deliveryXmlId === DELIVERY_PICKUP) {
            return this.msgs.deliveryNoticePickup || '';
          }
          if (this.deliveryXmlId === DELIVERY_COURIER_RB) {
            return this.msgs.deliveryNoticeCourierRb || '';
          }
          return this.msgs.deliveryNoticeCourier || '';
        },
        deliveryCurrentNotice: function () {
          if (this.subtotal <= 0) {
            return '';
          }
          if (this.deliveryPrice <= 0) {
            return this.msgs.deliveryNoticeCurrentFree || '';
          }
          var tpl = this.msgs.deliveryNoticeCurrentPaid || '';
          if (!tpl) {
            return '';
          }
          return tpl.replace('#PRICE#', this.formatMoney(this.deliveryPrice));
        },
        currentDeliveryLabel: function () {
          if (this.deliveryXmlId === DELIVERY_PICKUP) {
            return this.msgs.deliveryPickup || '';
          }
          if (this.deliveryXmlId === DELIVERY_COURIER_RB) {
            return this.msgs.deliveryCourierRb || '';
          }
          return this.msgs.deliveryCourier || '';
        },
        currentPaymentLabel: function () {
          if (this.paymentXmlId === PAYMENT_CASH) {
            return this.msgs.payCod || '';
          }
          return this.msgs.payCardOnDelivery || '';
        },
        summaryAddress: function () {
          if (!isCourierDelivery(this.deliveryXmlId)) {
            return '';
          }
          return String(this.deliveryAddress || '').trim();
        },
        selectedPickupStore: function () {
          var self = this;
          if (!self.selectedPickupId) {
            return null;
          }
          for (var i = 0; i < self.pickupStores.length; i += 1) {
            if (self.pickupStores[i].id === self.selectedPickupId) {
              return self.pickupStores[i];
            }
          }
          return null;
        },
        selectedPickupSummary: function () {
          var store = this.selectedPickupStore;
          if (!store) {
            return '';
          }
          var parts = [store.name || ''];
          if (store.address) {
            parts.push(store.address);
          }
          return parts.filter(Boolean).join(', ');
        },
      },
      methods: {
        qtyOf: function (id) {
          var k = coerceItemId(id);
          if (k === null) {
            return 0;
          }
          var dict = this.quantities;
          var raw = dict[k];
          if (raw === undefined) {
            raw = dict[String(k)];
          }
          var n = parseInt(String(raw), 10);
          if (isNaN(n)) {
            return 0;
          }
          if (n < 0) {
            return 0;
          }
          if (n > this.maxQty) {
            return this.maxQty;
          }
          return n;
        },
        inc: function (id) {
          var k = coerceItemId(id);
          if (k === null) {
            return;
          }
          this.quantities[k] = Math.min(this.maxQty, this.qtyOf(k) + 1);
        },
        dec: function (id) {
          var k = coerceItemId(id);
          if (k === null) {
            return;
          }
          this.quantities[k] = Math.max(0, this.qtyOf(k) - 1);
        },
        onQtyInput: function (id, evt) {
          var k = coerceItemId(id);
          if (k === null) {
            return;
          }
          var v = parseInt(String(evt.target.value), 10);
          if (isNaN(v)) {
            this.quantities[k] = 0;
            return;
          }
          this.quantities[k] = Math.max(0, Math.min(this.maxQty, v));
        },
        lineSumNumeric: function (row) {
          return Math.round(row.NOMINAL * row.qty * 100) / 100;
        },
        formatLineSum: function (row) {
          return formatMoney(this.lineSumNumeric(row));
        },
        formatMoney: function (amount) {
          return formatMoney(amount);
        },
        summaryLineTitle: function (name) {
          return String(name || '').replace(/^[\s\u2022\u2023\u2043\u00B7\u25CF●•*-]+/, '').trim();
        },
        ariaQty: function (item) {
          return this.msgs.qtyAria + ': ' + (item.NAME || '');
        },
        ariaInc: function (item) {
          return 'Увеличить: ' + (item.NAME || '');
        },
        ariaDec: function (item) {
          return 'Уменьшить: ' + (item.NAME || '');
        },
        scrollToCheckout: function () {
          Vue.nextTick(function () {
            var el = document.getElementById(DELIVERY_ANCHOR);
            if (el && typeof el.scrollIntoView === 'function') {
              el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          });
        },
        collectSubmitItems: function () {
          var self = this;
          var out = [];
          this.items.forEach(function (it) {
            var q = self.qtyOf(it.id);
            if (q >= 1) {
              out.push({ id: it.id, qty: q });
            }
          });
          return out;
        },
        resetCertificateQuantities: function () {
          var empty = {};
          for (var i = 0; i < this.items.length; i += 1) {
            var cid = coerceItemId(this.items[i].id);
            if (cid !== null) {
              empty[cid] = 0;
            }
          }
          this.quantities = empty;
          this.deliveryXmlId = DELIVERY_COURIER;
          this.selectedPickupId = null;
          this.deliveryAddress = '';
          destroyPickupMap();
          var rootReset = document.getElementById('dnk-cert-buy-root');
          if (rootReset) {
            syncAddressFieldVisibility(rootReset, DELIVERY_COURIER);
            var addressTaReset = rootReset.querySelector('textarea[name="dnk_cert_address"]');
            if (addressTaReset) {
              addressTaReset.value = '';
            }
          }
        },
        selectPickupStore: function (id) {
          var storeId = coerceItemId(id);
          if (storeId === null) {
            return;
          }
          this.selectedPickupId = storeId;
          this.refreshPickupMapSelection();
          var self = this;
          Vue.nextTick(function () {
            var row = document.querySelector(
              '.dnk-cert-buy__pickup-item[data-pickup-id="' + storeId + '"]'
            );
            if (row && typeof row.scrollIntoView === 'function') {
              row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            var store = self.selectedPickupStore;
            if (
              store &&
              store.lat != null &&
              store.lon != null &&
              pickupMapRuntime.map &&
              pickupMapRuntime.map.setCenter
            ) {
              pickupMapRuntime.map.setCenter([store.lat, store.lon], 15, { duration: 300 });
              var pm = pickupMapRuntime.placemarks[storeId];
              if (pm && pm.balloon && pm.balloon.open) {
                pm.balloon.open();
              }
            }
          });
        },
        refreshPickupMapSelection: function () {
          var selected = this.selectedPickupId;
          var keys = Object.keys(pickupMapRuntime.placemarks);
          for (var i = 0; i < keys.length; i += 1) {
            var id = parseInt(keys[i], 10);
            var pm = pickupMapRuntime.placemarks[keys[i]];
            if (!pm || !pm.options || !pm.options.set) {
              continue;
            }
            pm.options.set(
              'preset',
              id === selected ? 'islands#redIcon' : 'islands#redDotIcon'
            );
          }
        },
        schedulePickupMapInit: function () {
          var self = this;
          destroyPickupMap();
          Vue.nextTick(function () {
            if (self.deliveryXmlId === DELIVERY_PICKUP) {
              initPickupMap(self);
            }
          });
        },
      },
      mounted: function () {
        var self = this;
        if (self.deliveryXmlId === DELIVERY_PICKUP && !self.pickupStores.length) {
          self.deliveryXmlId = DELIVERY_COURIER;
        }
        Vue.nextTick(function () {
          bootPhoneMask(qs(root, '.js-dnk-cert-phone'));
          syncAddressFieldVisibility(root, self.deliveryXmlId);
          var addressTa = qs(root, 'textarea[name="dnk_cert_address"]');
          if (addressTa && !addressTa.dataset.dnkCertAddressBound) {
            addressTa.dataset.dnkCertAddressBound = '1';
            addressTa.addEventListener('input', function () {
              self.deliveryAddress = String(addressTa.value || '').trim();
            });
          }
          setTimeout(function () {
            bootPhoneMask(qs(root, '.js-dnk-cert-phone'));
          }, 100);
          if (self.deliveryXmlId === DELIVERY_PICKUP && self.pickupStores.length) {
            self.schedulePickupMapInit();
          }
        });
      },
      watch: {
        quantities: {
          deep: true,
          handler: function scheduleFromVue() {
            scheduleCertCartPersist(this);
          },
        },
        deliveryXmlId: function (nextVal) {
          var rootWatch = document.getElementById('dnk-cert-buy-root');
          if (rootWatch) {
            syncAddressFieldVisibility(rootWatch, nextVal);
          }
          if (nextVal === DELIVERY_PICKUP) {
            if (!this.pickupStores.length) {
              this.deliveryXmlId = DELIVERY_COURIER;
              return;
            }
            this.schedulePickupMapInit();
          } else {
            this.selectedPickupId = null;
            destroyPickupMap();
          }
        },
      },
      template: vueTemplate(),
    });

    try {
      var mounted = app.mount('#dnk-cert-buy-app');
      root.__dnkCertBuyVue = mounted;
      return mounted;
    } catch (e) {
      console.error('[dnk-cert-buy]: ошибка монтирования Vue', e);
      return null;
    }
  }

  function tryInit(root, attempt) {
    var n = typeof attempt === 'number' ? attempt : 0;
    if (
      typeof Vue !== 'undefined' &&
      Vue.createApp &&
      document.getElementById('dnk-cert-buy-app')
    ) {
      var vmOk = mountCatalog(root);
      if (vmOk) {
        bindSubmit(root, vmOk);
        restoreSuccessAfterReload(root, vmOk);
      }
      return;
    }
    if (n >= 160) {
      console.error('[dnk-cert-buy] Vue не успел загрузиться');

      return;
    }
    setTimeout(function () {
      tryInit(root, n + 1);
    }, 50);
  }

  function init() {
    var root = document.getElementById('dnk-cert-buy-root');
    if (!root) {
      return;
    }

    tryInit(root, 0);
  }

  if (typeof BX !== 'undefined' && BX.ready) {
    BX.ready(init);
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

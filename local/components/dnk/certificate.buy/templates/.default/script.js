(function () {
  'use strict';

  var TELEPORT_TO = '#dnk-cert-buy-summary-slot';
  var CONTACT_ANCHOR = 'dnk-cert-buy-contact-anchor';
  var DELIVERY_COURIER = 'courier';
  var DELIVERY_PICKUP = 'pickup';

  var pickupMapRuntime = {
    map: null,
    placemarks: {},
    initToken: 0,
  };

  function qs(root, selector) {
    return root.querySelector(selector);
  }

  function submitFeedback(root, kind, message) {
    var el = qs(root, '[data-role="submit-feedback"]');
    if (!el) {
      return;
    }
    el.classList.remove(
      'dnk-cert-buy__submit-feedback--success',
      'dnk-cert-buy__submit-feedback--error'
    );
    var text = typeof message === 'string' ? message.trim() : '';
    if (!text) {
      el.textContent = '';
      el.setAttribute('hidden', 'hidden');
      return;
    }
    el.removeAttribute('hidden');
    el.textContent = text;
    if (kind === 'success') {
      el.classList.add('dnk-cert-buy__submit-feedback--success');
    } else {
      el.classList.add('dnk-cert-buy__submit-feedback--error');
    }
    if (typeof el.scrollIntoView === 'function') {
      el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
    return new Promise(function (resolve, reject) {
      if (!apiKey) {
        reject(new Error('no api key'));
        return;
      }
      if (typeof window.ymaps !== 'undefined' && window.ymaps.ready) {
        window.ymaps.ready(resolve);
        return;
      }
      var existing = document.querySelector('script[data-dnk-yandex-maps="1"]');
      if (existing) {
        existing.addEventListener('load', function () {
          if (window.ymaps && window.ymaps.ready) {
            window.ymaps.ready(resolve);
          } else {
            reject(new Error('ymaps failed'));
          }
        });
        existing.addEventListener('error', reject);
        return;
      }
      var script = document.createElement('script');
      script.src =
        'https://api-maps.yandex.ru/2.1/?apikey=' +
        encodeURIComponent(apiKey) +
        '&lang=ru_RU';
      script.async = true;
      script.dataset.dnkYandexMaps = '1';
      script.onload = function () {
        if (window.ymaps && window.ymaps.ready) {
          window.ymaps.ready(resolve);
        } else {
          reject(new Error('ymaps failed'));
        }
      };
      script.onerror = function () {
        reject(new Error('ymaps load error'));
      };
      document.head.appendChild(script);
    });
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

  function bindSubmit(root, vueVm) {
    var btn = qs(root, '[data-role="submit"]');
    if (!btn || !btn.addEventListener) {
      return;
    }
    if (btn.getAttribute('data-dnk-submit-bound') === '1') {
      return;
    }
    btn.setAttribute('data-dnk-submit-bound', '1');

    btn.addEventListener('click', function () {
      submitFeedback(root, 'error', '');
      clearCertCartPersistSchedule();

      var nameInput = qs(root, 'input[name="dnk_cert_contact_name"]');
      var phoneInput = qs(root, 'input[name="dnk_cert_contact_phone"]');
      var commentTa = qs(root, 'textarea[name="dnk_cert_comment"]');

      var contactName = nameInput ? nameInput.value.trim() : '';
      var contactPhone = phoneInput ? phoneInput.value.trim() : '';
      var comment = commentTa ? commentTa.value.trim() : '';

      if (!contactName.length) {
        submitFeedback(root, 'error', 'Укажите имя.');
        if (nameInput) {
          nameInput.focus();
        }
        return;
      }

      if (!contactPhone.length || digits(contactPhone).length < 9) {
        submitFeedback(root, 'error', 'Укажите корректный телефон.');
        if (phoneInput) {
          phoneInput.focus();
        }
        return;
      }

      var vmSubmit = root.__dnkCertBuyVue;
      var collect =
        vmSubmit &&
        vmSubmit.collectSubmitItems &&
        typeof vmSubmit.collectSubmitItems === 'function'
          ? vmSubmit
          : vueVm &&
              vueVm.collectSubmitItems &&
              typeof vueVm.collectSubmitItems === 'function'
            ? vueVm
            : null;
      var items =
        collect && typeof collect.collectSubmitItems === 'function'
          ? collect.collectSubmitItems()
          : [];

      if (!items.length) {
        submitFeedback(root, 'error', 'Выберите количество хотя бы у одного сертификата.');
        return;
      }

      var deliveryXmlId =
        collect && collect.deliveryXmlId ? collect.deliveryXmlId : DELIVERY_COURIER;
      var pickupStoreId =
        collect && collect.selectedPickupId ? collect.selectedPickupId : null;

      if (deliveryXmlId === DELIVERY_PICKUP && !pickupStoreId) {
        submitFeedback(
          root,
          'error',
          (collect && collect.msgs && collect.msgs.pickupRequired) ||
            'Выберите пункт самовывоза.'
        );
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
        paymentXmlId: 'cash_on_delivery',
      };
      if (deliveryXmlId === DELIVERY_PICKUP && pickupStoreId) {
        payloadObj.pickupStoreId = pickupStoreId;
      }

      var payload = JSON.stringify(payloadObj);

      btn.disabled = true;
      clearCertCartPersistSchedule();

      persistCartAjax(collect)
        .catch(function () {
          return null;
        })
        .then(function () {
          return BX.ajax.runComponentAction('dnk:certificate.buy', 'submit', {
            mode: 'class',
            data: {
              payload: payload,
            },
          });
        })
        .then(
          function (response) {
            btn.disabled = false;
            var data = response && response.data ? response.data : {};
            var ok = !!(data.success && data.requestId);
            var msgTpl = ok
              ? root.getAttribute('data-msg-success') || ''
              : root.getAttribute('data-msg-error') || '';
            var extra = [];

            if (response && response.errors && response.errors.length) {
              for (var e = 0; e < response.errors.length; e += 1) {
                if (response.errors[e] && response.errors[e].message) {
                  extra.push(String(response.errors[e].message));
                }
              }
            }
            if (data.errors && data.errors.length) {
              for (var j = 0; j < data.errors.length; j += 1) {
                extra.push(String(data.errors[j]));
              }
            }

            if (ok) {
              submitFeedback(
                root,
                'success',
                msgTpl.replace('#REQUEST_ID#', String(data.requestId))
              );
              var vmOk = root.__dnkCertBuyVue;
              if (
                vmOk &&
                vmOk.resetCertificateQuantities &&
                typeof vmOk.resetCertificateQuantities === 'function'
              ) {
                vmOk.resetCertificateQuantities();
              }
            } else {
              submitFeedback(root, 'error', extra.filter(Boolean).join(' ') || msgTpl);
            }
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
      '  <div class="dnk-cert-buy__section">' +
      '    <h3 class="dnk-cert-buy__section-title font_20">{{ msgs.deliveryTitle }}</h3>' +
      '    <label class="dnk-cert-buy__inline">' +
      '      <input type="radio" name="dnk_cert_delivery" value="courier" v-model="deliveryXmlId">' +
      '      <span>{{ msgs.deliveryCourier }}</span>' +
      '    </label>' +
      '    <label v-if="pickupStores.length" class="dnk-cert-buy__inline dnk-cert-buy__inline--spaced">' +
      '      <input type="radio" name="dnk_cert_delivery" value="pickup" v-model="deliveryXmlId">' +
      '      <span>{{ msgs.deliveryPickup }}</span>' +
      '    </label>' +
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
      '      <input type="radio" name="dnk_cert_payment" value="cash_on_delivery" checked disabled>' +
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
      '        <div>{{ msgs.summaryPayment }}: <strong>{{ msgs.payCod }}</strong></div>' +
      '        <div v-if="deliveryXmlId === \'pickup\' && selectedPickupSummary">{{ msgs.summaryPickup }}: <strong>{{ selectedPickupSummary }}</strong></div>' +
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
          pickupStores: pickupStores,
          selectedPickupId: null,
          yandexApiKey: yandexApiKey,
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
        grandTotal: function () {
          return this.selectedLines.reduce(function (acc, row) {
            return acc + row.NOMINAL * row.qty;
          }, 0);
        },
        currentDeliveryLabel: function () {
          if (this.deliveryXmlId === DELIVERY_PICKUP) {
            return this.msgs.deliveryPickup || '';
          }
          return this.msgs.deliveryCourier || '';
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
            var el = document.getElementById(CONTACT_ANCHOR);
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
          destroyPickupMap();
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

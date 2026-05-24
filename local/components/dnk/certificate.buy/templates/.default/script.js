(function () {
  'use strict';

  var TELEPORT_TO = '#dnk-cert-buy-summary-slot';
  var CONTACT_ANCHOR = 'dnk-cert-buy-contact-anchor';

  function qs(root, selector) {
    return root.querySelector(selector);
  }

  /**
   * Сообщения об успехе / ошибках валидации / ответе сервера над кнопкой оформления.
   * @param {'success'|'error'} kind — при непустом message; если message пустой, kind игнорируется
   */
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

  /** Единый числовой id элемента каталога (ключи quantities всегда number). */
  function coerceItemId(raw) {
    var n = parseInt(String(raw), 10);
    return isNaN(n) || n <= 0 ? null : n;
  }

  var cartPersistTimer = null;
  var CART_PERSIST_DEBOUNCE_MS = 420;

  function clearCertCartPersistSchedule() {
    if (cartPersistTimer) {
      clearTimeout(cartPersistTimer);
      cartPersistTimer = null;
    }
  }

  /** Сохранить корзину в сессию (немедленно), для вызова перед submit без гонки с debounce. */
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

  /** Debounced синхронизация корзины с сессией при изменении количеств во Vue. */
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
    /** Не навешивать дважды (повтор BX.ready / кеш с повторным init). */
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

      /** Актуальный экземпляр Vue на корне (не замыкание со старым vm). */
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

      if (typeof BX === 'undefined' || !BX.ajax || !BX.ajax.runComponentAction) {
        submitFeedback(root, 'error', 'Не загружены скрипты Битрикс.');
        return;
      }

      var payload = JSON.stringify({
        items: items,
        contactName: contactName,
        contactPhone: contactPhone,
        comment: comment,
        deliveryXmlId: 'courier',
        paymentXmlId: 'cash_on_delivery',
      });

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
        /** Сброс выбора после успешной заявки (повтор без нового набора невозможен). */
        resetCertificateQuantities: function () {
          var empty = {};
          for (var i = 0; i < this.items.length; i += 1) {
            var cid = coerceItemId(this.items[i].id);
            if (cid !== null) {
              empty[cid] = 0;
            }
          }
          this.quantities = empty;
        },
      },
      mounted: function () {
        Vue.nextTick(function () {
          bootPhoneMask(qs(root, '.js-dnk-cert-phone'));
          setTimeout(function () {
            bootPhoneMask(qs(root, '.js-dnk-cert-phone'));
          }, 100);
        });
      },
      watch: {
        quantities: {
          deep: true,
          handler: function scheduleFromVue() {
            scheduleCertCartPersist(this);
          },
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

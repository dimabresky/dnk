/**
 * Diginetica AnyQuery window.digiLayer bridge for DNK (desktop + mobile).
 * Spec: cart / favorites / compare state and mutations via /local/ajax/digi_layer.php
 */
(function (window) {
  'use strict';

  if (window.digiLayer && window.digiLayer.__dnkReady) {
    return;
  }

  var ENDPOINT = '/local/ajax/digi_layer.php';

  function ensureCounters() {
    if (typeof window.arAsproCounters !== 'object' || !window.arAsproCounters) {
      window.arAsproCounters = {};
    }
  }

  function applySnapshot(data, refreshUi) {
    if (!data || typeof data !== 'object') {
      return;
    }

    ensureCounters();

    if (data.cart && typeof data.cart === 'object') {
      if (typeof window.arAsproCounters.BASKET !== 'object' || !window.arAsproCounters.BASKET) {
        window.arAsproCounters.BASKET = {};
      }
      window.arAsproCounters.BASKET.ITEMS = data.cart;
      window.arAsproCounters.BASKET.COUNT = Object.keys(data.cart).length;
    }

    if (Array.isArray(data.favorites)) {
      if (typeof window.arAsproCounters.FAVORITE !== 'object' || !window.arAsproCounters.FAVORITE) {
        window.arAsproCounters.FAVORITE = {};
      }
      var favItems = {};
      data.favorites.forEach(function (id) {
        favItems[id] = id;
      });
      window.arAsproCounters.FAVORITE.ITEMS = favItems;
      window.arAsproCounters.FAVORITE.COUNT = data.favorites.length;
    }

    if (Array.isArray(data.compares)) {
      if (typeof window.arAsproCounters.COMPARE !== 'object' || !window.arAsproCounters.COMPARE) {
        window.arAsproCounters.COMPARE = {};
      }
      var cmpItems = {};
      data.compares.forEach(function (id) {
        cmpItems[id] = id;
      });
      window.arAsproCounters.COMPARE.ITEMS = cmpItems;
      window.arAsproCounters.COMPARE.COUNT = data.compares.length;
    }

    if (!refreshUi) {
      return;
    }

    try {
      if (typeof window.JItemActionBasket === 'function') {
        if (typeof window.JItemActionBasket.markBadges === 'function') {
          window.JItemActionBasket.markBadges();
        }
        if (typeof window.JItemActionBasket.markItems === 'function') {
          window.JItemActionBasket.markItems();
        }
      }
      if (typeof window.JItemActionFavorite === 'function') {
        if (typeof window.JItemActionFavorite.markBadges === 'function') {
          window.JItemActionFavorite.markBadges();
        }
        if (typeof window.JItemActionFavorite.markItems === 'function') {
          window.JItemActionFavorite.markItems();
        }
      }
      if (typeof window.JItemActionCompare === 'function') {
        if (typeof window.JItemActionCompare.markBadges === 'function') {
          window.JItemActionCompare.markBadges();
        }
        if (typeof window.JItemActionCompare.markItems === 'function') {
          window.JItemActionCompare.markItems();
        }
      }
      if (typeof window.reloadCounters === 'function') {
        window.reloadCounters();
      } else if (typeof window.JItemAction === 'function' && typeof window.JItemAction.reload === 'function') {
        window.JItemAction.reload();
      }
    } catch (e) {
      // badges are best-effort; digiLayer result must still resolve
    }
  }

  function request(action, offerId, amount, options) {
    options = options || {};
    var refreshUi = options.refreshUi === true;

    return new Promise(function (resolve, reject) {
      if (typeof BX === 'undefined' || typeof BX.ajax !== 'function' || typeof BX.bitrix_sessid !== 'function') {
        reject(new Error('Bitrix JS API is not available'));
        return;
      }

      var data = {
        sessid: BX.bitrix_sessid(),
        action: action,
      };

      if (typeof offerId !== 'undefined' && offerId !== null && offerId !== '') {
        data.offer_id = offerId;
      }

      if (typeof amount !== 'undefined' && amount !== null && amount !== '') {
        data.amount = amount;
      }

      BX.ajax({
        url: ENDPOINT,
        method: 'POST',
        dataType: 'json',
        data: data,
        onsuccess: function (response) {
          if (!response || !response.success) {
            reject(new Error((response && response.error) || 'digiLayer request failed'));
            return;
          }
          applySnapshot(response, refreshUi);
          resolve(response.result);
        },
        onfailure: function () {
          reject(new Error('digiLayer network error'));
        },
      });
    });
  }

  function normalizeAmount(amount) {
    if (typeof amount === 'undefined' || amount === null || amount === '') {
      return 1;
    }
    var n = Number(amount);
    return isNaN(n) ? 1 : n;
  }

  window.digiLayer = {
    __dnkReady: true,

    addToCart: function (offerId, amount) {
      return request('addToCart', offerId, normalizeAmount(amount), { refreshUi: true });
    },

    removeFromCart: function (offerId, amount) {
      return request('removeFromCart', offerId, normalizeAmount(amount), { refreshUi: true });
    },

    cartState: function () {
      return request('cartState');
    },

    addToFavorites: function (offerId) {
      return request('addToFavorites', offerId, null, { refreshUi: true });
    },

    removeFromFavorites: function (offerId) {
      return request('removeFromFavorites', offerId, null, { refreshUi: true });
    },

    favoritesState: function () {
      return request('favoritesState');
    },

    addToCompare: function (offerId) {
      return request('addToCompare', offerId, null, { refreshUi: true });
    },

    removeFromCompare: function (offerId) {
      return request('removeFromCompare', offerId, null, { refreshUi: true });
    },

    comparesState: function () {
      return request('comparesState');
    },

    /** Alias from Diginetica docs table (compareState). */
    compareState: function () {
      return request('compareState');
    },
  };
})(window);

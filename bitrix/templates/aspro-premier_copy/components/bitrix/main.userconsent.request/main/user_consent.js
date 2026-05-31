(function () {
  function UserConsentControl(params) {
    this.caller = params.caller;
    this.formNode = params.formNode;
    this.controlNode = params.controlNode;
    this.inputNode = params.inputNode;
    this.config = params.config;
    this.saved = false;
  }
  UserConsentControl.prototype = {};

  BX.UserConsent = {
    msg: {
      title: "MAIN_USER_CONSENT_REQUEST_TITLE",
      btnAccept: "MAIN_USER_CONSENT_REQUEST_BTN_ACCEPT",
      btnReject: "MAIN_USER_CONSENT_REQUEST_BTN_REJECT",
      loading: "MAIN_USER_CONSENT_REQUEST_LOADING",
      errTextLoad: "MAIN_USER_CONSENT_REQUEST_ERR_TEXT_LOAD",
    },
    events: {
      save: "main-user-consent-request-save",
      refused: "main-user-consent-request-refused",
      accepted: "main-user-consent-request-accepted",
      afterAccepted: "main-user-consent-request-after-accepted",
    },
    current: null,
    autoSave: false,
    isFormSubmitted: false,
    attributeControl: "data-bx-user-consent",
    items: [],
    queue: 0,
    hideEventInited: false,
    load: function (context) {
      var item = this.find(context)[0];
      if (!item) {
        return null;
      }

      this.bind(item);
      return item;
    },
    loadAll: function (context, limit) {
      var items = this.find(context, limit);
      if (items.length > 0) {
        items.forEach(this.bind, this);
        this.items = this.items.concat(items);
      }
    },
    getItems: function () {
      return this.items;
    },
    loadFromForms: function () {
      var formNodes = document.getElementsByTagName("FORM");
      formNodes = BX.convert.nodeListToArray(formNodes);
      formNodes.forEach(this.loadAll, this);
    },
    find: function (context) {
      if (!context) {
        return [];
      }

      var controlNodes = context.querySelectorAll("[" + this.attributeControl + "]:not(.consent-inited)");
      controlNodes = BX.convert.nodeListToArray(controlNodes);
      return controlNodes.map(this.createItem.bind(this, context)).filter(function (item) {
        return !!item;
      });
    },
    bind: function (item) {
      if (item.controlNode.classList.contains("consent-inited")) {
        return;
      }

      if (item.config.submitEventName) {
        BX.addCustomEvent(item.config.submitEventName, this.onSubmit.bind(this, item));
      } else if (item.formNode) {
        var formItems = BX.data(item.formNode, "userConsentItems");
        if (!formItems) {
          formItems = [];
          BX.data(item.formNode, "userConsentItems", formItems);
          BX.bind(item.formNode, "submit", BX.proxy(this.onFormSubmit, this));
        }
        if (formItems.indexOf(item) === -1) {
          formItems.push(item);
        }
      }

      BX.bind(item.controlNode, "click", this.onClick.bind(this, item));
      item.controlNode.classList.add("consent-inited");

      if (!this.hideEventInited) {
        this.hideEventInited = true;
        BX.addCustomEvent("onUserConsentHide", () => {
          if (this.popup.nodes.container) {
            this.popup.hide();

            BX.UserConsent.queue = 0;
          }
        });
      }
    },
    createItem: function (context, controlNode) {
      var inputNode = controlNode.querySelector('input[type="checkbox"]');
      if (!inputNode) {
        return;
      }

      try {
        var config = JSON.parse(controlNode.getAttribute(this.attributeControl));
        var parameters = {
          formNode: null,
          controlNode: controlNode,
          inputNode: inputNode,
          config: config,
        };

        if (context.tagName == "FORM") {
          parameters.formNode = context;
        } else {
          parameters.formNode = BX.findParent(inputNode, { tagName: "FORM" });
        }

        parameters.caller = this;
        return new UserConsentControl(parameters);
      } catch (e) {
        return null;
      }
    },
    onClick: function (item, e) {
      if (item.config.url) {
        if (item.inputNode.checked) {
          BX.onCustomEvent(item, this.events.afterAccepted, [item]);
          BX.onCustomEvent(this, this.events.afterAccepted, [item]);
        } else {
          BX.onCustomEvent(item, this.events.refused, [item]);
          BX.onCustomEvent(this, this.events.refused, [item]);
        }

        return;
      }

      this.requestForItem(item);
      e.preventDefault();
    },
    onSubmit: function (item, e) {
      if (typeof e !== "undefined") {
        BX.UserConsent.queue++;
      }

      this.isFormSubmitted = true;
      if (this.check(item)) {
        return true;
      } else {
        if (e) {
          e.preventDefault();
        }

        return false;
      }
    },
    onFormSubmit: function (e) {
      var formNode = e.currentTarget || e.target;
      var items = BX.data(formNode, "userConsentItems") || [];

      this.isFormSubmitted = true;

      for (var i = 0; i < items.length; i++) {
        BX.UserConsent.queue = 1;
        if (!this.check(items[i])) {
          e.preventDefault();
          this.isFormSubmitted = false;
          BX.UserConsent.queue = 0;
          return false;
        }
      }

      BX.UserConsent.queue = 0;
      return true;
    },
    check: function (item) {
      if (BX.UserConsent.queue > 1) {
        return;
      }

      if (item.inputNode.checked) {
        BX.UserConsent.queue = 0;

        this.saveConsent(item, () => {
          item.saved = true;
        });

        return true;
      }

      this.requestForItem(item);
      return false;
    },
    requestForItem: function (item) {
      this.setCurrent(item);
      this.requestConsent(
        item.config.id,
        {
          sec: item.config.sec,
          replace: item.config.replace,
        },
        this.onAccepted,
        this.onRefused
      );
    },
    setCurrent: function (item) {
      this.current = item;
      this.autoSave = item.config.autoSave;
      this.actionRequestUrl = item.config.actionUrl;
    },
    onAccepted: function () {
      if (!this.current) {
        return;
      }

      var item = this.current;
      this.saveConsent(this.current, function () {
        BX.onCustomEvent(item, this.events.accepted, []);
        BX.onCustomEvent(this, this.events.accepted, [item]);

        item.saved = true;

        if (this.isFormSubmitted && item.formNode && !item.config.submitEventName) {
          // BX.submit(item.formNode);
        }

        this.current.inputNode.checked = true;

        if (
          $(this.current.formNode).attr("id") !== "bx-soa-order-form" &&
          typeof $.validator === "function" &&
          $(this.current.formNode).data("validator")
        ) {
          $(this.current.inputNode).valid();
        } else {
          let eventChange = new Event("change");
          this.current.inputNode.dispatchEvent(eventChange);
        }

        this.current = null;

        BX.onCustomEvent(item, this.events.afterAccepted, [item]);
        BX.onCustomEvent(this, this.events.afterAccepted, [item]);

        BX.UserConsent.queue = 0;
      });
    },
    onRefused: function () {
      BX.onCustomEvent(this.current, this.events.refused, [this.current]);
      BX.onCustomEvent(this, this.events.refused, [this.current]);
      this.current.inputNode.checked = false;

      if (
        $(this.current.formNode).attr("id") !== "bx-soa-order-form" &&
        typeof $.validator === "function" &&
        $(this.current.formNode).data("validator")
      ) {
        $(this.current.inputNode).valid();
      } else {
        let eventChange = new Event("change");
        this.current.inputNode.dispatchEvent(eventChange);
      }

      this.current = null;
      this.isFormSubmitted = false;

      BX.UserConsent.queue = 0;
    },
    initPopup: function () {
      if (this.popup) {
        return;
      }

      this.popup = {};
    },
    popup: {
      isInit: false,
      caller: null,
      nodes: {
        container: null,
        shadow: null,
        head: null,
        loader: null,
        content: null,
        textarea: null,
        buttonAccept: null,
        buttonReject: null,
      },
      onAccept: function () {
        this.hide();
        BX.onCustomEvent(this, "accept", []);
      },
      onReject: function () {
        this.hide();
        BX.onCustomEvent(this, "reject", []);
      },
      init: function () {
        if (this.isInit) {
          return true;
        }

        var tmplNode = document.querySelector("div[data-bx-template]");
        if (!tmplNode) {
          return false;
        }

        var popup = document.createElement("DIV");
        popup.innerHTML = tmplNode.innerHTML;
        popup = popup.children[0];
        if (!popup) {
          return false;
        }
        document.body.insertBefore(popup, document.body.children[0]);

        this.isInit = true;
        this.nodes.container = popup;
        this.nodes.shadow = this.nodes.container.querySelector("[data-bx-shadow]");
        this.nodes.head = this.nodes.container.querySelector("[data-bx-head]");
        this.nodes.loader = this.nodes.container.querySelector("[data-bx-loader]");
        this.nodes.content = this.nodes.container.querySelector("[data-bx-content]");
        this.nodes.textarea = this.nodes.container.querySelector("[data-bx-textarea]");
        this.nodes.link = this.nodes.container.querySelector("[data-bx-link]");
        this.nodes.linkA = this.nodes.link ? this.nodes.link.querySelector("a") : null;

        this.nodes.buttonAccept = this.nodes.container.querySelector("[data-bx-btn-accept]");
        this.nodes.buttonReject = this.nodes.container.querySelector("[data-bx-btn-reject]");
        this.nodes.buttonAccept.textContent = BX.message(this.caller.msg.btnAccept);
        this.nodes.buttonReject.textContent = BX.message(this.caller.msg.btnReject);
        BX.bind(this.nodes.buttonAccept, "click", this.onAccept.bind(this));
        BX.bind(this.nodes.buttonReject, "click", this.onReject.bind(this));

        return true;
      },
      setTitle: function (text) {
        if (!this.nodes.head) {
          return;
        }
        this.nodes.head.innerHTML = text;
      },
      setContent: function (text) {
        if (!this.nodes.textarea) {
          return;
        }
        this.nodes.textarea.innerHTML = text;

        this.nodes.link.style.display = "none";
        this.nodes.textarea.style.display = "";
      },
      setUrl: function (url) {
        if (!this.nodes.link) {
          return;
        }

        this.nodes.linkA.textContent = url;
        this.nodes.linkA.href = url;

        this.nodes.link.style.display = "";
        this.nodes.textarea.style.display = "none";
      },
      show: function (isContentVisible) {
        if (typeof isContentVisible == "boolean") {
          this.nodes.loader.style.display = !isContentVisible ? "" : "none";
          this.nodes.content.style.display = isContentVisible ? "" : "none";
        }

        this.nodes.container.style.display = "";

        // Delay blur to ensure focus is properly removed on iOS Safari,
        // where calling blur() synchronously may not dismiss the keyboard.
        setTimeout(() => {
          if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
          }
        }, 0);
        document.body.classList.add("overflow-block");
      },
      hide: function () {
        this.nodes.container.style.display = "none";
        document.querySelector("body").classList.remove("overflow-block");
      },
    },

    cache: {
      list: [],
      stringifyKey: function (key) {
        return BX.type.isString(key) ? key : JSON.stringify({ key: key });
      },
      set: function (key, data) {
        var item = this.get(key);
        if (item) {
          item.data = data;
        } else {
          this.list.push({
            key: this.stringifyKey(key),
            data: data,
          });
        }
      },
      getData: function (key) {
        var item = this.get(key);
        return item ? item.data : null;
      },
      get: function (key) {
        key = this.stringifyKey(key);
        var filtered = this.list.filter(function (item) {
          return item.key == key;
        });
        return filtered.length > 0 ? filtered[0] : null;
      },
      has: function (key) {
        return !!this.get(key);
      },
    },
    requestConsent: function (id, sendData, onAccepted, onRefused) {
      sendData = sendData || {};
      sendData.id = id;

      var cacheHash = this.cache.stringifyKey(sendData);

      if (!this.popup.isInit) {
        this.popup.caller = this;
        if (!this.popup.init()) {
          return;
        }

        BX.addCustomEvent(this.popup, "accept", onAccepted.bind(this));
        BX.addCustomEvent(this.popup, "reject", onRefused.bind(this));
      }

      if (this.current && this.current.config.text) {
        this.cache.set(cacheHash, this.current.config.text);
      }

      if (this.current && this.current.config.url) {
        this.setTextToPopup("", this.current.config.url);
      } else if (this.cache.has(cacheHash)) {
        this.setTextToPopup(this.cache.getData(cacheHash));
      } else {
        this.popup.setTitle(BX.message(this.msg.loading));
        this.popup.show(false);
        BX.ajax
          .runAction("main.agreement.get", {
            data: sendData,
          })
          .then((response) => {
            this.cache.set(cacheHash, response.data.content.html || "");
            this.setTextToPopup(this.cache.getData(cacheHash));
          })
          .catch(() => {
            this.popup.hide();
            alert(BX.message(this.msg.errTextLoad));
          });
      }
    },
    setTextToPopup: function (text, url) {
      this.popup.setTitle(BX.message(this.msg.title));
      if (url) {
        this.popup.setUrl(url);
      } else {
        this.popup.setContent(text);
      }
      this.popup.show(true);
    },
    saveConsent: function (item, callback) {
      this.setCurrent(item);

      var data = {
        id: item.config.id,
        sec: item.config.sec,
        url: window.location.href,
      };
      if (item.config.originId) {
        var originId = item.config.originId;
        if (item.formNode && originId.indexOf("%") >= 0) {
          var inputs = item.formNode.querySelectorAll('input[type="text"], input[type="hidden"]');
          inputs = BX.convert.nodeListToArray(inputs);
          inputs.forEach(function (input) {
            if (!input.name) {
              return;
            }
            originId = originId.replace("%" + input.name + "%", input.value ? input.value : "");
          });
        }
        data.originId = originId;
      }
      if (item.config.originatorId) {
        data.originatorId = item.config.originatorId;
      }

      BX.onCustomEvent(item, this.events.save, [data]);
      BX.onCustomEvent(this, this.events.save, [item, data]);

      if (item.saved || !item.config.autoSave) {
        if (callback) {
          callback.apply(this, []);
        }
      } else {
        this.sendActionRequest("saveConsent", data, () => {
          this.restoreDnkRevoke(item);
          if (callback) {
            callback.apply(this, []);
          }
        }, callback);
      }
    },
    restoreDnkRevoke: function (item) {
      if (!item || !item.config || !item.config.id) {
        return;
      }

      BX.ajax({
        url: "/local/ajax/user_consent.php",
        method: "POST",
        dataType: "json",
        data: {
          action: "restore",
          agreement_id: item.config.id,
          sessid: BX.bitrix_sessid(),
        },
      });
    },
    sendActionRequest: function (action, sendData, callbackSuccess, callbackFailure) {
      callbackSuccess = callbackSuccess || null;
      callbackFailure = callbackFailure || null;

      sendData.action = action;
      sendData.sessid = BX.bitrix_sessid();
      sendData.action = action;

      BX.ajax({
        url: this.actionRequestUrl,
        method: "POST",
        data: sendData,
        timeout: 10,
        dataType: "json",
        processData: true,
        onsuccess: BX.proxy(function (data) {
          data = data || {};
          if (data.error) {
            callbackFailure.apply(this, [data]);
          } else if (callbackSuccess) {
            callbackSuccess.apply(this, [data]);
          }
        }, this),
        onfailure: BX.proxy(function () {
          var data = { error: true, text: "" };
          if (callbackFailure) {
            callbackFailure.apply(this, [data]);
          }
        }, this),
      });
    },
  };

  BX.ready(function () {
    BX.UserConsent.loadFromForms();
  });

  BX.addCustomEvent("onUserConsentReload", () => {
    BX.UserConsent.loadFromForms();
  });
})();

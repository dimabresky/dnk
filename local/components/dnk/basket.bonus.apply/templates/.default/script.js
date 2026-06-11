(() => {
  let isProcessing = false;
  let isSyncing = false;

  const parseAmount = (value) => {
    const normalized = String(value || "").replace(",", ".");
    const parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const getRoot = () => document.querySelector(".dnk-basket-bonus-apply");

  const getMessages = (root) => {
    try {
      return JSON.parse(root.dataset.messages || "{}");
    } catch {
      return {};
    }
  };

  const getLabels = (root) => {
    try {
      return JSON.parse(root.dataset.labels || "{}");
    } catch {
      return {};
    }
  };

  const formatLabel = (template, replacements) => {
    if (!template) {
      return "";
    }

    return Object.entries(replacements).reduce(
      (text, [key, value]) => text.replaceAll(`#${key}#`, String(value)),
      template
    );
  };

  const showError = (root, message) => {
    const messages = getMessages(root);
    const text = message || messages.generic || "Не удалось применить бонусы";

    if (BX?.UI?.Notification?.Center) {
      BX.UI.Notification.Center.notify({ content: text });
      return;
    }

    console.warn(text);
  };

  const mapErrorMessage = (root, code) => {
    const messages = getMessages(root);
    const map = {
      not_authorized: messages.notAuthorized,
      empty_basket: messages.emptyBasket,
      bonus_not_applicable: messages.notApplicable,
      apply_failed: messages.applyFailed,
      basket_save_failed: messages.saveFailed,
      modules: messages.generic,
    };

    return map[code] || messages.generic || "Не удалось применить бонусы";
  };

  const setLoading = (root, loading) => {
    root.classList.toggle("dnk-basket-bonus-apply--loading", loading);
    root.toggleAttribute("aria-busy", loading);

    const loader = root.querySelector('[data-role="dnk-bonus-loader"]');
    if (loader) {
      loader.setAttribute("aria-hidden", loading ? "false" : "true");
    }

    root.querySelectorAll("button, [data-role='dnk-bonus-amount']").forEach((control) => {
      control.disabled = loading;
    });
  };

  const toggleElement = (element, visible) => {
    if (!element) {
      return;
    }

    element.style.display = visible ? "" : "none";
  };

  const updateUiFromData = (root, ui) => {
    if (!root || !ui) {
      return;
    }

    if (!ui.available) {
      root.style.display = "none";
      return;
    }

    root.style.display = "";

    const labels = getLabels(root);
    const applied = parseAmount(ui.applied);
    const maxPay = parseAmount(ui.max_pay);
    const hint = root.querySelector('[data-role="dnk-bonus-hint"]');
    const meta = root.querySelector('[data-role="dnk-bonus-meta"]');
    const controls = root.querySelector(".basket-bonus-section__controls");
    const links = root.querySelector('[data-role="dnk-bonus-links"]');
    const metaError = root.querySelector('[data-role="dnk-bonus-meta-error"]');
    const linksError = root.querySelector('[data-role="dnk-bonus-links-error"]');
    const input = root.querySelector('[data-role="dnk-bonus-amount"]');
    const applyAllBtn = root.querySelector('[data-role="dnk-bonus-apply-all"]');
    const resetBtn = root.querySelector('[data-role="dnk-bonus-reset"]');

    if (ui.error_min) {
      toggleElement(hint, true);
      if (hint) {
        hint.textContent = formatLabel(labels.minError, { MIN: ui.min_pay_formatted || ui.min_pay || 0 });
      }
      toggleElement(meta, false);
      toggleElement(controls, false);
      toggleElement(links, false);
      toggleElement(metaError, applied > 0);
      toggleElement(linksError, applied > 0);

      const appliedError = root.querySelector('[data-role="dnk-bonus-applied-error"]');
      if (appliedError) {
        appliedError.textContent = formatLabel(labels.applied, { APPLIED: ui.applied_formatted || applied });
      }
      return;
    }

    toggleElement(hint, false);
    toggleElement(meta, true);
    toggleElement(controls, true);
    toggleElement(links, true);
    toggleElement(metaError, false);
    toggleElement(linksError, false);

    const balanceNode = root.querySelector('[data-role="dnk-bonus-balance"]');
    const maxNode = root.querySelector('[data-role="dnk-bonus-max"]');
    const appliedNode = root.querySelector('[data-role="dnk-bonus-applied"]');

    if (balanceNode) {
      balanceNode.textContent = formatLabel(labels.balance, { BALANCE: ui.balance_formatted || ui.balance || 0 });
    }
    if (maxNode) {
      maxNode.textContent = formatLabel(labels.max, { MAX: ui.max_pay_formatted || ui.max_pay || 0 });
    }
    if (appliedNode) {
      appliedNode.textContent = formatLabel(labels.applied, { APPLIED: ui.applied_formatted || applied });
      toggleElement(appliedNode, applied > 0);
    }
    if (input) {
      input.value = applied > 0 ? String(applied) : "";
    }
    if (applyAllBtn) {
      applyAllBtn.dataset.max = String(maxPay);
      toggleElement(applyAllBtn, maxPay > 0);
    }
    if (resetBtn) {
      toggleElement(resetBtn, applied > 0);
    }
  };

  const reloadAfterBonusChange = () => {
    window.location.reload();
  };

  const runAction = async (action, data = {}) => {
    const response = await BX.ajax.runComponentAction("dnk:basket.bonus.apply", action, {
      mode: "class",
      data,
    });

    return response?.data || {};
  };

  const syncAfterBasketChange = async (basketComponent) => {
    const root = getRoot();
    if (!root || isSyncing || isProcessing) {
      return;
    }

    try {
      isSyncing = true;

      const result = await runAction("sync");
      if (!result.success) {
        return;
      }

      if (result.ui) {
        updateUiFromData(root, result.ui);
      }

      if (result.basket_refresh_needed && basketComponent?.sendRequest) {
        basketComponent.sendRequest("refreshAjax", {
          fullRecalculation: "Y",
          skipBonusSync: true,
        });
      }
    } catch (error) {
      console.error(error);
    } finally {
      isSyncing = false;
    }
  };

  const handleClick = async (event) => {
    const applyBtn = event.target.closest('[data-role="dnk-bonus-apply"]');
    const applyAllBtn = event.target.closest('[data-role="dnk-bonus-apply-all"]');
    const resetBtn = event.target.closest('[data-role="dnk-bonus-reset"]');

    if (!applyBtn && !applyAllBtn && !resetBtn) {
      return;
    }

    const root = event.target.closest(".dnk-basket-bonus-apply");
    if (!root || isProcessing) {
      return;
    }

    event.preventDefault();

    const input = root.querySelector('[data-role="dnk-bonus-amount"]');

    try {
      isProcessing = true;
      setLoading(root, true);

      if (applyBtn) {
        const result = await runAction("apply", { amount: parseAmount(input?.value) });
        if (!result.success) {
          showError(root, mapErrorMessage(root, result.message));
          return;
        }
        reloadAfterBonusChange();
        return;
      }

      if (applyAllBtn) {
        const max = parseAmount(applyAllBtn.dataset.max);
        if (input) {
          input.value = String(max);
        }
        const result = await runAction("apply", { amount: max });
        if (!result.success) {
          showError(root, mapErrorMessage(root, result.message));
          return;
        }
        reloadAfterBonusChange();
        return;
      }

      if (resetBtn) {
        const result = await runAction("reset");
        if (!result.success) {
          showError(root, mapErrorMessage(root, result.message));
          return;
        }
        reloadAfterBonusChange();
      }
    } catch (error) {
      console.error(error);
      showError(root);
    } finally {
      isProcessing = false;
      setLoading(root, false);
    }
  };

  const bindHandlers = () => {
    document.addEventListener("click", handleClick);
  };

  const ensureAjaxAndInit = () => {
    if (window.BX?.ajax?.runComponentAction) {
      bindHandlers();
      return;
    }

    if (window.BX?.loadExt) {
      BX.loadExt("ajax")
        .then(() => {
          if (window.BX?.ajax?.runComponentAction) {
            bindHandlers();
            return;
          }
          console.warn("dnk:basket.bonus.apply: BX.ajax.runComponentAction is not available");
        })
        .catch(() => {
          console.warn("dnk:basket.bonus.apply: failed to load ajax extension");
        });
      return;
    }

    console.warn("dnk:basket.bonus.apply: BX.ajax.runComponentAction is not available");
  };

  const init = () => {
    window.DnkBasketBonusApply = {
      syncAfterBasketChange,
      updateUiFromData,
    };
    ensureAjaxAndInit();
  };

  if (window.BX?.ready) {
    BX.ready(init);
  } else if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

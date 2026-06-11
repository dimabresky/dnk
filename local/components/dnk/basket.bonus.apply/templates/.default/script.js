(() => {
  let isProcessing = false;

  const parseAmount = (value) => {
    const normalized = String(value || "").replace(",", ".");
    const parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const getMessages = (root) => {
    try {
      return JSON.parse(root.dataset.messages || "{}");
    } catch {
      return {};
    }
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

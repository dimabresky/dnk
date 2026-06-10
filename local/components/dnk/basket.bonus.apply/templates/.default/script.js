(() => {
  const root = document.getElementById("dnk-basket-bonus-apply");
  if (!root || !BX?.Sale?.BasketComponent) {
    return;
  }

  const parseAmount = (value) => {
    const normalized = String(value || "").replace(",", ".");
    const parsed = parseFloat(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
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

  root.addEventListener("click", async (event) => {
    const applyBtn = event.target.closest('[data-role="dnk-bonus-apply"]');
    const applyAllBtn = event.target.closest('[data-role="dnk-bonus-apply-all"]');
    const resetBtn = event.target.closest('[data-role="dnk-bonus-reset"]');
    const input = root.querySelector('[data-role="dnk-bonus-amount"]');

    try {
      if (applyBtn) {
        const result = await runAction("apply", { amount: parseAmount(input?.value) });
        if (!result.success) {
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
          return;
        }
        reloadAfterBonusChange();
        return;
      }

      if (resetBtn) {
        const result = await runAction("reset");
        if (!result.success) {
          return;
        }
        reloadAfterBonusChange();
      }
    } catch (error) {
      console.error(error);
    }
  });
})();

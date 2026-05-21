BX.Aspro.Utils.readyDOM(() => {
  const $jsBlocks = document.querySelectorAll("[data-js-block]");

  if ($jsBlocks.length) {
    for (let i = 0; i < $jsBlocks.length; i++) {
      const $container = $jsBlocks[i];
      const $block = $container.dataset.jsBlock ? document.querySelector($container.dataset.jsBlock) : false;

      if ($block) {
        $container.appendChild($block);
        $container.removeAttribute(["data-js-block"]);
      }
    }
  }

  if (arAsproOptions?.THEME?.TOP_MENU_FIXED === "Y") {
    if (arAsproOptions?.THEME?.SHOW_HEADER_GOODS === "Y") {
      const initDetailHeader = () => {
        BX.removeCustomEvent("onAsproHeaderFixedAppear", initDetailHeader);

        BX.Aspro.Loader.addExt("header.detail").then(() => {
          BX?.Aspro?.Header?.Detail?.set?.();
        });
      };

      BX.addCustomEvent("onAsproHeaderFixedAppear", initDetailHeader);
    }

    if (arAsproOptions.THEME.USE_DETAIL_TABS === "Y" && arAsproOptions.THEME.SHOW_FIXED_HEADER_TABS === "Y") {
      const initHeaderTabs = () => {
        BX.removeCustomEvent("onAsproHeaderFixedAppear", initHeaderTabs);

        BX.Aspro.Loader.addExt("header.tabs").then(() => {
          BX.Aspro?.Header?.Tabs?.set?.();
        });
      };

      BX.addCustomEvent("onAsproHeaderFixedAppear", initHeaderTabs);
    }
  }

  const debounce = (fn, ms) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  const syncFns = [];

  document.querySelectorAll(".js-detail-description-expand").forEach((wrap) => {
    const textEl = wrap.querySelector(".js-detail-description-text");
    const toggle = wrap.querySelector(".js-detail-description-toggle");
    if (!textEl || !toggle) {
      return;
    }

    const expandText = toggle.dataset.expand || "";
    const collapseText = toggle.dataset.collapse || "";

    const CLS_COLLAPSED = "catalog-detail__description-text--collapsed";
    const CLS_EXPANDED = "catalog-detail__description-text--expanded";

    const isCollapsed = () => textEl.classList.contains(CLS_COLLAPSED);

    /**
     * Один клон: сначала измеряем высоту при line-clamp, на том же узле снимаем только
     * line-clamp/overflow (display остаётся -webkit-box) и сравниваем scrollHeight.
     * Два клона (collapsed vs expanded/block) давали разницу 15–40px при одинаковом числе строк.
     */
    const measureContentExceedsClamp = () => {
      const w = Math.max(1, Math.round(textEl.getBoundingClientRect().width));
      if (w < 1) {
        return false;
      }

      const clone = textEl.cloneNode(true);
      clone.classList.remove(CLS_EXPANDED);
      clone.classList.add(CLS_COLLAPSED);

      Object.assign(clone.style, {
        position: "absolute",
        left: "-99999px",
        top: "0",
        width: `${w}px`,
        visibility: "hidden",
        pointerEvents: "none",
      });

      wrap.appendChild(clone);
      void clone.offsetHeight;

      const heightClampedBox = clone.clientHeight;
      if (heightClampedBox < 1) {
        wrap.removeChild(clone);
        return false;
      }

      clone.style.setProperty("-webkit-line-clamp", "unset");
      clone.style.setProperty("line-clamp", "unset");
      clone.style.setProperty("overflow", "visible");
      void clone.offsetHeight;

      const fullScrollWithoutClamp = clone.scrollHeight;

      wrap.removeChild(clone);

      const cs = window.getComputedStyle(textEl);
      let lineHeightPx = parseFloat(cs.lineHeight);
      if (!Number.isFinite(lineHeightPx) || lineHeightPx <= 0) {
        lineHeightPx = parseFloat(cs.fontSize) * 1.2;
      }

      /** подпиксель / округление; не должен превышать ~½ строки иначе ловим ложные «есть ещё текст» */
      const fudge = Math.max(8, Math.round(lineHeightPx * 0.35));

      return fullScrollWithoutClamp > heightClampedBox + fudge;
    };

    const EXPANDABLE_CLASS = "catalog-detail__description-expand--expandable";

    const syncToggle = () => {
      requestAnimationFrame(() => {
        const need = measureContentExceedsClamp();

        if (!need) {
          wrap.classList.remove(EXPANDABLE_CLASS);
          toggle.setAttribute("hidden", "");
          return;
        }

        wrap.classList.add(EXPANDABLE_CLASS);
        toggle.removeAttribute("hidden");

        if (isCollapsed()) {
          toggle.textContent = expandText;
          toggle.setAttribute("aria-expanded", "false");
        } else {
          toggle.textContent = collapseText;
          toggle.setAttribute("aria-expanded", "true");
        }
      });
    };

    syncFns.push(syncToggle);

    requestAnimationFrame(() => {
      requestAnimationFrame(syncToggle);
    });

    textEl.querySelectorAll("img").forEach((img) => {
      if (!img.complete) {
        img.addEventListener(
          "load",
          () => {
            syncToggle();
          },
          { once: true }
        );
      }
    });

    window.addEventListener(
      "load",
      () => {
        syncToggle();
      },
      { once: true }
    );

    if (typeof ResizeObserver !== "undefined") {
      const ro = new ResizeObserver(
        debounce(() => {
          syncToggle();
        }, 80)
      );
      ro.observe(textEl);
    }

    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      if (!wrap.classList.contains(EXPANDABLE_CLASS)) {
        return;
      }
      if (isCollapsed()) {
        textEl.classList.remove(CLS_COLLAPSED);
        textEl.classList.add(CLS_EXPANDED);
      } else {
        textEl.classList.add(CLS_COLLAPSED);
        textEl.classList.remove(CLS_EXPANDED);
      }
      syncToggle();
    });
  });

  if (syncFns.length) {
    window.addEventListener(
      "resize",
      debounce(() => {
        syncFns.forEach((fn) => {
          fn();
        });
      }, 120)
    );
  }
});

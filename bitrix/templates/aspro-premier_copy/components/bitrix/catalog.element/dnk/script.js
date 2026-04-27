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
});

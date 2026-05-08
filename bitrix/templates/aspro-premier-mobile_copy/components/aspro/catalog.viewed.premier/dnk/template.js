if (!funcDefined("showViewedItems")) {
  showViewedItems = function (block, cookieItems, config) {
    config = typeof config === "object" && config ? config : {};
    config.SHOW_MEASURE = config.SHOW_MEASURE !== "N" ? "Y" : "N";
    config.SHOW_BONUS = config.SHOW_BONUS !== "Y" ? "N" : "Y";

    if (block) {
      try {
        if (BX.Type.isObject(BX.Aspro.Viewed)) {
          let bUseLazyload =
            typeof arAsproOptions === "object" && arAsproOptions && arAsproOptions.THEME.USE_LAZY_LOAD === "Y";
          let storageItems = BX.Aspro.Viewed.getProducts();

          // remove some old items
          for (var i in cookieItems) {
            let productId = cookieItems[i].PRODUCT_ID;
            if (productId && typeof storageItems[productId] == "undefined") {
              block.querySelector('.catalog-viewed__item[data-id="' + productId + '"]').remove();
            }
          }

          for (let productId in storageItems) {
            var item = block.querySelector('.catalog-viewed__item[data-id="' + productId + '"]');
            if (item) {
              let wrap = item.querySelector(".catalog-viewed__item-wrap");
              if (wrap) {
                let storageItem = storageItems[productId];

                let picture = {
                  ID: false,
                  SRC: arAsproOptions.SITE_TEMPLATE_PATH + "/images/svg/noimage_product.svg",
                  ALT: storageItem.NAME,
                  TITLE: storageItem.NAME,
                };
                if (typeof item.dataset.picture !== "undefined") {
                  picture = JSON.parse(item.dataset.picture);
                }

                let bSaleMode = arAsproOptions.MODULES.sale;
                let isOffer = storageItem.IS_OFFER == "Y";
                let bWithOffers = storageItem.WITH_OFFERS == "Y";
                let bShowMeasure = config.SHOW_MEASURE == "Y" && storageItem.CATALOG_MEASURE_NAME.length;

                const bExistsPrice = typeof storageItem.PRICE === "object" && storageItem.PRICE;
                const bFilledPrice =
                  storageItem.PRICE.PRICE > 0 ||
                  (parseInt(storageItem.PRICE.PRICE) === 0 && config.MISSING_GOODS_PRICE_DISPLAY === "PRICE");
                let bHasPrice = bExistsPrice && (bFilledPrice || (!bSaleMode && storageItem.PRICE.PRICE.length));
                let bHasOldPrice = bExistsPrice && (storageItem.PRICE.PRICEOLD > 0 || (!bSaleMode && storageItem.PRICE.PRICEOLD.length)) && (!bHasPrice || storageItem.PRICE.PRICEOLD != storageItem.PRICE.PRICE);
                let bHasEconomy =
                  bExistsPrice && (storageItem.PRICE.ECONOMY > 0 || (!bSaleMode && storageItem.PRICE.ECONOMY.length));

                let priceHtml = "";
                if (bHasPrice) {
                  let pricePrint =
                    typeof storageItem.PRICE.PRICE_PRINT !== "undefined"
                      ? storageItem.PRICE.PRICE_PRINT
                      : storageItem.PRICE.PRICE;
                  priceHtml +=
                    '<div class="price__new fw-500"><span class="price__new-val font_16 font_14--to-600">' +
                    (bWithOffers && bSaleMode ? BX.message("CATALOG_FROM_VIEWED") + " " : "") +
                    pricePrint.replace(BX.message("CATALOG_FROM_VIEWED"), "").trim() +
                    (bShowMeasure ? "/<span>" + storageItem.CATALOG_MEASURE_NAME + "</span>" : "") +
                    "</span></div>";
                } else if (bSaleMode && config.MISSING_GOODS_PRICE_DISPLAY === 'TEXT' && config.MISSING_GOODS_PRICE_TEXT) {
                  priceHtml = '<div class="price__new"><span class="price__new-val font_15">' + config.MISSING_GOODS_PRICE_TEXT + '</span></div>';
                }
                if (bHasOldPrice) {
                  let priceOldPrint =
                    typeof storageItem.PRICE.PRICEOLD_PRINT !== "undefined"
                      ? storageItem.PRICE.PRICEOLD_PRINT
                      : storageItem.PRICE.PRICEOLD;
                  priceHtml +=
                    '<div class="price__old fw-500"><del class="price__old-val font_12 secondary-color">' +
                    priceOldPrint +
                    "</del></div>";
                }
                if (bHasEconomy) {
                  let economyPrint =
                    typeof storageItem.PRICE.ECONOMY_PRINT !== "undefined"
                      ? storageItem.PRICE.ECONOMY_PRINT
                      : storageItem.PRICE.ECONOMY;
                  priceHtml +=
                    `<div class="price__economy sticker"><span class="price__economy-percent sticker__item sticker__item--sale-text font_12">` +
                    economyPrint +
                    `</span></div>`;
                }
                if (priceHtml) {
                  priceHtml =
                    '<div class="price color_dark mt mt--6"><div class="price__row">' + priceHtml + "</div></div>";
                }

                wrap.innerHTML =
                  '<div class="catalog-viewed__item__inner flexbox flexbox--column">' +
                  '<a class="catalog-viewed__item__image mb mb--16 relative overflow-block image-rounded-x" href="' +
                  storageItem.DETAIL_PAGE_URL +
                  '">' +
                  '<img class="img absolute fit-image object-fit-contain' +
                  (bUseLazyload ? " lazyload" : "") +
                  '" border="0" ' +
                  (bUseLazyload
                    ? 'src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="' +
                      picture.SRC +
                      '"'
                    : 'src="' + picture.SRC + '"') +
                  '" alt="' +
                  (picture.ALT.length ? picture.ALT : storageItem.NAME) +
                  '" title="' +
                  (picture.TITLE.length ? picture.TITLE : storageItem.NAME) +
                  '" />' +
                  "</a>" +
                  '<div class="catalog-viewed__item__info">' +
                  '<div class="catalog-viewed__item__title font_13 lineclamp-1">' +
                  '<a class="dark_link color-theme-target" href="' +
                  storageItem.DETAIL_PAGE_URL +
                  '" title="' +
                  storageItem.NAME +
                  '"><span>' +
                  storageItem.NAME +
                  "</span></a>" +
                  "</div>" +
                  priceHtml +
                  "</div>" +
                  "</div>";
              }
            } else {
              // item not finded
              // may be if it`s new item (it`s detail page now)
              // or quantity limit
            }
          }

          // if no items than remove block
          if (!block.querySelector(".catalog-viewed__item")) {
            block.closest(".catalog-viewed-list").remove();
          }
        }
      } catch (e) {
        console.error(e);
      }
    }
  };
}

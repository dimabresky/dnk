/*check all opt table items*/
$(document).on("change", "input#check_all_item", function () {
  const buyItemsCount = $(".catalog-table__item .to_cart").length;

  if ($(this).is(":checked") && buyItemsCount) {
    $(".opt_action[data-action='basket']").addClass("animate-load");
    $(".opt_action").removeClass("no-action");
    $(".opt_action .opt-buy__item-text").remove();

    //buy
    // $('<div class="opt-buy__item-text">(<span>' + buyItemsCount + "</span>)</div>").appendTo(
    //   $(".opt_action[data-action=buy]")
    // );

    //compare
    $('<div class="opt-buy__item-text">(<span>' + buyItemsCount + "</span>)</div>").appendTo(
      $(".opt_action")
    );

    $('input[name="check_item"]').prop("checked", "checked");
  } else {
    $(".opt_action").addClass("no-action");
    $(".opt_action").removeClass("animate-load");
    $(".opt_action .opt-buy__item-text").remove();

    $('input[name="check_item"]').prop("checked", "");
  }
});

/*check opt table item*/
$(document).on("change", "input[name='check_item']", function () {
  const _this = $(this);

  if (_this.is(":checked")) {
    $(".opt_action").each(function () {
      const _this = $(this);

      if (_this.find(".opt-buy__item-text").length) {
        let count = parseInt(_this.find(".opt-buy__item-text span").text());
        _this.find(".opt-buy__item-text span").text(++count);
      } else {
        _this.removeClass("no-action");
        _this.addClass("animate-load");
        $('<div class="opt-buy__item-text">(<span>1</span>)</div>').appendTo(_this);
      }
    });
  } else {
    $(".opt_action").each(function () {
      const _this = $(this);

      if (_this.find(".opt-buy__item-text").length) {
        let count = parseInt(_this.find(".opt-buy__item-text span").text());
        --count;
        _this.find(".opt-buy__item-text span").text(count);

        if (!count) {
          _this.addClass("no-action");
          _this.removeClass("animate-load");
          _this.find(".opt-buy__item-text").remove();
        }
      }
    });
  }
});

/*buy opt table items*/
$(document).on("click", ".opt_action", function () {
  const _this = $(this),
    action = _this.data("action"),
    basketParams = {
      action: action,
      type: 'multiple',
      is_ajax_post: 'Y',
      state: 1, // add
      IBLOCK_ID: _this.data("iblock_id"),
      SITE_ID: BX.message('SITE_ID'),
      lang: BX.message('LANGUAGE_ID'),
      sessid: BX.bitrix_sessid(),
      items: [],
    };

  if (!_this.hasClass("no-action")) {
    setTimeout(function () {
      var items2add = [];

      let $items = $("input[name=check_item]:checked").closest('.catalog-table__item');
      if ($items.length) {
        for (let i = 0, c = $items.length; i < c; ++i) {
          let $item = $items.eq(i);
          let $basketAction = $item.find('.item-action [data-action="' + JItemActionBasket.prototype.action + '"]');
          let bAdd = (action === 'compare' || action === 'favorite') || (action === 'basket' && $basketAction.length);
          if (bAdd) {
            // items`s nodes for success notice
            items2add.push($item.find(".catalog-table__info")[0]);

            let data = $item.find(".catalog-table__info").data("item");
            if (
              typeof data !== 'undefined' &&
              data
            ) {
              // if the action is compare or favorite than we send only id
              let item = data.ID;

              if (action === 'basket') {
                let quantity = 0;

                let $counter = $item.find('.counter__count');
                if ($counter.length) {
                  quantity = $counter.val();
                }

                if (quantity <= 0) {
                  if ($basketAction.length){
                    quantity = $basketAction.data('quantity');
                  }
                }

                if (quantity <= 0) {
                  quantity = 1;
                }

                item = {
                  ID: data.ID,
                  IBLOCK_ID: data.IBLOCK_ID,
                  quantity: quantity,
                };
              }

              basketParams["items"].push(item);
            }
          }
        }
      }

      if (basketParams["items"].length) {
        $.ajax({
          url: JItemAction.prototype.requestUrl,
          type: "POST",
          dataType: "json",
          sessid: BX.bitrix_sessid(),
          data: basketParams,
        })
        .done(function (data) {
          if (data.success) {
            _this.parent().removeClass('loadings');

            if (action === 'compare') {
              arAsproCounters.COMPARE.ITEMS = data.items;
              arAsproCounters.COMPARE.COUNT = data.count;
              arAsproCounters.COMPARE.TITLE = data.title;

              // mark all current items
              JItemActionCompare.markItems();

              // set current badges
              JItemActionCompare.markBadges();

              // show notice
              JNoticeSurface.get().onAdd2Compare(items2add, true);
            } else if (action === 'favorite') {
              arAsproCounters.FAVORITE.ITEMS = data.items;
              arAsproCounters.FAVORITE.COUNT = data.count;
              arAsproCounters.FAVORITE.TITLE = data.title;

              JItemActionFavorite.markItems();
              JItemActionFavorite.markBadges();
              JNoticeSurface.get().onAdd2Favorite(items2add, true);
            } else if (action === 'basket') {
              arAsproCounters.BASKET.ITEMS = data.items;
              arAsproCounters.BASKET.COUNT = data.count;
              arAsproCounters.BASKET.TITLE = data.title;

              JItemActionBasket.markItems();
              JItemActionBasket.markBadges();
              JNoticeSurface.get().onAdd2Cart(items2add);
            }
          }
        })
        .fail(function (xhr) {
          console.error(xhr);
          JNoticeSurface.get().onRequestError(xhr);
        });
        // else {
        //   $.ajax({
        //     url: arAsproOptions["SITE_DIR"] + "include/footer/basket.php",
        //     type: "POST",
        //     data: basketParams,
        //   }).done(function (html) {
        //     $(".ajax_basket").replaceWith(html);

        //     JNoticeSurface.get().onAdd2Cart(items2add);

        //     var eventdata = { action: "loadBasket" };
        //     BX.onCustomEvent("onCompleteAction", [eventdata, $(html)]);
        //   });
        // }
      }
    }, 0);
  }
});

$(document).ready(function () {
  //check oid
  if (!location.hash) {
    if ("scrollRestoration" in history) {
      history.scrollRestoration = "manual";
    }
    if (typeof arAsproOptions !== "undefined" && arAsproOptions["OID"]) {
      let url, oid;
      if (BX.browser.IsIE()) {
        url = parseUrlQuery();
        oid = arAsproOptions["OID"] ? url[arAsproOptions["OID"]] : null;
      } else {
        url = new URL(window.location);
        oid = arAsproOptions["OID"] ? url.searchParams.get(arAsproOptions["OID"]) : null;
      }
      if (oid) {
        scrollToBlock('[data-id="' + oid + '"]');
      }
    }
  }
});

BX.addCustomEvent("onCompleteAction", function (eventdata) {
  if (eventdata.action === "ajaxContentLoaded") {
    if (typeof window.tableScrollerOb === "object" && window.tableScrollerOb) {
      window.tableScrollerOb.toggle();
    }
  }
});

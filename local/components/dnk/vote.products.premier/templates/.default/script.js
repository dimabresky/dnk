if (typeof window.JVoteProducts === "undefined") {
  window.JVoteProducts = function (node, config) {
    node =
      typeof node === "object" && node && node instanceof Node
        ? node
        : typeof node === "string" && node.length
        ? document.querySelector(node)
        : null;
    config = typeof config === "object" && config ? config : {};

    this.node = node;

    var _private = {
      inited: false,
    };

    let _config = JSON.stringify(config);

    Object.defineProperties(this, {
      inited: {
        get: function () {
          return _private.inited;
        },
        set: function (value) {
          if (value) {
            _private.inited = true;
          }
        },
      },

      config: {
        get: function () {
          return JSON.parse(_config);
        },
      },
    });

    this.init();
  };

  window.JVoteProducts.prototype = {
    node: null,
    nodes: {
      slider: null,
      ignores: null,
      ratings: null,
      feedback: null,
      block: null,
      clear: null,
    },
    busy: false,

    init: function () {
      if (!this.inited) {
        this.inited = true;

        this.node.voteproducts = this;

        if (this.node) {
          this.nodes.slider = this.node.querySelector(".slider-solution");
          this.nodes.ignores = this.node.querySelectorAll(".votes--slider__product__ignore");
          this.nodes.ratings = this.node.querySelectorAll(".votes--slider__product__rating .item-rating");
          this.nodes.feedback = this.node.querySelectorAll(".votes--slider__product__feedback");
          this.nodes.block = this.node.closest(".personal__main-block");
          if (this.nodes.block) {
            this.nodes.clear = this.nodes.block.querySelector(".js_clear_votes");
          }

          this.initSlider();
          this.bindEvents();
        }
      }
    },

    initSlider: function () {
      if (this.nodes.slider) {
        if (typeof initSwiperSlider === "function") {
          initSwiperSlider();
        }
      }
    },

    bindEvents: function () {
      if (this.nodes.ignores) {
        if (typeof this.handlers.onClickIgnore === "function") {
          this.handlers.onClickIgnore = this.handlers.onClickIgnore.bind(this);

          this.nodes.ignores.forEach((ignore) => {
            ignore.addEventListener("click", this.handlers.onClickIgnore);
          });
        }
      }

      if (this.nodes.ratings) {
        if (typeof this.handlers.onClickRating === "function") {
          this.handlers.onClickRating = this.handlers.onClickRating.bind(this);

          this.nodes.ratings.forEach((rating) => {
            rating.addEventListener("click", this.handlers.onClickRating);
          });
        }
      }

      if (this.nodes.feedback) {
        if (typeof this.handlers.onClickFeedback === "function") {
          this.handlers.onClickFeedback = this.handlers.onClickFeedback.bind(this);

          this.nodes.feedback.forEach((feedback) => {
            feedback.addEventListener("click", this.handlers.onClickFeedback);
          });
        }
      }

      if (this.nodes.clear) {
        if (typeof this.handlers.onClickClear === "function") {
          this.handlers.onClickClear = this.handlers.onClickClear.bind(this);
          this.nodes.clear.addEventListener("click", this.handlers.onClickClear);
        }
      }

      if (typeof this.handlers.onIblockCatalogCommentAdd === "function") {
        BX.addCustomEvent("onIblockCatalogCommentAdd", BX.proxy(this.handlers.onIblockCatalogCommentAdd, this));
      }
    },

    unbindEvents: function () {
      if (this.nodes.ignores) {
        if (typeof this.handlers.onClickIgnore === "function") {
          this.nodes.ignores.forEach((ignore) => {
            ignore.removeEventListener("click", this.handlers.onClickIgnore);
          });
        }
      }

      if (this.nodes.ratings) {
        if (typeof this.handlers.onClickRating === "function") {
          this.nodes.ratings.forEach((rating) => {
            rating.removeEventListener("click", this.handlers.onClickRating);
          });
        }
      }

      if (this.nodes.feedback) {
        if (typeof this.handlers.onClickFeedback === "function") {
          this.nodes.feedback.forEach((feedback) => {
            feedback.removeEventListener("click", this.handlers.onClickFeedback);
          });
        }
      }

      if (this.nodes.clear) {
        if (typeof this.handlers.onClickClear === "function") {
          this.nodes.clear.removeEventListener("click", this.handlers.onClickClear);
        }
      }

      if (typeof this.handlers.onIblockCatalogCommentAdd === "function") {
        BX.removeCustomEvent("onIblockCatalogCommentAdd", BX.proxy(this.handlers.onIblockCatalogCommentAdd, this));
      }
    },

    handlers: {
      onClickIgnore: function (event) {
        event = event || window.event;

        let target = event.target;
        if (target) {
          let item = target.closest(".grid-list__item");
          if (item) {
            let id = item.dataset.id;
            id = id ? parseInt(id) : 0;
            this.ignore(id);
          }
        }
      },

      onClickRating: function (event) {
        event = event || window.event;

        let target = event.target;
        if (target) {
          let item = target.closest(".grid-list__item");
          if (item) {
            let id = item.dataset.id;
            id = id ? parseInt(id) : 0;

            let productId = item.dataset.productid;
            productId = productId ? parseInt(productId) : 0;

            let postId = item.dataset.postid;
            postId = postId ? parseInt(postId) : 0;

            let star = target.closest(".item-rating");
            if (star) {
              let rate = 0;

              do {
                ++rate;

                do {
                  star = star.previousSibling;
                } while (star && star.nodeType != Node.ELEMENT_NODE);
              } while (star);

              this.vote(id, productId, postId, rate);
            }
          }
        }
      },

      onClickFeedback: function (event) {
        event = event || window.event;

        let target = event.target;
        if (target) {
          let item = target.closest(".grid-list__item");
          if (item) {
            let id = item.dataset.id;
            id = id ? parseInt(id) : 0;

            let productId = item.dataset.productid;
            productId = productId ? parseInt(productId) : 0;

            let postId = item.dataset.postid;
            postId = postId ? parseInt(postId) : 0;

            this.feedback(id);
          }
        }
      },

      onClickClear: function (event) {
        this.clear();
      },

      onIblockCatalogCommentAdd: function (event) {
        this.refresh();
      },
    },

    showError: function (message) {
      // show error notice
      if (typeof JNoticeSurface === "function") {
        let surface = JNoticeSurface.get();
        surface.onResultError({
          error: message,
        });
      }
    },

    vote: function (id, productId, postId, rate) {
      if (id > 0 && rate >= 1 && rate <= 5) {
        let rating = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__rating')
          : null;
        if (rating) {
          rating.classList.add("loadings");
        }

        let feedback = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__feedback')
          : null;
        if (feedback) {
          feedback.classList.add("hidden");
        }

        let ignore = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__ignore')
          : null;
        if (ignore) {
          ignore.classList.add("hidden");
        }

        const params = {
          ELEMENT_ID: productId,
          POST_ID: postId,
          BLOG_URL: this.config.params.BLOG_URL,
          RATE: rate,
        };

        const data = {
          sessid: BX.message("bitrix_sessid"),
          action: "vote",
          ELEMENT_ID: productId,
          rating: rate,
        }

        if (id != productId) {
          params.OFFER_ID = id;
          data.OFFER_ID = id;
        }

        data.params = JSON.stringify(params),

        BX.ajax({
          url: BX.message("SITE_DIR") + "ajax/vote.php",
          method: "POST",
          dataType: "html",
          async: true,
          data: data,
          onsuccess: function (response) {
            response = response.trim();

            let obData = BX.processHTML(response);
            let html = obData.HTML;

            var tmp = BX.create("DIV", {
              html: html,
            });

            let alert = tmp.querySelector(".blog-error-text");
            if (alert) {
              JNoticeSurface.get().onResultError({ error: alert.innerText });

              if (rating) {
                rating.classList.remove("loadings");
              }

              if (feedback) {
                feedback.classList.remove("hidden");
              }

              if (ignore) {
                ignore.classList.remove("hidden");
              }
            } else {
              BX.onCustomEvent("onIblockCatalogCommentAdd");
            }
          },
          onfailure: BX.proxy(function () {
            if (rating) {
              rating.classList.remove("loadings");
            }

            if (feedback) {
              feedback.classList.remove("hidden");
            }

            if (ignore) {
              ignore.classList.remove("hidden");
            }
          }, this),
        });
      }
    },

    feedback: function (id) {
      if (id > 0) {
        let rating = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__rating')
          : null;
        if (rating) {
          rating.classList.add("hidden");
        }

        let feedback = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__feedback')
          : null;
        if (feedback) {
          feedback.classList.add("loadings");
        }

        let ignore = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__ignore')
          : null;
        if (ignore) {
          ignore.classList.add("hidden");
        }

        if (rating) {
          rating.classList.remove("hidden");
        }

        if (feedback) {
          feedback.classList.remove("loadings");
        }

        if (ignore) {
          ignore.classList.remove("hidden");
        }
      }
    },

    ignore: function (id) {
      if (id > 0) {
        let data = {
          productId: id,
        };

        let rating = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__rating')
          : null;
        if (rating) {
          rating.classList.add("hidden");
        }

        let feedback = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__feedback')
          : null;
        if (feedback) {
          feedback.classList.add("hidden");
        }

        let ignore = this.node
          ? this.node.querySelector('.grid-list__item[data-id="' + id + '"] .votes--slider__product__ignore')
          : null;
        if (ignore) {
          ignore.classList.add("loadings");
        }

        this.sendAction(
          "ignore",
          data,
          (response) => {
            this.refresh();
          },
          (message) => {},
          () => {
            if (rating) {
              rating.classList.remove("hidden");
            }

            if (feedback) {
              feedback.classList.remove("hidden");
            }

            if (ignore) {
              ignore.classList.remove("loadings");
            }
          }
        );
      }
    },

    clear: function () {
      if (this.nodes.clear) {
        this.nodes.clear.classList.add("loadings");
      }

      this.sendAction(
        "clear",
        {},
        (response) => {
          this.refresh();
        },
        (message) => {
          if (this.nodes.clear) {
            this.nodes.clear.classList.remove("loadings");
          }
        },
        () => {}
      );
    },

    refresh: function () {
      let data = {
        rand: this.config.rand,
        template: this.config.template,
        signedParameters: this.config.signedParameters,
      };

      if (this.node) {
        this.node.classList.add("form", "sending");
      }

      this.sendAction(
        "refresh",
        data,
        (response) => {
          if (this.node) {
            if (this.nodes.block) {
              let cnt = response.data.result;

              if (cnt) {
                let counter = this.nodes.block.querySelector(".main-block__title-count");
                if (counter) {
                  counter.innerHTML = cnt;
                }

                if (this.nodes.slider) {
                  let swiper = this.nodes.slider.swiper;
                  if (swiper) {
                    let e = document.createElement("div");
                    e.innerHTML = response.data.content.trim();

                    let items = e.querySelectorAll(".grid-list__item");
                    let itemsIds = [];
                    if (items.length) {
                      for (let i = 0, cnt = items.length; i < cnt; ++i) {
                        let itemId = items[i].dataset.id;
                        itemId = itemId ? parseInt(itemId) : 0;
                        itemsIds.push(itemId);
                      }
                    }

                    let sliderItems = swiper.slides;
                    let sliderItemsIds = [];
                    if (sliderItems.length) {
                      for (let i = 0, cnt = sliderItems.length; i < cnt; ++i) {
                        sliderItemId = sliderItems[i].dataset.id;
                        sliderItemId = sliderItemId ? parseInt(sliderItemId) : 0;
                        sliderItemsIds.push(sliderItemId);
                      }
                    }

                    this.unbindEvents();

                    let sliderItemsIndex2Remove = [];
                    if (sliderItems.length) {
                      for (let i = 0, cnt = sliderItems.length; i < cnt; ++i) {
                        sliderItemId = sliderItems[i].dataset.id;
                        sliderItemId = sliderItemId ? parseInt(sliderItemId) : 0;

                        if (!itemsIds.includes(sliderItemId)) {
                          sliderItemsIndex2Remove.push(i);
                        }
                      }
                    }
                    if (sliderItemsIndex2Remove.length) {
                      swiper.removeSlide(sliderItemsIndex2Remove);
                    }

                    let items2Add = [];
                    if (items.length) {
                      for (let i = 0, cnt = items.length; i < cnt; ++i) {
                        let itemId = items[i].dataset.id;
                        itemId = itemId ? parseInt(itemId) : 0;

                        if (!sliderItemsIds.includes(itemId)) {
                          items2Add.push(items[i]);
                        }
                      }
                    }
                    if (items2Add.length) {
                      swiper.appendSlide(items2Add);
                    }

                    this.nodes.ignores = this.node.querySelectorAll(".votes--slider__product__ignore");
                    this.nodes.ratings = this.node.querySelectorAll(".votes--slider__product__rating .item-rating");

                    this.bindEvents();
                  }
                }
              } else {
                this.nodes.block.remove();
              }
            }
          }
        },
        (message) => {},
        () => {
          if (this.node) {
            this.node.classList.remove("form", "sending");
          }
        }
      );
    },

    sendAction: function (componentAction, data, onsuccess, onfailure, oncomplete) {
      if (!this.busy) {
        this.busy = true;

        let componentName = "dnk:vote.products.premier";

        if (typeof data === "undefined" || !data) {
          data = {};
        }

        if (data instanceof FormData) {
          data.set("sessid", BX.message("bitrix_sessid"));
          data.set("lang", BX.message("LANGUAGE_ID"));
          data.set("siteId", BX.message("SITE_ID"));
        } else {
          data.sessid = BX.message("bitrix_sessid");
          data.lang = BX.message("LANGUAGE_ID");
          data.siteId = BX.message("SITE_ID");
        }

        let promise = BX.ajax.runComponentAction(componentName, componentAction, {
          mode: "ajax",
          data: data,
        });

        promise.then(
          (response) => {
            this.busy = false;

            if (typeof onsuccess === "function") {
              onsuccess(response);
            }

            if (typeof oncomplete === "function") {
              oncomplete();
            }
          },
          (response) => {
            this.busy = false;

            console.error(response);

            let message = "";
            for (let i = 0; i < response.errors.length; ++i) {
              if (typeof response.errors[i] === "object" && response.errors[i].message.length) {
                if (!message.length || response.errors[i].message.length < message.length) {
                  message = response.errors[i].message;
                }
              }
            }

            this.showError(message);

            if (typeof onfailure === "function") {
              onfailure(message);
            }

            if (typeof oncomplete === "function") {
              oncomplete();
            }
          }
        );
      }
    },
  };
}

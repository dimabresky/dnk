// if you want stopped slider - write in console ( window.asproStoriesSliderStopped = true ) and then open popup

document.addEventListener("keydown", function (e) {
  if (e.keyCode == 27) {
    $(".stories-popup").click();
  }
});
(() => {
  const obStories = {};

  $(document).on("click", ".front_stories .item", async function () {
    if (!window.asproStoriesLoading) {
      document.body.classList.add('overflow-block');
      window.asproStoriesLoading = true;
      const _this = $(this);

      let storiesPopupWrapper = $("body > .stories-popup");
      if (!storiesPopupWrapper.length) {
        storiesPopupWrapper = $('<div class="stories-popup"></div>');
      }

      try {
        const blockID = _this.closest(".front_stories").attr("id");
        if (!obStories[blockID]) {
          const response = await BX.ajax.runAction("aspro:premier.Stories.show", {
            data: {
              id: parseInt(_this.data("sectionId")),
              params: _this.closest(".front_stories").data("params"),
            },
          });
          obStories[blockID] = response.data;
        }

        const html = generateSectionsHtml(obStories[blockID], _this.data());
        if (html) {
          storiesPopupWrapper.html(html);
          storiesPopupWrapper.addClass("stories-popup--visible");
          $("body").append(storiesPopupWrapper);
          afterShowActions(obStories[blockID], blockID);
        }

        window.asproStoriesLoading = false;
      } catch (response) {
        if (response.errors) {
          response.errors.forEach((error) => {
            JNoticeSurface.get().onResultError({ error: error.message });
          });
        }

        document.body.classList.remove("overflow-block");
        window.asproStoriesLoading = false;
      }
    }
  });
})();

$(document).on("click", ".front_stories .top_block a", function (e) {
  e.preventDefault();
  $(".front_stories .item").eq(0).trigger("click");
});

function getSliderSectionsHTML(elementsHTML) {
  var html = "";
  var sectionSliderClass = "stories-popup__section-slider";
  var sectionSliderInnerClass = "stories-popup__section-slider-inner";

  html += '<div class="' + sectionSliderClass + '">';
  html +=
    '<div class="' +
    sectionSliderClass +
    '-loader"><svg width="48" height="48" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg" version="1.1"><path d="M 150,0 a 150,150 0 0,1 106.066,256.066 l -35.355,-35.355 a -100,-100 0 0,0 -70.711,-170.711 z" fill="#0000001a"><animateTransform attributeName="transform" attributeType="XML" type="rotate" from="0 150 150" to="360 150 150" begin="0s" dur=".8s" fill="freeze" repeatCount="indefinite"></animateTransform></path></svg></div>';
  html += '<div class="' + sectionSliderInnerClass + '">';
  html += elementsHTML;
  html += "</div>";
  html += "</div>";

  return html;
}

function getSliderElementsHTML(elementsHTML, elementsCount, sectionInfo) {
  var html = "";
  var elementSliderClass =
    "stories-popup__element-slider" +
    (sectionInfo.current ? " stories-popup__element-slider--active" : " stories-popup__element-slider--paused");
  var elementSliderHeaderClass = "stories-popup__element-slider-header";
  var elementSliderHeaderImgClass = "stories-popup__element-slider-header-img";
  var elementSliderHeaderNameClass = "stories-popup__element-slider-header-name";
  var elementSliderElementsWrapperClass = "stories-popup__element-slider-elements";

  var animationDefaultTime = 5;

  var topPanelHtml = "";
  var topPanelClass = "stories-popup__element-slider-panel";
  var topPanelElementClass = "stories-popup__element-slider-panel-element";
  topPanelHtml += '<div class="' + topPanelClass + '">';
  for (var i = 0; i < elementsCount; i++) {
    topPanelHtml += `<div class="${topPanelElementClass + (i == 0 ? " " + topPanelElementClass + "--active" : "")}">`;
    topPanelHtml += `<div class="${topPanelElementClass}-line" style="animation-duration:${animationDefaultTime}s"></div>`;
    topPanelHtml += "</div>";
  }
  topPanelHtml += "</div>";

  html += '<div class="' + elementSliderClass + '" data-section-id="' + sectionInfo.ID + '">';

  html += '<div class="' + elementSliderElementsWrapperClass + '">';
  html += elementsHTML;
  html += "</div>";

  html += topPanelHtml;

  html += '<div class="' + elementSliderHeaderClass + '">';
  if (sectionInfo.PICTURE) {
    html +=
      '<div class="' +
      elementSliderHeaderImgClass +
      '" style="background: url(' +
      sectionInfo.PICTURE +
      ') no-repeat center;" ></div>';
  }
  html += '<div class="' + elementSliderHeaderNameClass + '">' + sectionInfo.NAME + "</div>";
  html += "</div>";

  html += "</div>";

  return html;
}

function generateElementHtml(elementInfo, elementKey) {
  let html = "";
  const elementWrapperClass = "stories-popup__element" + (elementKey > 0 ? "" : " stories-popup__element--active");
  const elementImgClass = "stories-popup__element-image";

  const elementSliderElementsNavWrapperClass = "stories-popup__element-slider-navs";
  const elementSliderElementsNavPrevClass = "stories-popup__element-slider-navs-prev";
  const elementSliderElementsNavNextClass = "stories-popup__element-slider-navs-next";

  const hasButtons = elementInfo.PROPERTY_BTN_TEXT_VALUE || elementInfo.BUTTON_LINKED_ITEMS;

  html += `<div class="${elementWrapperClass} line-block line-block--gap" data-id="${elementInfo.ID}">`;

  html += `<div class="${elementImgClass}" style="background: url('${elementInfo.PREVIEW_PICTURE}') no-repeat center;" ></div>`;

  if (hasButtons) {
    const btnCommonClass = "btn btn-lg flex-1";

    html +=
      '<div class="stories-popup__element-buttons width-100 pb pb--16 p-inline p-inline--16 line-block line-block--gap line-block--gap-8 z-index-2">';

    if (elementInfo.PROPERTY_BTN_TEXT_VALUE) {
      const elementButtonTag = elementInfo.PROPERTY_BTN_LINK_VALUE ? "a" : "div";
      const elementButtonHref = elementInfo.PROPERTY_BTN_LINK_VALUE
        ? ` href="${elementInfo.PROPERTY_BTN_LINK_VALUE}"`
        : "";

      html += `<${elementButtonTag} ${elementButtonHref} class="stories-popup__element-btn ${btnCommonClass} ${elementInfo.PROPERTY_BTN_CLASS_VALUE}">`;
      html += elementInfo.PROPERTY_BTN_TEXT_VALUE;
      html += `</${elementButtonTag}>`;
    }

    if (elementInfo.BUTTON_LINKED_ITEMS) {
      html += `<button type="button" class="stories-popup__element-btn ${btnCommonClass} btn-black-opacity-blur _show-linked-items">`;
      html += elementInfo.BUTTON_LINKED_ITEMS;
      html += "</button>";
    }

    html += "</div>";
  }

  html += `<div class="${elementSliderElementsNavWrapperClass}">`;
  html += `<div class="${elementSliderElementsNavPrevClass}"></div>`;
  html += `<div class="${elementSliderElementsNavNextClass}"></div>`;
  html += "</div>";
  html += "</div>";

  return html;
}

function afterShowActions(sectionInfo, blockID = "") {
  sectionsSliderInit(sectionInfo, blockID);
}

function sectionsSliderInit(sections, blockID) {
  var index = 0;
  var activeSectionIndex = 0;
  for (var key in sections) {
    if (sections[key].current) {
      activeSectionIndex = index;
      break;
    }
    index++;
  }
  var options = {
    popup: $(".stories-popup"),
    activeSlideIndex: activeSectionIndex,
    activeSlideClass: "stories-popup__element-slider--active",
    pausedSlideClass: "stories-popup__element-slider--paused",
    slidesSelector: ".stories-popup__element-slider",
    innerSelector: "> .stories-popup__section-slider-inner",
    activeElementClass: "stories-popup__element--active",
    elementsSelector: ".stories-popup__element",
    elementNavPrevClass: "stories-popup__element-slider-navs-prev",
    elementNavNextClass: "stories-popup__element-slider-navs-next",
    elementPanelClass: "stories-popup__element-slider-panel-element",
    elementPanelActiveClass: "stories-popup__element-slider-panel-element--active",
    elementPanelFinishedClass: "stories-popup__element-slider-panel-element--finishied",
    changeSlideTime: 5000,
    transition: 0.25,
    activeSlideMargin: 8,
    dragAmount: 150,
    mobile: false,
    breakpoints: {
      "(max-width: 1025px)": {
        activeSlideMargin: 4,
      },
      "(max-width: 566px)": {
        mobile: true,
      },
    },
  };
  if (blockID) {
    options.originalBlock = $(`#${blockID}`);
  }
  InitAsproStoriesSectionSlider($(".stories-popup__section-slider"), options);
}

function generateSectionsHtml(sections, sectionData) {
  let html = "";
  html += getPopupCloseIcon();

  let elementsHTML = "";
  for (let sectionKey in sections) {
    const section = sections[sectionKey];
    delete section.current;

    if (section.ID == sectionData.sectionId) {
      section.current = true;
    }

    elementsHTML += generateElementsHtml(section);
  }

  html += getSliderSectionsHTML(elementsHTML);

  return html;
}

function generateElementsHtml(sectionInfo) {
  var html = "";
  var elementsHTML = "";

  var elements = sectionInfo.CHILDS;

  var elementsHTML = "";
  for (var elementKey in elements) {
    var element = elements[elementKey];
    elementsHTML += generateElementHtml(element, elementKey);
  }

  html += getSliderElementsHTML(elementsHTML, elements.length, sectionInfo);

  return html;
}

function getPopupCloseIcon() {
  var iconClass = "stories-popup__close";
  var icon =
    '<div class="' +
    iconClass +
    '"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 1L13 13M13 1L1 13" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></div>';

  return icon;
}

function InitAsproStoriesSectionSlider(el, options) {
  el.asproStoriesSectionsSlider(options);
}

$.fn.asproStoriesSectionsSlider = function (options) {
  function _storiesSlider(slider, options) {
    var _slider = $(slider);

    var responsiveOptions = Object.assign({}, options);
    for (var width in options.breakpoints) {
      if (window.matchMedia(width).matches) {
        responsiveOptions = Object.assign(responsiveOptions, options.breakpoints[width]);
      }
    }

    responsiveOptions.setStyles = function () {
      responsiveOptions.sliderInner.css({
        display: "flex",
        "align-items": "center",
        transition: "margin-left " + responsiveOptions.transition + "s ease-in-out",
        "margin-left": responsiveOptions.calculateMargin(responsiveOptions.activeSlideIndex),
      });
      responsiveOptions.slider.css({
        overflow: "hidden",
      });
    };

    responsiveOptions.getCurrentSizes = function () {
      responsiveOptions.sliderWidth = responsiveOptions.slider.outerWidth(true);
      responsiveOptions.sliderHeight = responsiveOptions.slider.outerHeight(true);
      responsiveOptions.updateSlidesHeight();
    };

    responsiveOptions.calculateMargin = function (slideIndex) {
      responsiveOptions.getCurrentSizes();
      if (responsiveOptions.mobile) {
        responsiveOptions.sliderInnerMargin =
          -responsiveOptions.slideWidth * (slideIndex + 1) +
          responsiveOptions.sliderWidth / 2 +
          responsiveOptions.slideWidth / 2;
      } else {
        responsiveOptions.sliderInnerMargin =
          -responsiveOptions.slideWidth * (slideIndex + 1) +
          responsiveOptions.sliderWidth / 2 +
          responsiveOptions.slideWidth / 2 -
          (responsiveOptions.sliderWidth / 100) * responsiveOptions.activeSlideMargin;
      }
    };

    responsiveOptions.setCenter = function (slideIndex) {
      responsiveOptions.calculateMargin(slideIndex);
      responsiveOptions.sliderInner.css({
        "margin-left": responsiveOptions.sliderInnerMargin,
      });
    };

    responsiveOptions.closePopup = function () {
      if (responsiveOptions.activeSlide) {
        responsiveOptions.pauseSlide(responsiveOptions.activeSlide);
      }
      responsiveOptions.popup.remove();
      document.body.classList.remove("overflow-block");
      responsiveOptions.unbindEvents();
    };

    responsiveOptions.setActive = function (slideIndex) {
      responsiveOptions.activeSlide
        .removeClass(responsiveOptions.activeSlideClass)
        .addClass(responsiveOptions.pausedSlideClass);
      $(
        responsiveOptions.activeSlide.panelElements[
          responsiveOptions.activeElements[responsiveOptions.activeSlideIndex]
        ]
      ).removeClass(responsiveOptions.elementPanelActiveClass);
      responsiveOptions.activeSlideIndex = slideIndex;

      responsiveOptions.activeSlide = $(responsiveOptions.slides[responsiveOptions.activeSlideIndex]);
      responsiveOptions.activeSlide
        .addClass(responsiveOptions.activeSlideClass)
        .removeClass(responsiveOptions.pausedSlideClass);

      responsiveOptions.activeSlide.elements = null;
      responsiveOptions.activeSlide.panelElements = null;

      responsiveOptions.updateElementsInfo(responsiveOptions.activeSlide);
      responsiveOptions.setActiveElement(responsiveOptions.activeSlide, 0);

      responsiveOptions.setCenter(slideIndex);
    };

    responsiveOptions.setWatched = function (slideIndex) {
      const currentStoryID = this.slides[slideIndex]?.dataset?.sectionId;
      if (currentStoryID) {
        const nodeFrontStoriesSlide = document.querySelector(
          `.stories-slider__item[data-section-id="${currentStoryID}"]`
        );
        if (nodeFrontStoriesSlide) {
          nodeFrontStoriesSlide.classList.remove("stories-item--unseen");
        }

        const cookieName = `${arAsproOptions.SOLUTION_ID.replace(".", "_")}_stories_${arAsproOptions.SITE_ID}`;
        const cookie = $.cookie(cookieName);
        if (cookie) {
          let arStoriesID;
          try {
            arStoriesID = JSON.parse(cookie);
          } catch (error) {
            arStoriesID = [];
          }
          if (!arStoriesID.includes(currentStoryID)) {
            arStoriesID.push(currentStoryID);
            $.cookie(cookieName, JSON.stringify(arStoriesID));
          }
        } else {
          $.cookie(cookieName, JSON.stringify([currentStoryID]));
        }
      }
    };

    responsiveOptions.updateElementsInfo = function (slide) {
      if (responsiveOptions.popoverXHR) {
        responsiveOptions.popoverXHR.abort();
        delete responsiveOptions.popoverXHR;
      }

      if (!slide.elements || !slide.panelElements) {
        slide.elements = slide.find(responsiveOptions.elementsSelector);
        slide.panelElements = slide.find("." + responsiveOptions.elementPanelClass);
      }
      if (!responsiveOptions.activeElements[slide.index()]) {
        responsiveOptions.activeElements[slide.index()] = 0;
      }
    };

    responsiveOptions.setNext = function () {
      responsiveOptions.setWatched(responsiveOptions.activeSlideIndex);

      if (responsiveOptions.activeSlideIndex + 1 < responsiveOptions.slides.length) {
        responsiveOptions.setActive(responsiveOptions.activeSlideIndex + 1);
      } else {
        responsiveOptions.closePopup();
      }
    };

    responsiveOptions.setPrev = function () {
      if (responsiveOptions.activeSlideIndex > 0) {
        responsiveOptions.setActive(responsiveOptions.activeSlideIndex - 1);
      }
    };

    responsiveOptions.setNextElement = function () {
      responsiveOptions.updateElementsInfo(responsiveOptions.activeSlide);
      responsiveOptions.elementTimerContinue = null;

      if (
        responsiveOptions.activeElements[responsiveOptions.activeSlideIndex] + 1 <
        responsiveOptions.activeSlide.elements.length
      ) {
        responsiveOptions.setActiveElement(responsiveOptions.activeSlide, +1);
      } else {
        responsiveOptions.clearSlide(responsiveOptions.activeSlide);
        responsiveOptions.setNext();
      }
    };

    responsiveOptions.setPrevElement = function () {
      responsiveOptions.updateElementsInfo(responsiveOptions.activeSlide);

      if (responsiveOptions.activeElements[responsiveOptions.activeSlideIndex] > 0) {
        responsiveOptions.setActiveElement(responsiveOptions.activeSlide, -1);
      } else {
        responsiveOptions.clearSlide(responsiveOptions.activeSlide);
        responsiveOptions.setPrev();
      }
    };

    responsiveOptions.clearSlide = function (slide) {
      // slide.elements.removeClass(responsiveOptions.activeElementClass);
      // slide.panelElements.removeClass(responsiveOptions.elementPanelActiveClass);
      // slide.panelElements.removeClass(responsiveOptions.elementPanelFinishedClass);
    };

    responsiveOptions.setActiveElement = function (slide, newActiveIndex) {
      if (responsiveOptions.elementTimer) {
        clearTimeout(responsiveOptions.elementTimer);
      }

      var oldActive = slide.elements[responsiveOptions.activeElements[responsiveOptions.activeSlideIndex]];
      var newActive =
        slide.elements[responsiveOptions.activeElements[responsiveOptions.activeSlideIndex] + newActiveIndex];
      $(oldActive).removeClass(responsiveOptions.activeElementClass);
      $(newActive).addClass(responsiveOptions.activeElementClass);
      responsiveOptions.activeElements[slide.index()] += newActiveIndex;

      slide.panelElements.each(function (index, element) {
        var _this = $(element);
        if (index < responsiveOptions.activeElements[slide.index()]) {
          _this.addClass(responsiveOptions.elementPanelFinishedClass);
          _this.removeClass(responsiveOptions.elementPanelActiveClass);
        } else if (index == responsiveOptions.activeElements[slide.index()]) {
          _this.removeClass(responsiveOptions.elementPanelFinishedClass);
          _this.addClass(responsiveOptions.elementPanelActiveClass);
        } else {
          _this.removeClass(responsiveOptions.elementPanelActiveClass);
          _this.removeClass(responsiveOptions.elementPanelFinishedClass);
        }
      });

      if (!window.asproStoriesSliderStopped) {
        responsiveOptions.elementTimerStart = Date.now();
        responsiveOptions.elementTimer = setTimeout(function () {
          responsiveOptions.setNextElement();
        }, responsiveOptions.changeSlideTime);
      }
    };

    responsiveOptions.refreshActiveElement = function () {
      const nodeActiveElement =
        responsiveOptions.activeSlide.panelElements[
          responsiveOptions.activeElements[responsiveOptions.activeSlideIndex]
        ];

      responsiveOptions.elementTimerContinue = responsiveOptions.animationTimer;

      nodeActiveElement.classList.remove(responsiveOptions.elementPanelActiveClass);
      setTimeout(() => {
        nodeActiveElement.classList.add(responsiveOptions.elementPanelActiveClass);
      }, 0);
    };

    responsiveOptions.pauseSlide = function (slide) {
      var index = slide.data("index");
      index = index ? index : slide.index();

      slide.addClass(responsiveOptions.pausedSlideClass);
      responsiveOptions.stoppedSlideIndex = index;
      if (responsiveOptions.elementTimer) {
        clearTimeout(responsiveOptions.elementTimer);
        responsiveOptions.animationTimer = responsiveOptions.elementTimerContinue
          ? responsiveOptions.elementTimerContinue
          : responsiveOptions.changeSlideTime;
        responsiveOptions.elementTimerContinue =
          responsiveOptions.animationTimer - (Date.now() - responsiveOptions.elementTimerStart);
      }
    };

    responsiveOptions.playSlide = function (slide) {
      var index = slide.data("index");
      index = index ? index : slide.index();

      if (index == responsiveOptions.stoppedSlideIndex) {
        slide.removeClass(responsiveOptions.pausedSlideClass);
        responsiveOptions.stoppedSlideIndex = null;
        if (!window.asproStoriesSliderStopped) {
          responsiveOptions.elementTimerStart = Date.now();
          responsiveOptions.elementTimer = setTimeout(function () {
            responsiveOptions.setNextElement();
          }, responsiveOptions.elementTimerContinue);
        }
      }
    };

    responsiveOptions.addDragEvents = function () {
      responsiveOptions.slides.on("touchstart", function (event) {
        var _this = $(this);
        var index = _this.data("index");
        index = index ? index : _this.index();

        if (index == responsiveOptions.activeSlideIndex) {
          if (!responsiveOptions.mobile) {
            responsiveOptions.touch.posPrev = event.originalEvent.changedTouches[0].pageX;
          }
        }
      });

      responsiveOptions.slides.on("touchmove", function (event) {
        var _this = $(this);
        var index = _this.data("index");
        index = index ? index : _this.index();

        if (index == responsiveOptions.activeSlideIndex) {
          if (!responsiveOptions.mobile) {
            responsiveOptions.touch.posCurrent =
              event.originalEvent.changedTouches[0].pageX - responsiveOptions.touch.posPrev;
            responsiveOptions.sliderInner.css({
              "margin-left": responsiveOptions.sliderInnerMargin + responsiveOptions.touch.posCurrent,
            });
            responsiveOptions.sliderInnerMargin =
              responsiveOptions.sliderInnerMargin + responsiveOptions.touch.posCurrent;
            responsiveOptions.touch.posPrev = event.originalEvent.changedTouches[0].pageX;
          }
        }
      });

      responsiveOptions.slides.on("mousedown", function (event) {
        var _this = $(this);
        var index = _this.data("index");
        index = index ? index : _this.index();

        if (index == responsiveOptions.activeSlideIndex) {
          if (!responsiveOptions.mobile) {
            var startX = event.pageX;

            function moveAt(pageX) {
              var left = pageX - startX > 0 ? pageX - startX : startX - pageX;
              var movePercent = left / responsiveOptions.dragAmount;
              if (
                (pageX - startX > 0 && responsiveOptions.activeSlideIndex > 0) ||
                (pageX - startX < 0 && responsiveOptions.activeSlideIndex < responsiveOptions.slides.length)
              ) {
                var dependSlide = pageX - startX > 0 ? _this.prev() : _this.next();
                var cssCurrent = {
                  "margin-left":
                    responsiveOptions.activeSlideMargin - responsiveOptions.activeSlideMargin * movePercent + "vw",
                  "margin-right":
                    responsiveOptions.activeSlideMargin - responsiveOptions.activeSlideMargin * movePercent + "vw",
                  transform: "scale(" + (1 - 0.25 * movePercent) + ")",
                };
                var cssDepend = {
                  "margin-left": responsiveOptions.activeSlideMargin * movePercent + "vw",
                  "margin-right": responsiveOptions.activeSlideMargin * movePercent + "vw",
                  transform: "scale(" + (0.75 + 0.25 * movePercent) + ")",
                };

                _this.css(cssCurrent);
                dependSlide.css(cssDepend);
              }
            }

            function onMouseMove(event) {
              moveAt(event.pageX);
            }

            document.addEventListener("mousemove", onMouseMove);

            $(document).on("mouseup", function () {
              document.removeEventListener("mousemove", onMouseMove);
              this.onmouseup = null;
            });

            this.ondragstart = function () {
              return false;
            };
          }
        }
      });
    };

    responsiveOptions.bindEvents = function () {
      BX.loadScript(arAsproOptions.SITE_TEMPLATE_PATH + "/js/jquery.mobile.custom.touch.min.js", () => {
        responsiveOptions.slides.on("swiperight", function () {
          if (responsiveOptions.mobile) {
            const nodeElementWithSwipeignore = this.querySelector(".stories-popup__element.swipeignore");
            if (!nodeElementWithSwipeignore) {
              responsiveOptions.setPrev();
            }
          }
        });

        responsiveOptions.slides.on("swipeleft", function () {
          if (responsiveOptions.mobile) {
            const nodeElementWithSwipeignore = this.querySelector(".stories-popup__element.swipeignore");
            if (!nodeElementWithSwipeignore) {
              responsiveOptions.setNext();
            }
          }
        });
      });

      responsiveOptions.slides.on("click", function () {
        var _this = $(this);
        var index = _this.data("index");
        index = index ? index : _this.index();

        if (index != responsiveOptions.activeSlideIndex) {
          responsiveOptions.setActive(index);
        }
      });

      responsiveOptions.slides.on("taphold", function () {
        const nodeElementWithSwipeignore = this.querySelector(".stories-popup__element.swipeignore");
        if (!nodeElementWithSwipeignore) {
          responsiveOptions.pauseSlide($(this));
        }
      });

      responsiveOptions.slides.on("touchend", function () {
        const nodeElementWithSwipeignore = this.querySelector(".stories-popup__element.swipeignore");
        if (!nodeElementWithSwipeignore) {
          responsiveOptions.playSlide($(this));
        }
      });

      responsiveOptions.slides.on("mousedown", function () {
        if (this.querySelector(".xpopover.show")) {
          return;
        }

        const timeout = 1000;
        responsiveOptions.mousedownTimer = setTimeout(() => {
          responsiveOptions.mouseHold = true;
          responsiveOptions.pauseSlide($(this));
        }, timeout);
      });

      responsiveOptions.slides.on("mousemove", function () {
        if (responsiveOptions.mousedownTimer) {
          clearTimeout(responsiveOptions.mousedownTimer);
          responsiveOptions.mouseHold = false;
        }
      });

      responsiveOptions.slides.on("mouseup", function (e) {
        if (this.querySelector(".xpopover.show")) {
          return;
        }

        if (responsiveOptions.mouseHold) {
          responsiveOptions.playSlide($(this));
        } else {
          if (responsiveOptions.mousedownTimer) {
            clearTimeout(responsiveOptions.mousedownTimer);
            responsiveOptions.mouseHold = false;
          }
        }
      });

      responsiveOptions.slides.on("click", "." + responsiveOptions.elementNavPrevClass, function (e) {
        if (!responsiveOptions.mouseHold) {
          var _this = $(this);
          var _thisSlide = _this.closest(responsiveOptions.slidesSelector);
          var index = _thisSlide.data("index");
          index = index ? index : _thisSlide.index();

          if (index == responsiveOptions.activeSlideIndex) {
            responsiveOptions.setPrevElement();
            e.preventDefault();
            e.stopPropagation();
          }
        } else {
          responsiveOptions.mouseHold = false;
        }
      });

      responsiveOptions.slides.on("click", "." + responsiveOptions.elementNavNextClass, function (e) {
        if (!responsiveOptions.mouseHold) {
          var _this = $(this);
          var _thisSlide = _this.closest(responsiveOptions.slidesSelector);
          var index = _thisSlide.data("index");
          index = index ? index : _thisSlide.index();

          if (index == responsiveOptions.activeSlideIndex) {
            responsiveOptions.setNextElement();
            e.preventDefault();
            e.stopPropagation();
          }
        } else {
          responsiveOptions.mouseHold = false;
        }
      });

      responsiveOptions.slides.on("click", "._show-linked-items", function () {
        responsiveOptions.pauseSlide(responsiveOptions.activeSlide);

        const nodeSlide = this.closest(".stories-popup__element");
        nodeSlide.classList.add("swipeignore");

        if (this.dataset.xpopover) {
          return;
        }

        const id = nodeSlide?.dataset?.id;
        const params = responsiveOptions.originalBlock.data("params");
        if (!id || !params) {
          return;
        }

        this.classList.add("loadings");
        this.disabled = true;

        BX.ajax
          .runAction(`${arAsproOptions.SOLUTION_ID.replace(".", ":")}.Stories.getItems`, {
            data: { id, params },
            onrequeststart: function (xhr) {
              responsiveOptions.popoverXHR = xhr;
            },
          })
          .then((response) => {
            delete responsiveOptions.popoverXHR;

            BX.Aspro.Loader.addExt("popover").then(() => {
              this.setAttribute(
                "data-xpopover",
                JSON.stringify({
                  hideOnChanged: false,
                  overlay: true,
                  placement: "nested",
                })
              );

              this.classList.add("xpopover-toggle");

              const xpopover = BX.Aspro.xPopover.get(this);
              xpopover.onHide = () => {
                responsiveOptions.playSlide(responsiveOptions.activeSlide);
                nodeSlide.classList.remove("swipeignore");
              };

              xpopover.onShow = () => {
                responsiveOptions.refreshActiveElement();

                if (typeof window.JItemAction === "function") {
                  JItemAction.markItems();
                }
              };

              xpopover.setContent(response.data);
              xpopover.toggle();

              this.classList.remove("loadings");
              this.disabled = false;
            });
          })
          .catch((response) => {
            if (response.errors) {
              response.errors.forEach((error) => {
                JNoticeSurface.get().onResultError({ error: error.message });
              });
            }

            responsiveOptions.playSlide(responsiveOptions.activeSlide);
            this.classList.remove("loadings");
            this.disabled = false;
            nodeSlide.classList.remove("swipeignore");
          });
      });

      responsiveOptions.slides.on("click", "._close", function () {
        const xpopover = this.closest(".xpopover")?.xpopover;
        if (xpopover) {
          xpopover.hide();
        }
      });

      BX.addCustomEvent("onWindowResize", responsiveOptions.onResize);

      BX.bind(document, "click", responsiveOptions.onClose);
    };

    responsiveOptions.unbindEvents = function () {
      BX.unbind(document, "click", responsiveOptions.onClose);

      BX.removeCustomEvent("onWindowResize", responsiveOptions.onResize);
    };

    responsiveOptions.onClose = function (e) {
      if (
        !$(e.target).closest(".stories-popup__element-slider").length ||
        $(e.target).closest(".stories-popup__close").length
      ) {
        const instance = $(e.target)
          .closest(".stories-popup")
          .find(".stories-popup__section-slider")
          .data("asproStoriesSlider");
        if (instance) {
          instance.closePopup();
        }

        document.body.classList.remove('overflow-block');
      }
    };

    responsiveOptions.onResize = function () {
      try {
        responsiveOptions.update();
      } catch (e) {
        console.log(e);
      }
    };

    responsiveOptions.updateSlidesHeight = function () {
      if (responsiveOptions.mobile) {
        responsiveOptions.slideWidth = responsiveOptions.sliderWidth;
        responsiveOptions.slides.css({
          width: "",
        });
      } else {
        responsiveOptions.slideWidth = (responsiveOptions.sliderHeight + 80) / 1.98;
        responsiveOptions.slides.css({
          width: responsiveOptions.slideWidth,
        });
      }
    };

    responsiveOptions.update = function () {
      var activeSlide = responsiveOptions.activeSlideIndex;
      responsiveOptions = Object.assign(responsiveOptions, options);
      for (var width in options.breakpoints) {
        if (window.matchMedia(width).matches) {
          responsiveOptions = Object.assign(responsiveOptions, options.breakpoints[width]);
        }
      }

      responsiveOptions.activeSlideIndex = activeSlide;
      responsiveOptions.getCurrentSizes();
      responsiveOptions.setCenter(responsiveOptions.activeSlideIndex);
    };

    responsiveOptions.init = function (_slider) {
      responsiveOptions.slides = _slider.find(responsiveOptions.slidesSelector);
      responsiveOptions.activeSlide = $(responsiveOptions.slides[responsiveOptions.activeSlideIndex]);
      responsiveOptions.sliderInner = _slider.find(responsiveOptions.innerSelector);
      responsiveOptions.slider = _slider;
      responsiveOptions.sliderWidth = responsiveOptions.slider.outerWidth(true);
      responsiveOptions.slides.addClass("swipeignore");
      responsiveOptions.touch = {};
      responsiveOptions.activeElements = {};

      responsiveOptions.setStyles();
      responsiveOptions.getCurrentSizes();
      responsiveOptions.setCenter(responsiveOptions.activeSlideIndex);

      responsiveOptions.updateElementsInfo(responsiveOptions.activeSlide);
      responsiveOptions.setActiveElement(responsiveOptions.activeSlide, 0);

      responsiveOptions.bindEvents();
      _slider.data("asproStoriesSlider", responsiveOptions);
    };

    responsiveOptions.init(_slider);
    setTimeout(function () {
      responsiveOptions.slider.addClass("aspro_slider_init");
    }, responsiveOptions.transition * 1000 + 10);
  }

  var el = $(this);

  if (el.hasClass("aspro_slider_init")) return false;

  el.each(function (i, slider) {
    _storiesSlider(slider, options);
  });
};

BX.Aspro.Utils.readyDOM(() => {
  const nodeStoriesSliderItems = document.querySelectorAll(".stories-slider__item");
  if (nodeStoriesSliderItems.length) {
    let storiesItems = $.cookie(`${arAsproOptions.SOLUTION_ID.replace(".", "_")}_stories_${arAsproOptions.SITE_ID}`);
    if (storiesItems) {
      try {
        storiesItems = JSON.parse(storiesItems);
      } catch (error) {
        storiesItems = [];
      }
    } else {
      storiesItems = [];
    }

    nodeStoriesSliderItems.forEach((nodeStoriesSliderItem) => {
      if (!storiesItems.includes(nodeStoriesSliderItem.dataset.sectionId)) {
        nodeStoriesSliderItem.classList.add("stories-item--unseen");
      }
    });
  }
});

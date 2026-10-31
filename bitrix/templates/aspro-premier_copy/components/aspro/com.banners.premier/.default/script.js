let progressBar = null;

function checkNavColor(slider) {
  let menu_color = slider.find('.swiper-slide-active').data('color');

  checkNavColor.hasLongBanner = $('.header_opacity').length;
  if (checkNavColor.hasLongBanner) {
    var logoOpacity = $('body.header_opacity').length;
    var bLogoImg = $('body:not(.left_header_column) header .logo img').length;

    if (
      bLogoImg &&
      logoOpacity
    ) {
      $('header .logo img').attr('src', arAsproOptions.THEME.LOGO_IMAGE);
    }

    $('header').removeClass('theme-light theme-dark');
    $('header').addClass('theme-' + (menu_color === 'light' ? 'dark' : 'light'));

    if (
      menu_color === 'light' &&
      bLogoImg &&
      logoOpacity &&
      arAsproOptions.THEME.LOGO_IMAGE_WHITE
    ) {
      $('header .logo img').attr('src', arAsproOptions.THEME.LOGO_IMAGE_WHITE);
    }
  }

  // swiper-pagintaion & navigation background color change
  if (menu_color) {
    slider.find('.banners-big__nav-wrap').removeClass('theme-light theme-dark');
    slider.find('.banners-big__nav-wrap').addClass('theme-' + (menu_color === 'light' ? 'dark' : 'light'));
  }

  var eventdata = {slider: slider};
  BX.onCustomEvent('onSlide', [eventdata]);
}

readyDOM(function () {
  $(".main-slider").mouseenter(function () {
    let swiper = $(this).data("swiper");
    if (!$(this).hasClass("video_visible") && swiper) {
      if (swiper?.params.autoplay.enabled) {
        if (!swiper.autoplay.paused) {
          swiper.autoplay.pause();
        }
      }
    }
  });

  $(".main-slider").mouseleave(function () {
    let swiper = $(this).data("swiper");
    if (!$(this).hasClass("video_visible") && swiper) {
      if ($(this).data("swiper")?.params.autoplay.enabled) {
        if (swiper.autoplay.paused) {
          $(this).data("swiper").autoplay.resume();
        }
      }
    }
  });
});

BX.addCustomEvent("onSetSliderOptions", function (options) {
  if ("type" in options && options.type === "main_banner") {
    if (typeof arAsproOptions["THEME"] != "undefined") {
      const slideshowSpeed = Math.abs(parseInt(arAsproOptions["THEME"]["BIGBANNER_SLIDESSHOWSPEED"]));
      const animationSpeed = Math.abs(parseInt(arAsproOptions["THEME"]["BIGBANNER_ANIMATIONSPEED"]));

      options.autoplay = slideshowSpeed && arAsproOptions["THEME"]["BIGBANNER_ANIMATIONTYPE"].length ? {} : false;
      // options.autoplay.pauseOnMouseEnter = true;
      // options.autoplay.disableOnInteraction = false;
      options.effect = arAsproOptions["THEME"]["BIGBANNER_ANIMATIONTYPE"] === "FADE" ? "fade" : "slide";
      if (animationSpeed >= 0) {
        options.speed = animationSpeed;
      }
      if (slideshowSpeed >= 0) {
        options.autoplay.delay = slideshowSpeed;
      }
      /*if (arAsproOptions["THEME"]["BIGBANNER_ANIMATIONTYPE"] !== "FADE") {
          options.direction =
            arAsproOptions["THEME"]["BIGBANNER_ANIMATIONTYPE"] === "SLIDE_VERTICAL" ? "vertical" : "horizontal";
        }*/
    }

    if ("CURRENT_BANNER_INDEX" in arAsproOptions && arAsproOptions["CURRENT_BANNER_INDEX"]) {
      currentBannerIndex = arAsproOptions["CURRENT_BANNER_INDEX"] - 1;
      if (currentBannerIndex < options.countSlides) {
        options.initialSlide = currentBannerIndex;
        // options.autoplay = false;
      }
    }

    if (!options.on) {
      options.on = {}
    }
    
    options.on.autoplayTimeLeft = (swiper, time, progress) => {
      if (progressBar) {
        progressBar.value = 1 - progress;
      }
    }      
  }
});

BX.addCustomEvent("onSlideChanges", function (eventdata) {
  if ("slider" in eventdata && eventdata.slider) {
    const slider = eventdata.slider;
    if (slider && slider.params) {
      if ("type" in slider.params && slider.params.type === "main_banner") {
        setTimeout(function () {
          checkNavColor($(slider.el));
        }, 100);

        if (
          slider.pagination 
          && slider.pagination.bullets
          && slider.pagination.bullets[slider.activeIndex]
        ) {
          slider.pagination.el.querySelector('.swiper-pagination-bullet-active').append(progressBar);
        }
      }
    }
  }
});

BX.addCustomEvent('onInitSlider', (eventdata) => {
  if (!eventdata.options) return;

  if ("type" in eventdata.options && eventdata.options.type === "main_banner") {
    progressBar = document.querySelector(".autoplay-progress");
    
    if (
      eventdata.slider
      && eventdata.slider.pagination 
      && eventdata.slider.pagination.bullets
      && eventdata.slider.pagination.bullets[0]
    ) {
      eventdata.slider.pagination.bullets[0].append(progressBar);
    }
  }
});
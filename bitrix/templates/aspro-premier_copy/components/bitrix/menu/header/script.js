BX.Aspro.Utils.readyDOM(() => {
  $(document).on("click", ".header-menu__wide-submenu-item-inner .toggle_block", function (e) {
    e.preventDefault();

    var _this = $(this),
      menu = _this.closest(".header-menu__wide-submenu-item-inner").find("> .submenu-wrapper");

    if (!_this.hasClass("clicked")) {
      _this.addClass("clicked");

      menu.slideToggle(150, function () {
        _this.removeClass("clicked");
      });

      _this.closest(".header-menu__wide-submenu-item-inner").toggleClass("opened");
    }
  });

  BX.bindDelegate(document, 'click', {class: 'header-menu__link--only-catalog'}, function (e) { 
    e.preventDefault();

    const catalogButton = this;
    const nodeSvgUse = catalogButton.querySelector('use');
    const svgLink = nodeSvgUse.getAttribute('xlink:href');
    
    if (catalogButton.classList.contains('opened')) {
      catalogButton.classList.remove('opened');
      nodeSvgUse.setAttribute('xlink:href', svgLink.replace('#close', '#burger'));
    } else {
      catalogButton.classList.add('opened');
      nodeSvgUse.setAttribute('xlink:href', svgLink.replace('#burger', '#close'));
      
      onlyCatalogMenuOpen($(catalogButton));
    }

    onlyCatalogMenuClose(catalogButton);
  });

  BX.bindDelegate(document, "click", {tag: 'body'}, function (e) {
    const catalogButton = document.querySelector('.header-menu__link--only-catalog.opened');
    const isMenu = e.target.closest('.header-menu__wrapper');

    if (catalogButton && !isMenu) {
      onlyCatalogMenuClose();
    }
  });

  onlyCatalogMenuClose = function (currentButton = null) { 
    let nodeListCatalogButton = document.querySelectorAll('.header-menu__link--only-catalog.opened');
    if (
      !nodeListCatalogButton.length
      || (nodeListCatalogButton.length === 1 && nodeListCatalogButton[0] === currentButton)
    ) return;

    nodeListCatalogButton.forEach(nodeCatalogButton => {
      if (nodeCatalogButton !== currentButton) {
        const currentSvg = nodeCatalogButton.querySelector('use');
        const currentSvgLink = currentSvg.getAttribute('xlink:href');
        nodeCatalogButton.classList.remove('opened');
        currentSvg.setAttribute('xlink:href', currentSvgLink.replace('#close', '#burger'));
      }
    })
  }

  onlyCatalogMenuOpen = function (catalogButton) {
    var menuNLO = catalogButton.siblings("[data-nlo]");

    if (menuNLO.length) {
      if (!menuNLO.hasClass("nlo-loadings")) {
        menuNLO.addClass("nlo-loadings");
        var menuCatalog = $(".header-menu__link--only-catalog ~ .header-menu__dropdown-menu");
        if(menuCatalog.length){
          menuNLO.replaceWith(menuCatalog.clone());        
          catalogButton.parent().find('.aim-init').removeClass('aim-init');
          if (BX.Aspro.Utils.isFunction("InitMenuNavigationAim")) {
            InitMenuNavigationAim();
          }
        } else {
          var buttonTitle = catalogButton.find(".header-menu__title");
          buttonTitle.addClass("loadings");
          $.ajax({
            data: { nlo: menuNLO.attr("data-nlo") },
            error: function () {
              menuNLO.removeClass("nlo-loadings");
            },
            complete: function (jqXHR, textStatus) {
              if (textStatus === "success" || jqXHR.status == 404) {
                var ob = BX.processHTML($.trim(jqXHR.responseText));
                BX.ajax.processScripts(ob.SCRIPT);
                menuNLO.replaceWith(ob.HTML);
                if (BX.Aspro.Utils.isFunction("InitMenuNavigationAim")) {
                  InitMenuNavigationAim();
                }
              }
              buttonTitle.removeClass("loadings");
            },
          });
        }
      }
    }
  };

  BX.addCustomEvent("onAjaxChangeWidgetValue", function (eventdata) {
    if (eventdata && eventdata["NAME"] === "PAGE_WIDTH") {
      if (BX.Aspro.Utils.isFunction("CheckTopMenuDotted")) {
        CheckTopMenuDotted();
      }
    }
  });
  
});

BX.Aspro.Utils.readyDOM(() => {
  $(document).on(
    'click',
    '.mobilemenu__menu--catalog-wide .mobilemenu__menu-item--parent > .link-wrapper > .mobilemenu__item-link',
    function (e) {
      e.preventDefault();

      const $toggle = $(this).siblings('.toggle_block');
      if ($toggle.length) {
        $toggle.trigger('click');
      }
    }
  );
});

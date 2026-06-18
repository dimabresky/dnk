(function () {
  if (window.__dnkMobileMenuParentExpandBound) {
    return;
  }

  window.__dnkMobileMenuParentExpandBound = true;

  const PARENT_ROW_SELECTOR = '.mobilemenu__menu-item--parent > .link-wrapper';

  const handleParentRowClick = (e) => {
    if (!e.isTrusted) {
      return;
    }

    if (e.target.closest('.mobilemenu__menu-parent-link')) {
      return;
    }

    if (e.target.closest('.toggle_block')) {
      return;
    }

    const wrapper = e.target.closest(PARENT_ROW_SELECTOR);
    if (!wrapper) {
      return;
    }

    const toggle = wrapper.querySelector(':scope > .toggle_block');
    if (!toggle) {
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    toggle.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
  };

  BX.Aspro.Utils.readyDOM(() => {
    document.addEventListener('click', handleParentRowClick, true);
  });
})();

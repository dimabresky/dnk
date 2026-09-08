export function mkResult() {
  return new Promise((resolve, reject) => {
    let result = '';

    const oCtr = this.getCtr();

    if (
      BX.Type.isObject(oCtr)
      && oCtr.RESULT
      && BX.Type.isObject(BX.Aspro.SmartSearch.History.Storage)
    ) {
      const items = BX.Aspro.SmartSearch.History.Storage.get();
      if (items.length) {
        const action = oCtr.INPUT.closest('form').getAttribute('action');
        const url = new URL(action, location.origin);
        const type = oCtr.TYPE ? oCtr.TYPE.value : '';
        if (type) {
          url.searchParams.set('type', type);
        }

        result = `
          <div class="search-result search-result--offset scrollbar mt mt--8 search-history-list">
            <div class="line-block line-block--gap flexbox--wrap line-block--align-normal pt pt--12 pb pb--12">
        `;

        items.forEach((item, i) => {
          result += `
            <div class="line-block__item search-history__item">
              <div class="font_14 search-history-list__name">
                <a class="chip chip--toggle bg-theme-active color-theme-hover-no-active" href="` + url.toString() + (url.searchParams.size ? '&' : '?') + 'q=' + item.QUERY_URLENCODED + `">
                  <span class="search-history__item__label chip__label">` + item.QUERY + `</span>
                  <span class="search-history__item__btn--delete chip__icon" title="` + BX.message('SEARCH_HISTORY_DELETE_ITEM') +`">
                    <i class="svg inline fill-dark-light-block opacity_5" aria-hidden="true"><svg width="12" height="12"><use xlink:href="` + arAsproOptions.SITE_TEMPLATE_PATH + `/images/svg/catalog/item_icons.svg?#close-12-12"></use></svg></i>
                  </span>
                </a>
              </div>
            </div>
          `;
        });

        result += `
          <div class="line-block__item search-history__item search-history-list__btn--clean">
            <div class="font_14 search-history-list__name">
              <a class="chip chip--transparent bordered" href="javascript:;">
                <span class="chip__label">` + BX.message('SEARCH_HISTORY_DELETE_ALL') + `</span>
              </a>
            </div>
          </div>
        `;

        result += `
            </div>
          </div>
        `;
      }
    }


    resolve(result);
  });
}

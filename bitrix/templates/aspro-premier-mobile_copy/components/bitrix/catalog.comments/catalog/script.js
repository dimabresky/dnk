BX.addCustomEvent('onAsproLoadBlogResult', () => {
  const nodeFormTemplate = document.getElementById('review-form-template');
  const nodeMobileFilter = document.getElementById('mobilefilter');

  if (nodeFormTemplate && nodeMobileFilter) {
    nodeMobileFilter.innerHTML = '';
    nodeMobileFilter.appendChild(nodeFormTemplate.content.cloneNode(true));

    nodeMobileFilter.classList.add('scrollbar-filter--comments');
  }
});

document.addEventListener('DOMContentLoaded', function() {
	const $jsBlocks = document.querySelectorAll('[data-js-block]');

	if ($jsBlocks.length) {
		for (let i = 0; i < $jsBlocks.length; i++) {
			const $container = $jsBlocks[i];
			const $block = $container.dataset.jsBlock
				? document.querySelector($container.dataset.jsBlock)
				: false;
			
			if ($block) {
				$container.appendChild($block);
				$container.removeAttribute(['data-js-block'])
			}
		}
	}

	const detailExpandRoots = document.querySelectorAll('[data-catalog-detail-detail-text-expand]');
	if (detailExpandRoots.length) {
		const measureOverflow = function(inner) {
			return inner.scrollHeight > inner.clientHeight + 1;
		};

		const bindExpand = function(wrap) {
			const inner = wrap.querySelector('.catalog-detail__detail-text-inner');
			const btn = wrap.querySelector('.catalog-detail__detail-text-expand-btn--toggle');
			if (!inner || !btn) {
				return;
			}

			const updateToggleVisibility = function() {
				if (wrap.classList.contains('catalog-detail__detail-text-wrap--expanded')) {
					btn.hidden = false;
					return;
				}
				btn.hidden = !measureOverflow(inner);
			};

			requestAnimationFrame(function() {
				requestAnimationFrame(updateToggleVisibility);
			});

			btn.addEventListener('click', function() {
				wrap.classList.toggle('catalog-detail__detail-text-wrap--expanded');
				requestAnimationFrame(updateToggleVisibility);
			});
		};

		for (let j = 0; j < detailExpandRoots.length; j++) {
			bindExpand(detailExpandRoots[j]);
		}
	}
})
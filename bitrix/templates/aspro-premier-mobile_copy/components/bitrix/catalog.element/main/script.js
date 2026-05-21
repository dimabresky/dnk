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
		/**
		 * При -webkit-line-clamp часто scrollHeight === clientHeight, хотя текст обрезан.
		 * Сравниваем высоту блока со снятым clamp (клон) с видимой высотой.
		 */
		const measureOverflow = function(inner) {
			const visibleH = inner.offsetHeight || inner.getBoundingClientRect().height;

			let width = inner.offsetWidth || inner.getBoundingClientRect().width;

			const ghost = inner.cloneNode(true);
			ghost.classList.remove('catalog-detail__detail-text-inner');

			ghost.style.position = 'absolute';
			ghost.style.left = '0';
			ghost.style.top = '0';
			ghost.style.width = width ? width + 'px' : '100%';
			ghost.style.visibility = 'hidden';
			ghost.style.pointerEvents = 'none';
			ghost.style.zIndex = '-1';
			ghost.style.display = 'block';
			ghost.style.overflow = 'visible';
			ghost.style.setProperty('-webkit-line-clamp', 'unset');
			ghost.style.setProperty('-webkit-box-orient', 'unset');

			inner.parentNode.insertBefore(ghost, inner.nextSibling);
			let fullHeight = ghost.offsetHeight;
			const scrollHeight = ghost.scrollHeight;

			if (scrollHeight > fullHeight + 2) {
				fullHeight = scrollHeight;
			}

			ghost.remove();

			return fullHeight > visibleH + 4;
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

			if (typeof ResizeObserver !== 'undefined') {
				const resizeObserver = new ResizeObserver(function() {
					requestAnimationFrame(updateToggleVisibility);
				});
				resizeObserver.observe(inner);
			}
		};

		for (let j = 0; j < detailExpandRoots.length; j++) {
			bindExpand(detailExpandRoots[j]);
		}
	}
})
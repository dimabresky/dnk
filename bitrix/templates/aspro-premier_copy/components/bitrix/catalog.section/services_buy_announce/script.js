BX.Aspro.Utils.readyDOM(() => {
	BX.bindDelegate(
		document,
		'click',
		{
			class: 'services-items__more',
		},
		(event) => {
			let block = document.querySelector('.detail-block.buy_services');
			if (block) {
				scrollToBlock(block);
				return;
			}

			event = event || window.event;
			let target = event.target;
			let more = target.closest('.services-items__more');
			let bOpened = more.dataset.bOpened == 1;

			bOpened = bOpened ? 0 : 1;
			more.dataset.bOpened = bOpened;

			let text = bOpened ? more.dataset.close : more.dataset.open;
			more.querySelector('span').innerText = text;

			let items = more.closest('.services-items');
			if (items) {
				if (bOpened) {
					items.classList.add('services-items--show-all');
				}
				else {
					items.classList.remove('services-items--show-all');
				}
			}
		}
	);
});
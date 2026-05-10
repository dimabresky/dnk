BX.addCustomEvent(
	'onCounterGoals',
	function (eventdata) {
		try {
			if (
				eventdata.goal &&
				eventdata.params &&
				eventdata.params.id &&
				(
					eventdata.goal === 'goal_wish_add' ||
					eventdata.goal === 'goal_wish_remove'
				)
			) {
				requestFavorit();
			}
		}
		catch (e) {
			console.error(e);
		}
	}
);

document.addEventListener('click', function(e){
	const label = e.target.closest('.js_clear_favorits');

	if (label) {
		const action = JItemActionFavorite.prototype.action.toUpperCase();

		if (
			typeofExt(arAsproCounters) === 'object' &&
			typeofExt(arAsproCounters[action]) === 'object' &&
			typeofExt(arAsproCounters[action].ITEMS) === 'object'
		) {
			BX.ajax({
				url: JItemAction.prototype.requestUrl,
				data: {
					action: JItemActionFavorite.prototype.action.toLowerCase(),
					type: 'multiple',
					items: Object.keys(arAsproCounters[action].ITEMS),
					sessid: BX.bitrix_sessid(),
				},
				method: 'POST',
				dataType: 'json',
				async: true,
				onsuccess: function(data) {
					label.remove();
					arAsproCounters[action].COUNT = data.count;

					requestFavorit('.personal__block--favorite-products .catalog-block');
				}
			});	
		}
	}
});

function requestFavorit(selector){
	const selectorNode = selector || '.personal__block--favorite-products .js_append.ajax_load';

	BX.ajax({
		url: location.href,
		data: {
			action: 'reload',
			ajax: 'y',
			sessid: BX.bitrix_sessid(),
		},
		method: 'POST',
		dataType: 'html',
		async: true,
		onsuccess: function(html) {
			var ob = BX.processHTML(html);

			const action = JItemActionFavorite.prototype.action.toUpperCase();
			if (arAsproCounters[action].COUNT) {
				$(selectorNode).html(ob.HTML);
			}
			else {
				$('.js_clear_favorits').remove();
				$(selectorNode).closest('.personal__block--favorite-products').html(ob.HTML);
			}

			JItemActionFavorite.markItems();
		}
	});
}

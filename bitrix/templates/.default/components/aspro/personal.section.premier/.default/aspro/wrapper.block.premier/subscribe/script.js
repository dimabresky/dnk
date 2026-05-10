BX.addCustomEvent(
	'onCounterGoals',
	function (eventdata) {
		try {
			if (
				eventdata.goal &&
				eventdata.params &&
				eventdata.params.id &&
				(
					eventdata.goal === 'goal_subscribe_add' ||
					eventdata.goal === 'goal_subscribe_remove'
				)
			) {
				requestSubscription();
			}
		}
		catch (e) {
			console.error(e);
		}
	}
);

document.addEventListener('click', function(e){
	const label = e.target.closest('.js_clear_subscription');

	if (label) {
		const action = JItemActionSubscribe.prototype.action.toUpperCase();

		if (
			typeofExt(arAsproCounters) === 'object' &&
			typeofExt(arAsproCounters[action]) === 'object' &&
			typeofExt(arAsproCounters[action].ITEMS) === 'object'
		) {
			BX.ajax({
				url: JItemAction.prototype.requestUrl,
				data: {
					action: JItemActionSubscribe.prototype.action.toLowerCase(),
					type: 'multiple',
					items: Object.values(arAsproCounters[action].ITEMS),
					sessid: BX.bitrix_sessid(),
				},
				method: 'POST',
				dataType: 'json',
				async: true,
				onsuccess: function(data) {
					label.remove()
					arAsproCounters[action].COUNT = data.count;

					requestSubscription('.personal__block--subscribe-products .catalog-block');
				}
			});				
		}
	}
});

function requestSubscription(selector){
	const selectorNode = selector || '.personal__block--subscribe-products .js_append.ajax_load';

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

			const action = JItemActionSubscribe.prototype.action.toUpperCase();
			if (arAsproCounters[action].COUNT) {
				$(selectorNode).html(ob.HTML);
			}
			else {
				$(selectorNode).closest('.personal__block--subscribe-products').html(ob.HTML);
			}

			JItemActionSubscribe.markItems();
		}
	});
}

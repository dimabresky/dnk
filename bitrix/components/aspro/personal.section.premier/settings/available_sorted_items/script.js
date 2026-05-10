function initAvailableSortedItems(params) {
	let data = JSON.parse(params.data);
	if (data) {
		BX.loadScript('/bitrix/js/main/core/core_dragdrop.js', function() {
			(function waiter() {
				if (!!BX.DragDrop) {
					window['dnd_parameter_' + params.propertyID] = new AvailableSortedItems(data, params);
				}
				else {
					setTimeout(waiter, 50);
				}
			})();
		});
	}
}

function AvailableSortedItems(items, params) {
	let rand = BX.util.getRandomString(5);

	this.params = typeof params === 'object' && params ? params : {};

	BX.loadCSS(this.getPath() + '/style.css?' + rand);

	this.items = this.getSortedItems(items);
	this.rootElementId = 'asi-params__container--' + this.params.propertyID + '-' + rand;
	this.dragItemClassName = 'asi-params__block--' + this.params.propertyID + '-' + rand;

	// this setTimeout() fixes checkbox`s styles after refresh (change event)
	setTimeout(() => {
		this.buildNodes();
		this.initDragDrop();
	}, 0);
}

AvailableSortedItems.prototype = {
	getPath: function() {
		let path = this.params.propertyParams.JS_FILE.split('/');
		path.pop();

		return path.join('/');
	},

	getSortedItems: function(items) {
		if (!items || typeof(items) !== 'object')
			return [];

		let availableSortedItems = items.available;
		if (!availableSortedItems || typeof(availableSortedItems) !== 'object')
			return [];

		let refreshItems = items.refresh;
		if (!refreshItems || typeof(refreshItems) !== 'object') {
			refreshItems = [];
		}

		let inputValue = this.params.oInput.value || this.params.propertyParams.DEFAULT || '',
			result = [],
			k;

		let values = inputValue.split(',');

		for (k in values) {
			if (values.hasOwnProperty(k)) {
				let value = BX.util.trim(values[k]);
				let checked = value.indexOf('-') === -1;
				value = value.replace(/[-+]/g, '');
				values[k] = value;

				if (availableSortedItems[value]) {
					let message = availableSortedItems[value];
					let refresh = BX.util.in_array(value, refreshItems);
					result.push({
						value: value,
						message: message,
						checked: checked,
						refresh: refresh,
					});
				}
			}
		}

		for (value in availableSortedItems) {
			if (availableSortedItems.hasOwnProperty(value) && !BX.util.in_array(value, values)) {
				let message = availableSortedItems[value];
				let refresh = BX.util.in_array(value, refreshItems);
				result.push({
					value: value,
					message: message,
					checked: false,
					refresh: refresh,
				});
			}
		}

		return result;
	},

	buildNodes: function() {
		if (this.params.oCont.querySelectorAll('.asi-params__container').length) {
			return;
		}

		let baseNode = BX.create('DIV', {
			props: {className: 'asi-params__container', id: this.rootElementId}
		});

		let onChange1 = BX.proxy(
			function(eventObj, dragElement, event){
				this.saveData();
			}, this
		);

		let onChange2 = BX.proxy(
			function(eventObj, dragElement, event){
				this.saveData();
				BX.fireEvent(this.params.oInput, 'change');
			}, this
		);

		for (let k in this.items) {
			if (this.items.hasOwnProperty(k)) {		
				baseNode.appendChild(
					BX.create('DIV', {
						attrs: {'data-value': this.items[k].value},
						props: {
							className: 'asi-params__block ' + this.dragItemClassName,
						},
						children: [
							BX.create('div', {
								props: {
									className: 'asi-params__block-drag',
								},
								html: `
									<svg xmlns="http://www.w3.org/2000/svg" width="5" height="11" viewBox="0 0 5 11">
										<path id="Shape_1_copy_7" data-name="Shape 1 copy 7" fill="#333" fill-rule="evenodd" d="M815,765h2l-2.5,3-2.5-3h2v-5h-2l2.5-3,2.5,3h-2v5Z" transform="translate(-812 -757)"/>
									</svg>`,
							}),
							BX.create('input', {
								attrs: {
									type: 'checkbox',
									checked: this.items[k].checked,
								},
								events: {
									change: this.items[k].refresh ? onChange2 : onChange1,
								},
							}),
							BX.create('span', {
								text: this.items[k].message,
							}),
						],
					})
				);
			}
		}

		this.params.oCont.appendChild(baseNode);
	},

	initDragDrop: function() {
		if (BX.isNodeInDom(this.params.oCont)) {
			this.dragdrop = BX.DragDrop.create({
				dragItemClassName: this.dragItemClassName,
				dragItemControlClassName: this.dragItemClassName,
				sortable: {rootElem: BX(this.rootElementId)},
				dragEnd: BX.delegate(function(eventObj, dragElement, event){
					this.saveData();
				}, this)
			});
		}
		else {
			setTimeout(BX.delegate(this.initDragDrop, this), 50);
		}
	},

	saveData: function() {
		let items = this.params.oCont.querySelectorAll('.' + this.dragItemClassName),
			arr = [];

		for (let k in items) {
			if (items.hasOwnProperty(k)) {
				let value = items[k].getAttribute('data-value').toString();
				let checked = true;
				let checkbox = items[k].querySelector('input[type=checkbox]');
				if (checkbox) {
					checked = checkbox.checked;
				}
				arr.push((checked ? '' : '-') + value);
			}
		}

		this.params.oInput.value = arr.join(',');
	}
};
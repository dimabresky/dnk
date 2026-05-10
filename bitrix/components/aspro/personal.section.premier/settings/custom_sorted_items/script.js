function initCustomSortedItems(params) {
	BX.loadScript('/bitrix/js/main/core/core_dragdrop.js', function() {
		(function waiter() {
			if (!!BX.DragDrop) {
				window['dnd_parameter_' + params.propertyID] = new CustomSortedItems(params);
			}
			else {
				setTimeout(waiter, 50);
			}
		})();
	});
}

function CustomSortedItems(params) {
	let rand = BX.util.getRandomString(5);

	this.params = typeof params === 'object' && params ? params : {};
	
	BX.loadCSS(this.getPath() + '/style.css?' + rand);
	
	try {
		this.options = JSON.parse(params.data);
	}
	catch (e) {
		this.options = {};
	}
	this.options = typeof this.options === 'object' && this.options ? this.options : {};

	this.bCheckable = Boolean(this.options.checkable ? true : false);
	this.bSortable = Boolean(this.options.sortable ? true : false);
	this.rootElementId = 'csi-params__container--' + this.params.propertyID + '-' + rand;
	this.dragItemClassName = 'csi-params__block--' + this.params.propertyID + '-' + rand;
	this.header = typeof this.options.header === 'object' && this.options.header ? this.options.header : {};
	this.header.title = typeof this.header.title === 'string' ? this.header.title : '';
	this.header.code = typeof this.header.code === 'string' ? this.header.code : '';
	this.props = typeof this.options.props === 'object' && this.options.props ? this.options.props : [];

	// this setTimeout() fixes checkbox`s styles after refresh (change event)
	setTimeout(() => {
		this.buildNodes();
		if (this.bSortable) {
			this.initDragDrop();
		}
	}, 0);
}

CustomSortedItems.prototype = {
	baseNode: null,

	getPath: function() {
		let path = this.params.propertyParams.JS_FILE.split('/');
		path.pop();

		return path.join('/');
	},

	get openedItems() {
		if (
			typeof CustomSortedItems.openedItems !== 'object' ||
			!CustomSortedItems.openedItems
		) {
			CustomSortedItems.openedItems = {};
		}

		let openedItems = CustomSortedItems.openedItems[this.params.propertyID];
		openedItems = typeof openedItems === 'object' && openedItems ? openedItems : {};

		return openedItems;
	},
	
	set openedItems(value) {
		if (
			typeof CustomSortedItems.openedItems !== 'object' ||
			!CustomSortedItems.openedItems
		) {
			CustomSortedItems.openedItems = {};
		}

		value = typeof value === 'object' && value ? value : {};
		CustomSortedItems.openedItems[this.params.propertyID] = value;

		return value;
	},

	buildNodes: function() {
		if (this.params.oCont.querySelectorAll('.csi-params__container').length) {
			return;
		}

		this.baseNode = BX.create('DIV', {
			props: {
				className: 'csi-params__container', 
				id: this.rootElementId,
			}
		});
	
		addPageParams = this.baseNode.appendChild(BX.create('input', {
			props: {
				value: '+',
				type: 'button',
				className: 'addPageParams',
			},
			events: {
				click: BX.proxy(
					function() {
						this.addBlock();
	
						if (this.bSortable) {
							this.initDragDrop();
						}
					}, this
				),
			}
		}));
	
		this.params.oCont.appendChild(this.baseNode);
	
		if (
			this.params.oInput.value != '' &&
			this.params.oInput.value != '[]'
		) {
			try {
				this.inputData = JSON.parse(this.params.oInput.value);
			}
			catch(e) {
				this.inputData = [];
			}

			this.inputData.forEach(function(values) {
				this.addBlock(values);
			}, this);
		}
		else {
			this.addBlock();
		}
	},

	initDragDrop: function() {
		if (BX.isNodeInDom(this.params.oCont)) {
			this.dragdrop = BX.DragDrop.create({
				dragItemClassName: this.dragItemClassName,
				dragItemControlClassName: this.dragItemClassName,
				sortable: {rootElem: BX(this.rootElementId)},
				dragEnd: BX.delegate(function(eventObj, dragElement, event){
					this.save();
				}, this)
			});
		}
		else {
			setTimeout(BX.delegate(this.initDragDrop, this), 50);
		}
	},

	addBlock: function(values) {
		let html = '';

		if (typeof(values) === 'undefined') {
			values = {};
		}

		let props = typeof this.options.props === 'object' && this.options.props ? this.options.props : [];

		if (
			this.header &&
			this.header.title.length &&
			this.header.code.length
		) {
			html = html + '<div class="csi-params__block-header">';

				if (this.bSortable) {
					html = html + `
						<div class="csi-params__block-drag">
							<svg xmlns="http://www.w3.org/2000/svg" width="5" height="11" viewBox="0 0 5 11">
								<path id="Shape_1_copy_7" data-name="Shape 1 copy 7" fill="#333" fill-rule="evenodd" d="M815,765h2l-2.5,3-2.5-3h2v-5h-2l2.5-3,2.5,3h-2v5Z" transform="translate(-812 -757)"/>
							</svg>
						</div>`;
				}

				if (this.bCheckable) {
					let bActive = typeof values.active === 'undefined' ? true : Boolean(values.active);
					html = html + `
						<div class="csi-params__block-active">
							<input type="checkbox"` + (bActive ? ' checked' : '') + ` class="csi-params__option-value" />
						</div>`;
				}

				let headerValue = typeof(values[this.header.code]) === 'undefined' ? '' : values[this.header.code];

				html = html + `
					<div class="csi-params__option csi-params__option--string">
						<input 
							type="text" 
							class="csi-params__option-value" 
							title="` + this.escapeHtml(this.header.title) + `" 
							placeholder="` + this.escapeHtml(this.header.title) + `" 
							value="` + this.escapeHtml(headerValue) + `" 
							data-name="` + this.header.code + `" 
						/>
					</div>`;

				if (props.length) {
					html = html + `<input type="button" class="csi-params__block-opener" value="..." />`;
				}

			html = html + '</div>';

			if (props.length) {
				html = html + '<div class="csi-params__block-props">';

					props.forEach((prop) => {
						let code = prop.code;
						let type = prop.type;

						if (
							typeof code === 'string' &&
							code.length &&
							typeof type === 'string'
						) {
							let value = typeof(values[code]) === 'undefined' ? '' : values[code];
							
							if (value === '') {
								let rand = typeof prop.rand !== 'undefined' && prop.rand;

								if (rand) {
									value = this.generateRand(code);
								}
							}

							if (type === 'hidden') {
								html = html + `
									<div class="csi-params__option csi-params__option--hidden">
										<input 
											type="hidden" 
											class="csi-params__option-value" 
											value="` + this.escapeHtml(value) + `" 
											data-name="` + code + `" 
										/>
									</div>`;
							}
							else {
								let title = prop.title;
								if (title.length) {
									if (type === 'string') {
										html = html + `
											<div class="csi-params__option csi-params__option--string">
												<label>` + title + `</label>
												<input 
													type="text" 
													class="csi-params__option-value" 
													title="` + this.escapeHtml(title) + `" 
													placeholder="` + this.escapeHtml(title) + `" 
													value="` + this.escapeHtml(value) + `" 
													data-name="` + code + `" 
												/>
											</div>`;
									}
								}
							}
						}
					});

				html = html + '</div>';
			}

			let bOpened = false;
			let id = typeof(values['id']) === 'undefined' ? null : values['id'];
			if (id) {
				let openedItems = this.openedItems;
				bOpened = openedItems[id];
			}

			let block = BX.create('div', {
				props: {
					className: 'csi-params__block' + (this.bSortable ? ' csi-params__block--sortable ' + this.dragItemClassName : '') + (bOpened ? ' opened' : ''),
				},
				html: html
			});

			let addPageParams = this.params.oCont.querySelector('.addPageParams');
			if (addPageParams) {
				BX.insertBefore(block, addPageParams);
			}
			else {
				this.params.oCont.appendChild(block);
			}

			BX.bindDelegate(
				block,
				'click',
				{
					class: 'csi-params__block-opener',
				},
				BX.proxy(
					function(event) {
						event = event || window.event;

						let block = event.target.closest('.csi-params__block');
						if (block) {
							block.classList.toggle('opened');

							let id = this.getBlockId(block);
							if (id) {
								let openedItems = this.openedItems;
								openedItems[id] = block.classList.contains('opened');
								this.openedItems = openedItems;
							}
						}
					}, this
				)
			);
		
			BX.bindDelegate(
				block,
				'change',
				{
					class: 'csi-params__option-value',
				},
				BX.proxy(
					function(event) {
						this.save();
					}, this
				)
			);

			BX.bind(
				block,
				'mousedown',
				BX.proxy(
					function(event) {
						event = event || window.event;

						let target = event.target;
						block.draggable = !target.closest('input,textarea,select');
					}, this
				)
			);

			BX.bind(
				block,
				'dragstart',
				BX.proxy(
					function(event) {
						event = event || window.event;

						let target = event.target;
						if (!target.draggable) {
							event.preventDefault();
						}
					}, this
				)
			);
		}
	},

	getBlockId: function(block) {
		if (block) {
			let inputId = block.querySelector('input[data-name="id"]');
			if (inputId) {
				let id = inputId.value;
				if (id.length) {
					return id;
				}
			}
		}

		return null;
	},

	save: function() {
		let hidden = this.params.oCont.querySelector('input[type="hidden"]');
		if (
			hidden &&
			this.header &&
			this.header.code.length
		) {
			let values = [];
			let inputBlocks = this.params.oCont.getElementsByClassName('csi-params__block');
			if (inputBlocks.length) {
				Array.prototype.forEach.call(inputBlocks, (inputBlock) => {
					let value = {};
		
					let inputs = inputBlock.querySelectorAll('.csi-params__option .csi-params__option-value');
					if (inputs.length) {
						inputs.forEach((input) => {
							let code = input.getAttribute('data-name');
							value[code] = input.value;
						});
					}
		
					if (this.bCheckable) {
						value.active = 0;
		
						let active = inputBlock.querySelector('.csi-params__block-header .csi-params__block-active input[type="checkbox"]');
						if (active) {
							value.active = active.checked ? 1 : 0;
						}
					}
		
					if (value[this.header.code] !== '') {
						values.push(value);
					}
				});
			}	
	
			let oldValue = hidden.value;
			let newValue = JSON.stringify(values);
			hidden.value = newValue;

			if (
				oldValue !== newValue &&
				this.params.propertyParams.REFRESH === 'Y'
			) {
				if (this.refreshTimer) {
					clearInterval(this.refreshTimer);
					this.refreshTimer = false;
				}
				
				this.refreshTimer = setInterval(
					BX.proxy(
						function() {
							let bRefresh = true;

							let activeElement = document.activeElement;
							if (activeElement) {
								let baseNode = activeElement.closest('.csi-params__container');
								if (
									baseNode &&
									baseNode === this.baseNode
								) {
									bRefresh = false;
								}
							}

							if (bRefresh) {
								clearInterval(this.refreshTimer);
								this.refreshTimer = false;

								BX.fireEvent(this.params.oInput, 'change');
							}
						}, this
					), 100
				);
			}
		}
	},

	generateRand:function (code) {
		let value = '';
		let values = [];

		if (this.inputData) {
			values = this.inputData.map(values => values[code]);
		}

		do {			
			value = Math.floor(Math.random() * Date.now()).toString(36).slice(0, 4);
		}
		while (values.indexOf(value) !== -1);

		return value;
	},

	escapeHtml:function (text) {
		return BX.Text.encode(text);
		
		// return text
		// 	.replace(/&/g, "&amp;")
		// 	.replace(/</g, "&lt;")
		// 	.replace(/>/g, "&gt;")
		// 	.replace(/"/g, "&quot;")
		// 	.replace(/'/g, "&#039;");
	}
};

if (typeof window.JOrderCancel === 'undefined') {
	window.JOrderCancel = function (node, config) {
		node = (typeof node === 'object' && node && node instanceof Node) ? node : ((typeof node === 'string' && node.length) ? document.querySelector(node) : null);
		config = (typeof config === 'object' && config) ? config : {};

		this.node = node;

		var _private = {
			inited: false,
		};
  
		let _config = JSON.stringify(config);

		Object.defineProperties(this, {
			inited: {
				get: function () {
					return _private.inited;
				},
				set: function (value) {
					if (value) {
						_private.inited = true;
					}
				},
			},

			config: {
				get: function() {
					return JSON.parse(_config);
				},
			},
		});

		this.init();
	};

	window.JOrderCancel.prototype = {
		node: null,
		nodes: {
			form: null,
			submit: null,
		},

		init: function () {
			if (!this.inited) {
				this.inited = true;

				this.node.ordercancel = this;
				
				if (this.node) {
					this.nodes.form = this.node.querySelector('form');
					if (this.nodes.form) {
						this.nodes.submit = this.nodes.form.querySelector('.btn[type="submit"]');
					}

					this.bindEvents();

					// disable submit button
					if (this.config.reasonRequired) {
						BX.fireEvent(this.nodes.form.querySelector('textarea'), 'change');
					}
				}
			}
		},
	
		bindEvents: function() {
			if (this.nodes.form) {
				if (typeof this.handlers.onFormSubmit === 'function') {
					BX.bind(
						this.nodes.form,
						'submit',
						BX.proxy(
							this.handlers.onFormSubmit,
							this
						)
					);
				}

				if (typeof this.handlers.onControlChange === 'function') {
					let controls = [
						{
							tag: 'input',
							props: {
								type: 'checkbox',
							},
						},
						{
							tag: 'textarea',
						},
					];
					let events = ['change', 'keyup', 'paste'];

					for (let i = 0, icnt = controls.length; i < icnt; ++i) {
						for (let j = 0, jcnt = events.length; j < jcnt; ++j) {
							BX.bindDelegate(
								this.nodes.form,
								events[j],
								controls[i],
								BX.proxy(
									this.handlers.onControlChange,
									this
								)
							);
						}
					}
				}
			}
		},

		unbindEvents: function() {
			if (this.nodes.form) {
				if (typeof this.handlers.onFormSubmit === 'function') {
					BX.unbind(
						this.nodes.form,
						'submit',
						BX.proxy(
							this.handlers.onFormSubmit,
							this
						)
					);
				}
			}
		},
		
		handlers: {
			onControlChange: function(event) {
				event = event || window.event;

				let reasonText = '';

				let reasons = this.nodes.form.querySelectorAll('[name="REASONS[]"]:checked');
				if (reasons.length) {
					reasons.forEach((reason) => {
						reasonText = reasonText + (reasonText.length ? "\n" : '') + reason.value;
					});
				}

				let reasonAnother = this.nodes.form.querySelector('[name="ANOTHER_REASON"]');
				if (reasonAnother) {
					reasonText = reasonText + (reasonText.length ? "\n" : '') + reasonAnother.value;
				}

				let reason = this.nodes.form.querySelector('input[name="REASON_CANCELED"]');
				if (reason) {
					reason.value = reasonText;
				}

				// do not cancel without reason
				if (this.config.reasonRequired) {
					if (this.nodes.submit) {
						this.nodes.submit.disabled = reasonText.length ? false : true;
					}
				}
			},

			onFormSubmit: function(event) {
				event = event || window.event;

				if (this.nodes.form) {
					let reason = this.nodes.form.querySelector('input[name="REASON_CANCELED"]');
					if (reason) {
						if (reason.value.length) {
							let form = this.nodes.form.closest('.form');
							if (form) {
								form.classList.add('sending');
							}

							return true;
						}
					}
				}

				// do not cancel without reason
				if (this.config.reasonRequired) {
					event.preventDefault();
					this.nodes.submit.disabled = true;
				}
			},
		},
	};
}
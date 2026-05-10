BX.namespace('BX.Sale');

BX.Sale.OrderPaymentChange = (function() {
	var classDescription = function(params) {
		this.ajaxUrl = params.url;
		this.wrapperId = params.wrapperId || '';
		this.wrapper = document.getElementById('bx-sopc' + this.wrapperId);
		this.templateName = params.templateName || '';
		this.templateFolder = params.templateFolder;
		this.orderId = params.orderId || 0;
		this.accountNumber = params.accountNumber || {};
		this.paymentNumber = params.paymentNumber || {};
		this.inner = params.inner || '';
		this.onlyInnerFull = params.onlyInnerFull || '';
		this.pathToPayment = params.pathToPayment || '';
		this.returnUrl = params.returnUrl || '';
		this.refreshPrices = params.refreshPrices || 'N';
		this.parentComponent = params.parentComponent || {};

		if (this.wrapper) {
			this.form = this.wrapper.querySelector('form');
		}

		BX.ready(BX.proxy(this.init, this));
	};

	classDescription.prototype.init = function() {
		if (this.form) {
			BX.bind(
				this.form,
				'submit',
				BX.delegate(function(event) {
					event = event || window.event;
					event.preventDefault();
	
					if (this.form.querySelector('.form-group.form-group--paysystems')) {
						let popup = this.form.closest('.popup');
						let closeChangePaymentPopup = function() {
							if (popup) {
								let closer = popup.querySelector('.jqmClose');
								if (closer) {
									BX.fireEvent(closer, 'click');
								}
								else {
									let overlay = popup.parentElement.querySelector('.jqmOverlay');
									if (overlay) {
										BX.fireEvent(overlay, 'click');
									}
									else {
										popup.innerHTML = '';
									}
								}
							}
						}
		
						let paySystemId = this.form.querySelector('.form-radiobox__input:checked').value;
		
						if (paySystemId) {
							this.form.classList.add('sending');
			
							BX.ajax({
								method: 'POST',
								dataType: 'html',
								url: this.ajaxUrl,
								data: {
									sessid: BX.bitrix_sessid(),
									paySystemId: paySystemId,
									templateName: this.templateName,
									accountNumber: this.accountNumber,
									paymentNumber: this.paymentNumber,
									inner: this.inner,
									onlyInnerFull: this.onlyInnerFull,
									refreshPrices: this.refreshPrices,
									pathToPayment: this.pathToPayment,
									returnUrl: this.returnUrl,
									
									ajaxDisplay: 'Y',
									PARENT_COMPONENT: this.parentComponent.name,
									PARENT_COMPONENT_TEMPLATE: this.parentComponent.template,
									PARENT_COMPONENT_PAGE: this.parentComponent.page,
								},
								onsuccess: BX.proxy(function(response) {
									let obData = BX.processHTML(response);
									let html = obData.HTML.trim();
		
									if (html.length) {
										let popupBody = this.wrapper.querySelector('.form-body');
										if (popupBody) {
											let popupFooter = popup.querySelector('.form-footer');
											if (popupFooter) {
												popupFooter.remove();
											}
		
											popupBody.innerHTML = html;
											BX.ajax.processScripts(obData.SCRIPT);
										}
									}
									else {
										closeChangePaymentPopup();
		
										BX.onCustomEvent(
											'onOrderPaymentChange',
											[{
												orderId: this.orderId,
												accountNumber: this.accountNumber,
												paymentNumber: this.paymentNumber,
											}]
										);
									}
			
									this.form.classList.remove('sending');
								}, this),
								onfailure: BX.proxy(function() {
									this.form.classList.remove('sending');
			
									return this;
								}, this)
							}, this);
						}
						else {
							closeChangePaymentPopup();
						}
					}
				}, this)
			);
		}

		return this;
	};

	return classDescription;
})();

BX.Sale.OrderInnerPayment = (function() {
	var paymentDescription = function(params) {		
		this.messages = params.alertMessages || {};
		this.ajaxUrl = params.url;
		this.wrapperId = params.wrapperId || '';
		this.wrapper = document.getElementById('bx-sopc-inner' + this.wrapperId);
		this.templateName = params.templateName || '';
		this.templateFolder = params.templateFolder;
		this.accountNumber = params.accountNumber || {};
		this.paymentNumber = params.paymentNumber || {};
		this.valueLimit =  parseFloat(params.valueLimit) || 0;
		this.inner = params.inner || '';
		this.onlyInnerFull = params.onlyInnerFull || '';
		this.returnUrl = params.returnUrl || '';
		this.parentComponent = params.parentComponent || {};

		if (this.wrapper) {
			this.form = this.wrapper.closest('form');

			if (this.form) {
				this.input = this.form.querySelector('input[name="payInner"]');
				this.button = this.form.querySelector('button[type="submit"]');
			}
		}

		BX.ready(BX.proxy(this.init, this));
	};

	paymentDescription.prototype.init = function() {
		if (this.input) {
			BX.bind(
				this.input,
				'input',
				BX.delegate(function() {
					this.input.value = this.input.value.replace(/[^\d,.]*/g, '')
						.replace(/,/g, '.')
						.replace(/([,.])[,.]+/g, '$1')
						.replace(/^[^\d]*(\d+([.,]\d{0,2})?).*$/g, '$1');
	
					var value = this.input.value.length ? parseFloat(this.input.value) : 0;
	
					if (value > this.valueLimit) {
						value = this.valueLimit;
					}

					if (value <= 0) {
						value = 0;
					}

					this.input.value = value;

					if (this.button) {
						this.button.disabled = value <= 0;
					}
				}, this)
			);
		}

		if (this.form) {
			BX.bind(
				this.form,
				'submit',
				BX.delegate(function(event) {
					event = event || window.event;
					event.preventDefault();

					if (!this.form.querySelector('.form-group.form-group--paysystems')) {
						let popup = this.form.closest('.popup');
						let closeChangePaymentPopup = function() {
							if (popup) {
								let closer = popup.querySelector('.jqmClose');
								if (closer) {
									BX.fireEvent(closer, 'click');
								}
								else {
									let overlay = popup.parentElement.querySelector('.jqmOverlay');
									if (overlay) {
										BX.fireEvent(overlay, 'click');
									}
									else {
										popup.innerHTML = '';
									}
								}
							}
						}

						if (this.input) {
							if (parseFloat(this.input.value) <= 0 || this.input.value == '') {
								window.alert(BX.util.htmlspecialchars(this.messages.wrongInput));
			
								return false;
							}
						}

						this.form.classList.add('sending');

						BX.ajax({
							method: 'POST',
							dataType: 'html',
							url: this.ajaxUrl,
							data: {
								sessid: BX.bitrix_sessid(),
								templateName: this.templateName,
								accountNumber: this.accountNumber,
								paymentNumber: this.paymentNumber,
								inner: 'Y',
								onlyInnerFull: this.onlyInnerFull,
								paymentSum: this.input ? this.input.value : 0,
								returnUrl: this.returnUrl,

								ajaxDisplay: 'Y',
								PARENT_COMPONENT: this.parentComponent.name,
								PARENT_COMPONENT_TEMPLATE: this.parentComponent.template,
								PARENT_COMPONENT_PAGE: this.parentComponent.page,
							},
							onsuccess: BX.proxy(function(response) {
								let obData = BX.processHTML(response);
								let html = obData.HTML.trim();
	
								if (html.length) {
									let popupBody = this.wrapper.closest('.form-body');
									if (popupBody) {	
										popupBody.innerHTML = html;
										BX.ajax.processScripts(obData.SCRIPT);
									}
								}
								else {
									closeChangePaymentPopup();
	
									BX.onCustomEvent(
										'onOrderPaymentChange',
										[{
											accountNumber: this.accountNumber,
											paymentNumber: this.paymentNumber,
										}]
									);
								}

								this.form.classList.remove('sending');
							},this),
							onfailure: BX.proxy(function() {
								this.form.classList.remove('sending');
		
								return this;
							}, this)
						}, this
						);
					}
				}, this)
			);
		}
	};

	return paymentDescription;
})();
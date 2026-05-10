
BX.saleAccountPay = (function() {
	var classDescription = function(params) {
		this.messages = params.alertMessages || {};
		this.ajaxUrl = params.url;
		this.signedParams = params.signedParams || {};
		this.wrapperId = params.wrapperId || '';
		this.templateFolder = params.templateFolder;
		this.templateName = params.templateName || '';
		this.wrapper = document.getElementById('bx-sap' + this.wrapperId);

		if (this.wrapper) {
			this.form = this.wrapper.querySelector('form');

			if (this.form) {
				this.inputElement = this.form.querySelector('.sale-acountpay-input');
				this.changeInputContainer = this.form.querySelector('.form-group--input .fixedpay .line-block');
				this.paySystemsContainer = this.form.querySelector('.form-group--paysystems .line-block');
				this.buttons = this.form.querySelectorAll('button[type="submit"]');
			}
		}

		BX.ready(BX.proxy(this.init, this));
	}
	
	classDescription.prototype.init = function() {
		if (this.inputElement) {
			BX.bind(
				this.inputElement,
				'input',
				BX.delegate(function () {
					this.inputElement.value = this.inputElement.value.replace(/[^\d,.]*/g, '')
						.replace(/\,/g, '.')
						.replace(/([,.])[,.]+/g, '$1')
						.replace(/^[^\d]*(\d+([.,]\d{0,5})?).*$/g, '$1');
	
					var value = this.inputElement.value.length ? parseFloat(this.inputElement.value) : 0;
	
					if (value <= 0) {
						value = 0;
					}
	
					this.inputElement.value = value;
	
					if (this.buttons) {
						this.buttons.forEach(function(button) {
							button.disabled = value <= 0;
						});
					}
				}, this)
			);
		}
		
		if (this.changeInputContainer) {
			BX.bindDelegate(
				this.changeInputContainer,
				'click',
				{
					'className': 'chip'
				},
				BX.proxy(function(event) {
					this.inputElement.value = parseInt(event.target.innerText);
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
					
					if (this.inputElement) {
						if (parseFloat(this.inputElement.value) <= 0 || this.inputElement.value == '') {
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
							templateName: '', // include confirm_template.php from .default template, not from this.templateName,
							signedParamsString: this.signedParams,
							buyMoney: this.inputElement ? this.inputElement.value : 0,
							paySystemId: this.form.querySelector('.form-radiobox__input:checked').value,
						},
						onsuccess: BX.proxy(function(result) {
							this.wrapper.innerHTML = '<div class="personal__top-form--replenish__payment">' + result + '</div>';
							this.form.classList.remove('sending');
	
							this.destroy();
						}, this),
						onfailure: BX.proxy(function() {
							this.form.classList.remove('sending');
							
							return this;
						}, this)
					}, this);
				}, this)
			);
		}

		return this;
	}

	classDescription.prototype.destroy = function() {
		this.messages = null;
		this.signedParams = null;
		this.form = null;
		this.inputElement = null;
		this.changeInputContainer = null;
		this.paySystemsContainer = null;
	}

	return classDescription;
})();
BX.namespace('BX.Sale.PersonalProfileComponent');

(function() {
	BX.Sale.PersonalProfileComponent.PersonalProfileDetail = function(formSelector, params) {
		this.init(formSelector, params);
	}

	BX.Sale.PersonalProfileComponent.PersonalProfileDetail.prototype = {
		form: null,
		id: 0,
		personTypeId: 0,
		ajaxUrl: '',
		listUrl: '',
		deleteUrl: '',

		init: function (formSelector, params) {
			this.form = document.querySelector(formSelector);

			if (BX.type.isPlainObject(params)) {
				this.id = params.id;
				this.personTypeId = params.personTypeId;
				this.ajaxUrl = params.ajaxUrl;
				this.listUrl = params.listUrl;
				this.deleteUrl = params.deleteUrl;
			}

			if (arAsproOptions['THEME']['DATE_MASK'].length) {
				$(this.form).find('input.date').inputmask('datetime', {
					'inputFormat':  arAsproOptions['THEME']['DATE_MASK'],
					'placeholder': arAsproOptions['THEME']['DATE_PLACEHOLDER'],
					'showMaskOnHover': false,
				});
			}

			if (arAsproOptions['THEME']['DATETIME_MASK'].length) {
				$(this.form).find('input.datetime').inputmask('datetime', {
					'inputFormat':  arAsproOptions['THEME']['DATETIME_MASK'],
					'placeholder': arAsproOptions['THEME']['DATETIME_PLACEHOLDER'],
					'showMaskOnHover': false,
				});
			}

			$(this.form).find('input[type=file]').uniform({
				fileButtonHtml: BX.message('JS_FILE_BUTTON_NAME'),
				fileDefaultHtml: BX.message('SPPD_TPL_FILE_DEFAULT'),
			});

			let $items = $(this.form).find('input[type=file]');
			if ($items.length) {
				for (let i = 0; i < $items.length; ++i) {
					let name = $items.eq(i).data('file-name');
					let href = $items.eq(i).data('file-href');
					if (name) {
						let uploader = $items.eq(i)[0].closest('.uploader');
						if (uploader) {
							BX.addClass(uploader, 'files_add');

							let filename = uploader.querySelector('.filename');
							if (filename) {
								BX.addClass(filename, 'with_href');
								filename.innerHTML = '<a href="' + href + '" download="' + name + '">' + name + '</a>';
							}

							let reset = this.createUploaderResetButton(uploader);
							uploader.insertBefore(reset, $items.eq(i)[0].nextSibling);
						}
					}
				}
			}

			BX.bindDelegate(
				this.form, 
				'click', 
				{
					className: 'js-profile-delete',
				},
				BX.proxy(
					function(event) {
						event = event || wondow.event;
						event.preventDefault();

						this.showDeletePopup();
					}, this
				)
			);

			BX.bindDelegate(
				this.form, 
				'click', 
				{
					className: 'sale-personal-profile-detail-form-date',
				},
				BX.proxy(
					function(e) {
						let input = e.target;
						if (input.classList.contains('bx-calendar')) {
							input = input.parentNode;
						}

						if (
							!BX.type.isDomNode(input) ||
							input.tagName !== 'INPUT'
						) {
							return;
						}

						BX.calendar({
							node: input,
							field: input,
							form: '',
							bTime: BX.hasClass(input, 'datetime'),
							bHideTime: true,
						});
					}, this
				)
			);

			BX.bindDelegate(
				this.form,
				'click',
				{
					className: 'input-add-multiple',
				},
				BX.proxy(
					function(event) {
						let trigger = event.target.closest('.input-add-multiple');
						switch (trigger.getAttribute('data-add-type')) {
							case "LOCATION": this.createLocationInput(event);
								break;
							case "TEXT": this.createTextInput(event);
								break;
							case "FILE": this.createFileInput(event);
								break;
							case "DATE": this.createDateInput(event);
							break;
						}
					}, this
				)
			);

			BX.bindDelegate(
				this.form, 
				'change', 
				{
					tag: 'input',
					attrs: {
						type: 'file',
					},
				},
				BX.proxy(
					function(e) {
						let input = e.target;

						if (
							!BX.type.isDomNode(input) ||
							input.tagName !== 'INPUT'
						) {
							return;
						}

						if (input.value) {
							input.closest('.uploader').classList.add('files_add');
						}
						else {
							input.closest('.uploader').classList.remove('files_add');
						}
					}, this
				)
			);

			BX.bind(
				this.form, 
				'submit', 
				BX.proxy(
					function(event) {
						event.preventDefault();

						if ($(this.form).valid()) {
							this.form.closest('.form').classList.add('sending');

							let fd = new FormData(this.form);
							fd.set('AJAX_POST', 'Y');
							fd.set('apply', 'apply');
							fd.set('sessid', BX.bitrix_sessid());
	
							let action = this.form.getAttribute('action');
							action = action + (action.indexOf('?') == -1 ? '?' : '&') + 'AJAX_POST=Y';
		
							BX.ajax({
								url: action,
								data: fd,
								method: 'POST',
								dataType: 'html',
								async: true,
								preparePost: false,
								processData: false,
								scriptsRunFirst: false,
								emulateOnload: false,
								start: true,
								cache: false,
								onsuccess: BX.proxy(function(response) {
									response = response.trim();
									
									// get new page h1
									let newTitle = $(response).find('#pagetitle');
									let newTitleHtml = newTitle.length ? newTitle[0].innerHTML : '';
									let newTitleText = newTitle.length ? newTitle[0].innerText : '';

									// get new page breadcrumbs
									let newBreadcrumbs = $(response).find('#navigation');
									let newBreadcrumbsHtml = newBreadcrumbs.length ? newBreadcrumbs[0].innerHTML : '';

									response = $(response).find('form.sale-profile-detail-form').closest('.personal__block--profile')[0].outerHTML;

									let bList = Boolean(this.form.closest('.profiles__items'));
									let id = this.id;
									let bError = response.indexOf('alert-danger') != -1;

									if (bList && id > 0 && !bError) {
										this.refreshListItem();
									}
									else {
										this.form.closest('.form').classList.remove('sending');

										let obData = BX.processHTML(response);
										let html = response; // full response, not use obData.HTML;
										let block = this.form.closest('.personal__block--profile');
										if (block) {
											block.outerHTML = html;

											if (!bList) {
												// replace page title, h1
												if (newTitleHtml.length) {
													let h1 = document.getElementById('pagetitle');
													if (h1) {
														h1.innerHTML = newTitleHtml;
														document.title = newTitleText;
													}
												}
	
												// replace page breadcrumbs
												if (newBreadcrumbsHtml.length) {
													let breadcrumbs = document.getElementById('navigation');
													if (breadcrumbs) {
														breadcrumbs.innerHTML = newBreadcrumbsHtml;
													}
												}
											}
										}

										// get real scripts, before use BX.ajax.processScripts(obData.SCRIPT);
										scripts = $(response).find('script');
										scripts = Array.prototype.slice.call(scripts).map(function(i) {
											return i.innerHTML;
										});
										
										obData.SCRIPT = obData.SCRIPT.filter(function(i) {
											return scripts.indexOf(i.JS) !== -1;
										});

										BX.ajax.processScripts(obData.SCRIPT);
									}
								}, this),
								onfailure: BX.proxy(function() {
									this.form.closest('.form').classList.remove('sending');
								}, this)
							});
						}

					}, this
				)
			);

			BX.bindDelegate(
				this.form, 
				'click',
				{
					tag: 'button',
					attrs: {
						name: 'cancel',
					},
				},
				BX.proxy(
					function(event) {
						event = event || window.event;

						let bList = Boolean(this.form.closest('.profiles__items'));
						let id = this.id;

						if (bList && id > 0) {
							this.refreshListItem();
						}
						else {
							event.preventDefault();
						}
					},
					this
				)
			);

			$(this.form).validate({
				messages:{
					licenses_popup: {
						required : BX.message('JS_REQUIRED_LICENSES')
					}
				}
			});
		},

		showDeletePopup: function() {
			let showDeleteButton = this.form.querySelector('.js-profile-delete');
			if (showDeleteButton) {
				showDeleteButton.classList.add('loadings');
			}

			let trigger = BX.create({
				tag: 'div',
				attrs: {
					'data-event': 'jqm',
					'data-name': 'message',
					'data-param-form_id': 'message',
					'data-param-message_title': encodeURIComponent(BX.message('SPPD_TPL_DELETE_CONFIRM_TITLE').replace('#ID#', this.id)),
					'data-param-message_button_title': encodeURIComponent(BX.message('SPPD_TPL_DELETE')),
					'data-param-message_button_class': encodeURIComponent('btn btn-default btn-lg btn-wide'),
					'data-param-message_content': encodeURIComponent(BX.message('SPPD_TPL_DELETE_CONFIRM_DESC')),
				},
			});
			
			BX.append(trigger, document.body);
			
			$(trigger).jqmEx(
				BX.proxy(
					function(name, hash, _this) {
						if (showDeleteButton) {
							showDeleteButton.classList.remove('loadings');
						}

						let popup = hash.w[0];
						if (popup) {
							popup.classList.add('popup--profile-delete');
						}
						
						let popupButtonOk = popup.querySelector('.form-footer input[type="submit"]');
						if (popupButtonOk) {
							let closeDeletePopup = function() {
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

							BX.bind(
								popupButtonOk,
								'click',
								BX.proxy(
									function() {
										popupButtonOk.classList.add('loadings');

										let bList = Boolean(this.form.closest('.profiles__items'));
										let id = this.id;

										if (id > 0) {
											BX.ajax({
												url: this.deleteUrl,
												method: 'POST',
												dataType: 'html',
												async: true,
												preparePost: false,
												processData: false,
												scriptsRunFirst: false,
												emulateOnload: false,
												start: true,
												cache: false,
												onsuccess: BX.proxy(
													function(response) {
														response = response.trim();
														let $block = $(response).find('.profiles__item[data-id="' + id + '"]');
														let bError = Boolean($block.length);
	
														if (bError) {
															location.href = this.deleteUrl;
														}
														else {
															let successUrl = this.listUrl + (this.listUrl.indexOf('?') == -1 ? '?' : '&') + 'success_del_id=' + this.id;
															if (successUrl.indexOf('#pt') == -1) {
																successUrl = successUrl + '#pt' + this.personTypeId;
															}

															if (bList) {
																let block = this.form.closest('.personal__block--profile');
																if (block) {
																	block.remove();
																	location.href = successUrl;
																}
																else {
																	location.href = successUrl;
																}
															}
															else {
																location.href = successUrl;
															}
														}
													}, this
												),
												onfailure: BX.proxy(
													function() {
														popupButtonOk.classList.remove('loadings');
													}, this
												)
											});
										}
										else {
											closeDeletePopup();
										}
									}, this
								)
							);

							let buttonsBlock = popupButtonOk.closest('div');
							buttonsBlock.classList.add('line-block', 'line-block--20', 'line-block--20-vertical');

							let lineBlockItem1 = BX.create({
								tag: 'div',
								attrs: {
									className: 'line-block__item flex-1',
								},
								children: [
									popupButtonOk,
								],
							});

							let lineBlockItem2 = BX.create({
								tag: 'div',
								attrs: {
									className: 'line-block__item flex-1',
								},
								children: [
									BX.create({
										tag: 'span',
										attrs: {
											className: 'btn btn-transparent btn-lg btn-wide',
										},
										text: BX.message('SPPD_TPL_NOT_DELETE'),
										events: {
											'click': BX.proxy(
												function() {
													closeDeletePopup();
												}, this
											), 
										},
									}),
								],
							});

							buttonsBlock.append(lineBlockItem1);
							buttonsBlock.append(lineBlockItem2);
						}
					}, this
				),
				BX.proxy(
					function(name, hash, _this) {
						if (showDeleteButton) {
							showDeleteButton.classList.remove('loadings');
						}
					}, this
				)
			);
			
			// do not click with mobile template
			if (!arAsproOptions.SITE_TEMPLATE_PATH_MOBILE) {
				BX.fireEvent(trigger, 'click');
			}
			
			trigger.remove();
		},

		refreshListItem: function() {
			let bList = Boolean(this.form.closest('.profiles__items'));
			let id = this.id;

			if (bList && id > 0) {
				this.form.closest('.form').classList.add('sending');

				BX.ajax({
					url: location.href,
					method: 'GET',
					dataType: 'html',
					async: true,
					processData: false,
					scriptsRunFirst: false,
					emulateOnload: false,
					start: true,
					cache: false,
					onsuccess: BX.proxy(function(response) {
						this.form.closest('.form').classList.remove('sending');
						
						response = response.trim();
						let $block = $(response).find('.profiles__item[data-id="' + id + '"]');
						let block = this.form.closest('.personal__block--profile');
						if (
							$block.length &&
							block
						) {
							block.outerHTML = $block[0].outerHTML;
						}
					}, this),
					onfailure: BX.proxy(function() {
						this.form.closest('.form').classList.remove('sending');
					}, this),
				});
			}
		},

		createUploaderResetButton: function(uploader) {
            reset = BX.create({
                tag: 'span',
                props: {
                    className: 'resetfile',
                },
                attrs: {
                    title: BX.message('SPPD_TPL_UPLOAD_CLEAR'),
                },
                events: {
                    click: BX.proxy(
                        function() {
                            BX.removeClass(this, 'error files_add');

                            let error = this.querySelector('.error');
                            if (error) {
                                error.remove();
                            }

                            let reset = this.querySelector('.resetfile');
                            if (reset) {
                                reset.remove();
                            }

                            let input = this.querySelector('input[type="file"]');
                            if (input) {
                                input.value = '';
                                $.uniform.update($(input));

								let fileId = $(input).data('file-id');

								let filename = this.querySelector('.filename');
								if (filename) {
									BX.removeClass(filename, 'with_href');
								}

								if (fileId) {
									let block = input.closest('.input');
									if (block) {
										let name = input.getAttribute('name');
										if (name) {
											let delName = name.replace('[]', '') + '_del';

											let del = block.querySelector('input[type="hidden"][name="' + delName + '"]');
											if (!del) {
												del = BX.create({
													tag: 'input',
													attrs: {
														type: 'hidden',
														name: delName,
														value: '',
													}
												});
		
												block.insertBefore(del, block.firstChild);
											}

											if (del) {
												del.value = (del.value.length ? del.value + ';' : '') + fileId;
											}
										}
									}
								}
                            }
                        },
                        uploader
                    ),
                },
                html: '<svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 1.00161L1 9M9 9L1 1" stroke="#999999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            });

            return reset;
        },

		createDateInput: function(event) {
			if (!BX.type.isDomNode(event.target)) {
				return;
			}

			let trigger = event.target.closest('.input-add-multiple');
			let newInput = BX.create('input', {attrs: {
				className: 'form-control date',
				type: 'text',
				maxlength: '50',
				name: trigger.getAttribute('data-add-name'),
			}});

			trigger.parentNode.insertBefore(newInput, trigger);
		},

		createTextInput: function(event) {
			if (!BX.type.isDomNode(event.target)) {
				return;
			}

			let trigger = event.target.closest('.input-add-multiple');
			let newInput = BX.create('input', {attrs: {
				className: 'form-control',
				type: 'text',
				name: trigger.getAttribute('data-add-name'),
			}});

			trigger.parentNode.insertBefore(newInput, trigger);
		},

		createFileInput: function(event) {
			if (!BX.type.isDomNode(event.target)) {
				return;
			}

			let trigger = event.target.closest('.input-add-multiple');
			let newInput = BX.create('input', {attrs: {
				className: '',
				type: 'file',
				name: trigger.getAttribute('data-add-name'),
			}});

			trigger.parentNode.insertBefore(newInput, trigger);
			$(newInput).uniform({
				fileButtonHtml: BX.message('JS_FILE_BUTTON_NAME'),
				fileDefaultHtml: BX.message('SPPD_TPL_FILE_DEFAULT'),
			});
		},

		createLocationInput: function(event) {
			let trigger = event.target.closest('.input-add-multiple');
			let newKey = parseInt(trigger.getAttribute('data-add-last-key')) + 1;

			BX.ajax(
				{
					method: 'POST',
					dataType: 'html',
					url: this.ajaxUrl,
					data: {
						sessid: BX.bitrix_sessid(),
						params: {
							LOCATION_NAME: trigger.getAttribute('data-add-name'),
							LOCATION_TEMPLATE: trigger.getAttribute('data-add-template'),
							LOCATION_KEY: newKey,
							ACTION: 'getLocationHtml'
						},
						signedParamsString: this.signedParams
					},
					onsuccess: BX.proxy(
						function(result) {
							var wrapper = BX.create("div");
							wrapper.innerHTML = result;
							trigger.parentNode.insertBefore(wrapper, trigger);
							trigger.setAttribute('data-add-last-key', newKey);
						}, this
					),
					onfailure: BX.proxy(
						function() {
							return this;
						}, this
					)
				}, this
			);
		},
	}
})();
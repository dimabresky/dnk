$(document).on('click', '.js-profile-change', function(e) {
	e.preventDefault();

	let href = $(this).attr('href');
	let $block = $(this).closest('.profiles__item');

	if (!$block.length || !href.length) {
		return;
	}

	BX.ajax({
		url: href,
		data: {
			AJAX_POST: 'Y',
		},
		method: 'POST',
		dataType: 'html',
		async: true,
		processData: false,
		scriptsRunFirst: false,
		emulateOnload: false,
		start: true,
		cache: false,
		onsuccess: function(response) {
			let obData = BX.processHTML(response);
			let html = obData.HTML;
			$block[0].outerHTML = html;
			BX.ajax.processScripts(obData.SCRIPT);
		},
		onfailure: function() {

		},
	});
});

(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var button = event.target;
		if (!button || !button.matches('.button-link-delete')) {
			return;
		}
		if (!window.confirm('Delete this item?')) {
			event.preventDefault();
		}
	});

	document.addEventListener('click', function (event) {
		var selectButton = event.target.closest('[data-pasat-logo-select]');
		var removeButton = event.target.closest('[data-pasat-logo-remove]');
		var frame;
		var input = document.querySelector('[data-pasat-logo-id]');
		var preview = document.querySelector('[data-pasat-logo-preview]');

		if (!selectButton && !removeButton) {
			return;
		}

		event.preventDefault();

		if (removeButton) {
			if (input) {
				input.value = '0';
			}
			if (preview) {
				preview.textContent = '';
			}
			return;
		}

		if (!window.wp || !window.wp.media || !input || !preview) {
			return;
		}

		frame = window.wp.media({
			title: window.PASAT_ADMIN && window.PASAT_ADMIN.chooseLogo ? window.PASAT_ADMIN.chooseLogo : 'Choose Poster Logo',
			button: {
				text: window.PASAT_ADMIN && window.PASAT_ADMIN.useLogo ? window.PASAT_ADMIN.useLogo : 'Use this logo'
			},
			library: {
				type: 'image'
			},
			multiple: false
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var image = document.createElement('img');
			input.value = attachment.id || '0';
			preview.textContent = '';
			image.className = 'pasat-logo-preview__image';
			image.alt = attachment.alt || attachment.title || '';
			image.src = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			preview.appendChild(image);
		});

		frame.open();
	});
}());

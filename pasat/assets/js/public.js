(function () {
	'use strict';

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.matches('[data-pasat-signup-form]') || !window.fetch || !window.PASAT_PUBLIC) {
			return;
		}

		event.preventDefault();
		var data = new FormData(form);
		var payload = {};
		data.forEach(function (value, key) {
			payload[key] = value;
		});

		fetch(window.PASAT_PUBLIC.restUrl + '/signups', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.PASAT_PUBLIC.nonce
			},
			body: JSON.stringify(payload)
		}).then(function (response) {
			return response.json().then(function (body) {
				return { ok: response.ok, body: body };
			});
		}).then(function (result) {
			var notice = form.parentNode.querySelector('.pasat-js-notice');
			if (!notice) {
				notice = document.createElement('div');
				notice.className = 'pasat-js-notice pasat-notice';
				form.parentNode.insertBefore(notice, form);
			}
			notice.classList.toggle('pasat-notice--success', result.ok);
			notice.classList.toggle('pasat-notice--error', !result.ok);
			notice.textContent = result.ok
				? 'Signup received. Please check your e-mail.'
				: (result.body && result.body.message ? result.body.message : 'Signup failed.');
			if (result.ok) {
				form.reset();
			}
		});
	});
}());

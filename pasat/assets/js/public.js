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
				? window.PASAT_PUBLIC.signupSuccess
				: (result.body && result.body.message ? result.body.message : window.PASAT_PUBLIC.signupFailed);
			if (result.ok) {
				form.reset();
			}
		});
	});

	function boardLabel(key, fallback) {
		return window.PASAT_PUBLIC && window.PASAT_PUBLIC.board && window.PASAT_PUBLIC.board[key]
			? window.PASAT_PUBLIC.board[key]
			: fallback;
	}

	function pluralLabel(template, value) {
		return String(template).replace('%d', value);
	}

	function boardStatus(activity) {
		if (!activity.signup_open) {
			return boardLabel('signupClosed', 'Signup closed');
		}

		if (activity.remaining === 0 && activity.waitlist_enabled) {
			return boardLabel('waitlistOpen', 'Waitlist open');
		}

		if (activity.remaining === 0) {
			return boardLabel('full', 'Full');
		}

		if (activity.remaining === null || activity.remaining === undefined) {
			return boardLabel('open', 'Open');
		}

		return pluralLabel(boardLabel('spotsLeft', '%d spots left'), activity.remaining);
	}

	function formatActivityDate(value) {
		if (!value) {
			return '';
		}

		var date = new Date(String(value).replace(' ', 'T') + 'Z');
		return isNaN(date.getTime()) ? value : date.toLocaleString();
	}

	function appendText(parent, tag, className, text) {
		if (!text && text !== 0) {
			return null;
		}

		var element = document.createElement(tag);
		if (className) {
			element.className = className;
		}
		element.textContent = text;
		parent.appendChild(element);
		return element;
	}

	function renderBoard(board, activities) {
		board.textContent = '';

		if (!activities.length) {
			appendText(board, 'p', 'pasat-empty', boardLabel('noActivities', 'No public activities are currently available.'));
			return;
		}

		activities.forEach(function (activity) {
			var card = document.createElement('article');
			card.className = 'pasat-card';
			card.setAttribute('data-pasat-activity-id', activity.id);

			var body = document.createElement('div');
			body.className = 'pasat-card__body';
			appendText(body, 'h3', 'pasat-card__title', activity.title);
			appendText(body, 'p', 'pasat-card__meta', formatActivityDate(activity.starts_at));
			appendText(body, 'p', 'pasat-card__meta', activity.venue_name);
			appendText(body, 'p', 'pasat-card__description', activity.description);

			var aside = document.createElement('div');
			aside.className = 'pasat-card__aside';
			appendText(aside, 'span', 'pasat-status', boardStatus(activity));
			appendText(
				aside,
				'span',
				'pasat-board-counts',
				pluralLabel(boardLabel('confirmed', '%d confirmed'), activity.confirmed || 0) + ', ' + pluralLabel(boardLabel('waitlisted', '%d waitlisted'), activity.waitlisted || 0)
			);

			card.appendChild(body);
			card.appendChild(aside);
			board.appendChild(card);
		});

		var updated = appendText(board, 'p', 'pasat-board-updated', boardLabel('updated', 'Updated just now'));
		if (updated) {
			updated.setAttribute('data-pasat-board-updated', '');
		}
	}

	function refreshBoard(board) {
		if (!window.fetch || !window.PASAT_PUBLIC || !window.PASAT_PUBLIC.restUrl) {
			return;
		}

		fetch(window.PASAT_PUBLIC.restUrl + '/activities?limit=100', {
			headers: {
				'Accept': 'application/json'
			}
		}).then(function (response) {
			return response.ok ? response.json() : [];
		}).then(function (activities) {
			renderBoard(board, Array.isArray(activities) ? activities : []);
		}).catch(function () {
			var updated = board.querySelector('[data-pasat-board-updated]');
			if (updated) {
				updated.textContent = '';
			}
		});
	}

	document.querySelectorAll('[data-pasat-activity-board]').forEach(function (board) {
		var interval = parseInt(board.getAttribute('data-pasat-poll-interval'), 10) || 60000;
		refreshBoard(board);
		window.setInterval(function () {
			refreshBoard(board);
		}, Math.max(interval, 15000));
	});
}());

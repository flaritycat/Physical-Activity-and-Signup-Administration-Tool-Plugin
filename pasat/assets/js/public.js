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

	function activityStartsSoon(activity) {
		if (!activity.starts_at) {
			return false;
		}

		var start = new Date(String(activity.starts_at).replace(' ', 'T') + 'Z');
		if (isNaN(start.getTime())) {
			return false;
		}

		var delta = start.getTime() - Date.now();
		return delta >= 0 && delta <= 60 * 60 * 1000;
	}

	function boardStatusKey(activity, options) {
		if (activity.status === 'cancelled') {
			return 'cancelled';
		}

		if (!activity.signup_open) {
			return 'signup-closed';
		}

		if (activityStartsSoon(activity)) {
			return 'starting-soon';
		}

		if (activity.remaining === 0 && activity.waitlist_enabled) {
			return 'waitlist-open';
		}

		if (activity.remaining === 0) {
			return 'full';
		}

		if (activity.remaining === null || activity.remaining === undefined) {
			return 'open';
		}

		if (activity.remaining <= options.fewSpotsThreshold) {
			return 'few-spots';
		}

		return 'spots-left';
	}

	function boardStatus(activity, options) {
		var key = boardStatusKey(activity, options);
		if (key === 'cancelled') {
			return boardLabel('cancelled', 'Cancelled');
		}
		if (key === 'signup-closed') {
			return boardLabel('signupClosed', 'Signup closed');
		}
		if (key === 'starting-soon') {
			return boardLabel('startingSoon', 'Starting soon');
		}
		if (key === 'waitlist-open') {
			return boardLabel('waitlistOpen', 'Waitlist open');
		}
		if (key === 'full') {
			return boardLabel('full', 'Full');
		}
		if (key === 'open') {
			return boardLabel('open', 'Open');
		}
		if (key === 'few-spots') {
			return boardLabel('fewSpots', 'Few spots left');
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

	function boardOptions(board) {
		return {
			activityType: board.getAttribute('data-pasat-activity-type') || '',
			fewSpotsThreshold: parseInt(board.getAttribute('data-pasat-few-spots-threshold'), 10) || 3,
			hostId: parseInt(board.getAttribute('data-pasat-host-id'), 10) || 0,
			limit: parseInt(board.getAttribute('data-pasat-limit'), 10) || 20,
			showQr: board.getAttribute('data-pasat-show-qr') === '1',
			venueId: parseInt(board.getAttribute('data-pasat-venue-id'), 10) || 0
		};
	}

	function stateSignature(activity, statusKey) {
		return JSON.stringify({
			confirmed: activity.confirmed || 0,
			remaining: activity.remaining,
			signup_open: !!activity.signup_open,
			status: statusKey,
			waitlisted: activity.waitlisted || 0
		});
	}

	function renderQr(target, value) {
		var matrix = createQrMatrix(value);
		if (!matrix) {
			target.textContent = boardLabel('qrFallback', 'Signup QR');
			return;
		}

		var quiet = 4;
		var size = matrix.length;
		var viewBox = size + quiet * 2;
		var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		var background = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
		var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		var commands = [];

		svg.setAttribute('viewBox', '0 0 ' + viewBox + ' ' + viewBox);
		svg.setAttribute('role', 'img');
		svg.setAttribute('focusable', 'false');
		background.setAttribute('width', viewBox);
		background.setAttribute('height', viewBox);
		background.setAttribute('fill', '#fff');
		path.setAttribute('fill', '#111');

		matrix.forEach(function (row, y) {
			row.forEach(function (dark, x) {
				if (dark) {
					commands.push('M' + (x + quiet) + ' ' + (y + quiet) + 'h1v1h-1z');
				}
			});
		});

		path.setAttribute('d', commands.join(''));
		svg.appendChild(background);
		svg.appendChild(path);
		target.textContent = '';
		target.appendChild(svg);
	}

	function renderExistingQr(board) {
		if (board.getAttribute('data-pasat-show-qr') !== '1') {
			return;
		}

		board.querySelectorAll('[data-pasat-qr-value]').forEach(function (target) {
			renderQr(target, target.getAttribute('data-pasat-qr-value') || '');
		});
	}

	function collectInitialStates(board) {
		var states = {};
		board.querySelectorAll('[data-pasat-activity-id][data-pasat-board-state]').forEach(function (card) {
			states[card.getAttribute('data-pasat-activity-id')] = card.getAttribute('data-pasat-board-state');
		});
		return states;
	}

	function setBoardMessage(board, mode, text) {
		var updated = board.querySelector('[data-pasat-board-updated]');
		if (!updated) {
			updated = appendText(board, 'p', 'pasat-board-updated', '');
			updated.setAttribute('data-pasat-board-updated', '');
		}

		board.setAttribute('data-pasat-refresh-state', mode);
		updated.classList.toggle('pasat-board-updated--error', mode === 'error');
		updated.classList.toggle('pasat-board-updated--refreshing', mode === 'refreshing');
		updated.textContent = text;
	}

	function updateBoardTimestamp(board) {
		if (!board._pasatLastUpdated || board.getAttribute('data-pasat-refresh-state') !== 'ok') {
			return;
		}

		var elapsed = Math.max(0, Math.floor((Date.now() - board._pasatLastUpdated) / 1000));
		if (elapsed < 5) {
			setBoardMessage(board, 'ok', boardLabel('updated', 'Updated just now'));
			return;
		}

		if (elapsed < 60) {
			setBoardMessage(board, 'ok', pluralLabel(boardLabel('updatedSecondsAgo', 'Updated %d seconds ago'), elapsed));
			return;
		}

		setBoardMessage(board, 'ok', pluralLabel(boardLabel('updatedMinutesAgo', 'Updated %d minutes ago'), Math.floor(elapsed / 60)));
	}

	function renderBoard(board, activities) {
		var options = boardOptions(board);
		var nextStates = {};
		board.textContent = '';

		if (!activities.length) {
			appendText(board, 'p', 'pasat-empty', boardLabel('noActivities', 'No public activities are currently available.'));
			board._pasatStates = {};
			board._pasatLastUpdated = Date.now();
			board._pasatFailures = 0;
			setBoardMessage(board, 'ok', boardLabel('updated', 'Updated just now'));
			return;
		}

		activities.forEach(function (activity) {
			var card = document.createElement('article');
			var statusKey = boardStatusKey(activity, options);
			var signature = stateSignature(activity, statusKey);
			var previous = board._pasatStates ? board._pasatStates[activity.id] : null;
			card.className = 'pasat-card';
			card.setAttribute('data-pasat-activity-id', activity.id);
			card.setAttribute('data-pasat-board-state', signature);
			nextStates[activity.id] = signature;

			if (previous && previous !== signature) {
				card.classList.add('pasat-board-changed');
				window.setTimeout(function () {
					card.classList.remove('pasat-board-changed');
				}, 2600);
			}

			var body = document.createElement('div');
			body.className = 'pasat-card__body';
			appendText(body, 'h3', 'pasat-card__title', activity.title);
			appendText(body, 'p', 'pasat-card__meta', formatActivityDate(activity.starts_at));
			appendText(body, 'p', 'pasat-card__meta', activity.venue_name);
			appendText(body, 'p', 'pasat-card__description', activity.description);

			var aside = document.createElement('div');
			aside.className = 'pasat-card__aside';
			var status = appendText(aside, 'span', 'pasat-status pasat-status--' + statusKey, boardStatus(activity, options));
			if (status) {
				status.setAttribute('data-pasat-status', '');
				status.setAttribute('data-pasat-status-key', statusKey);
			}
			appendText(
				aside,
				'span',
				'pasat-board-counts',
				pluralLabel(boardLabel('confirmed', '%d confirmed'), activity.confirmed || 0) + ', ' + pluralLabel(boardLabel('waitlisted', '%d waitlisted'), activity.waitlisted || 0)
			);

			if (options.showQr && activity.signup_url) {
				var qr = appendText(aside, 'span', 'pasat-board-qr', boardLabel('qrFallback', 'Signup QR'));
				var link = appendText(aside, 'a', 'pasat-board-qr-link', boardLabel('signUp', 'Sign up'));
				if (qr) {
					qr.setAttribute('data-pasat-qr-value', activity.signup_url);
					renderQr(qr, activity.signup_url);
				}
				if (link) {
					link.setAttribute('href', activity.signup_url);
				}
			}

			card.appendChild(body);
			card.appendChild(aside);
			board.appendChild(card);
		});

		board._pasatStates = nextStates;
		board._pasatLastUpdated = Date.now();
		board._pasatFailures = 0;
		setBoardMessage(board, 'ok', boardLabel('updated', 'Updated just now'));
	}

	function refreshBoard(board) {
		if (!window.fetch || !window.PASAT_PUBLIC || !window.PASAT_PUBLIC.restUrl) {
			return;
		}

		var options = boardOptions(board);
		var params = new URLSearchParams();
		params.set('limit', options.limit);
		if (options.venueId) {
			params.set('venue_id', options.venueId);
		}
		if (options.activityType) {
			params.set('activity_type', options.activityType);
		}
		if (options.hostId) {
			params.set('host_id', options.hostId);
		}

		setBoardMessage(board, 'refreshing', boardLabel('refreshing', 'Refreshing...'));

		fetch(window.PASAT_PUBLIC.restUrl + '/activities?' + params.toString(), {
			headers: {
				'Accept': 'application/json'
			}
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('PASAT board refresh failed');
			}
			return response.json();
		}).then(function (activities) {
			if (Array.isArray(activities)) {
				renderBoard(board, activities);
			}
		}).catch(function () {
			board._pasatFailures = (board._pasatFailures || 0) + 1;
			if (board._pasatFailures >= 2) {
				setBoardMessage(board, 'error', boardLabel('connectionLost', 'Connection lost. Showing last saved board.'));
			} else {
				board.setAttribute('data-pasat-refresh-state', 'ok');
				updateBoardTimestamp(board);
			}
		});
	}

	document.querySelectorAll('[data-pasat-activity-board]').forEach(function (board) {
		var interval = parseInt(board.getAttribute('data-pasat-poll-interval'), 10) || 60000;
		board._pasatStates = collectInitialStates(board);
		board._pasatLastUpdated = Date.now();
		board._pasatFailures = 0;
		board.setAttribute('data-pasat-refresh-state', 'ok');
		renderExistingQr(board);
		refreshBoard(board);
		window.setInterval(function () {
			refreshBoard(board);
		}, Math.max(interval, 15000));
		window.setInterval(function () {
			updateBoardTimestamp(board);
		}, 5000);
	});

	function createQrMatrix(text) {
		var bytes = textBytes(text);
		var versions = [
			{ version: 1, size: 21, data: 19, ecc: 7, align: [] },
			{ version: 2, size: 25, data: 34, ecc: 10, align: [6, 18] },
			{ version: 3, size: 29, data: 55, ecc: 15, align: [6, 22] },
			{ version: 4, size: 33, data: 80, ecc: 20, align: [6, 26] },
			{ version: 5, size: 37, data: 108, ecc: 26, align: [6, 30] }
		];
		var info = versions.find(function (candidate) {
			return bytes.length <= candidate.data - 2;
		});

		if (!info || !bytes.length) {
			return null;
		}

		var data = qrDataCodewords(bytes, info.data);
		var ecc = qrErrorCodewords(data, info.ecc);
		var codewords = data.concat(ecc);
		var matrix = Array.from({ length: info.size }, function () {
			return Array(info.size).fill(false);
		});
		var reserved = Array.from({ length: info.size }, function () {
			return Array(info.size).fill(false);
		});

		qrFunctionPatterns(matrix, reserved, info);
		qrDataModules(matrix, reserved, info, codewords);
		qrFormatBits(matrix, reserved, info.size);
		return matrix;
	}

	function textBytes(text) {
		if (window.TextEncoder) {
			return Array.from(new TextEncoder().encode(text));
		}

		return String(text).split('').map(function (char) {
			return char.charCodeAt(0) & 255;
		});
	}

	function qrDataCodewords(bytes, capacity) {
		var bits = [0, 1, 0, 0];
		var out = [];
		var pads = [0xec, 0x11];
		var padIndex = 0;
		appendBits(bits, bytes.length, 8);
		bytes.forEach(function (byte) {
			appendBits(bits, byte, 8);
		});
		appendBits(bits, 0, Math.min(4, capacity * 8 - bits.length));
		while (bits.length % 8) {
			bits.push(0);
		}
		while (bits.length) {
			out.push(bits.splice(0, 8).reduce(function (value, bit) {
				return (value << 1) | bit;
			}, 0));
		}
		while (out.length < capacity) {
			out.push(pads[padIndex % 2]);
			padIndex += 1;
		}
		return out;
	}

	function appendBits(bits, value, length) {
		for (var i = length - 1; i >= 0; i -= 1) {
			bits.push((value >>> i) & 1);
		}
	}

	function qrErrorCodewords(data, degree) {
		var gen = [1];
		var ecc = Array(degree).fill(0);
		for (var i = 0; i < degree; i += 1) {
			var next = Array(gen.length + 1).fill(0);
			for (var j = 0; j < gen.length; j += 1) {
				next[j] ^= gen[j];
				next[j + 1] ^= gfMul(gen[j], gfExp(i));
			}
			gen = next;
		}
		data.forEach(function (byte) {
			var factor = byte ^ ecc.shift();
			ecc.push(0);
			for (var k = 0; k < degree; k += 1) {
				ecc[k] ^= gfMul(gen[k + 1], factor);
			}
		});
		return ecc;
	}

	function gfExp(power) {
		var value = 1;
		for (var i = 0; i < power; i += 1) {
			value <<= 1;
			if (value & 0x100) {
				value ^= 0x11d;
			}
		}
		return value;
	}

	function gfMul(a, b) {
		var result = 0;
		while (b) {
			if (b & 1) {
				result ^= a;
			}
			a <<= 1;
			if (a & 0x100) {
				a ^= 0x11d;
			}
			b >>>= 1;
		}
		return result;
	}

	function qrSet(matrix, reserved, row, col, dark) {
		if (row < 0 || col < 0 || row >= matrix.length || col >= matrix.length) {
			return;
		}
		matrix[row][col] = !!dark;
		reserved[row][col] = true;
	}

	function qrFunctionPatterns(matrix, reserved, info) {
		qrFinder(matrix, reserved, 0, 0);
		qrFinder(matrix, reserved, info.size - 7, 0);
		qrFinder(matrix, reserved, 0, info.size - 7);
		for (var i = 8; i < info.size - 8; i += 1) {
			qrSet(matrix, reserved, 6, i, i % 2 === 0);
			qrSet(matrix, reserved, i, 6, i % 2 === 0);
		}
		info.align.forEach(function (row) {
			info.align.forEach(function (col) {
				if (!reserved[row][col]) {
					qrAlignment(matrix, reserved, row, col);
				}
			});
		});
		qrSet(matrix, reserved, 4 * info.version + 9, 8, true);
		for (var j = 0; j < 9; j += 1) {
			qrSet(matrix, reserved, 8, j, false);
			qrSet(matrix, reserved, j, 8, false);
		}
		for (var k = 0; k < 8; k += 1) {
			qrSet(matrix, reserved, 8, info.size - 1 - k, false);
			qrSet(matrix, reserved, info.size - 1 - k, 8, false);
		}
	}

	function qrFinder(matrix, reserved, row, col) {
		for (var y = -1; y <= 7; y += 1) {
			for (var x = -1; x <= 7; x += 1) {
				var dark = x >= 0 && x <= 6 && y >= 0 && y <= 6 && (x === 0 || x === 6 || y === 0 || y === 6 || (x >= 2 && x <= 4 && y >= 2 && y <= 4));
				qrSet(matrix, reserved, row + y, col + x, dark);
			}
		}
	}

	function qrAlignment(matrix, reserved, row, col) {
		for (var y = -2; y <= 2; y += 1) {
			for (var x = -2; x <= 2; x += 1) {
				qrSet(matrix, reserved, row + y, col + x, Math.max(Math.abs(x), Math.abs(y)) !== 1);
			}
		}
	}

	function qrDataModules(matrix, reserved, info, codewords) {
		var bits = [];
		codewords.forEach(function (word) {
			appendBits(bits, word, 8);
		});
		var index = 0;
		var upward = true;
		for (var right = info.size - 1; right >= 1; right -= 2) {
			if (right === 6) {
				right -= 1;
			}
			for (var vert = 0; vert < info.size; vert += 1) {
				var row = upward ? info.size - 1 - vert : vert;
				for (var offset = 0; offset < 2; offset += 1) {
					var col = right - offset;
					if (reserved[row][col]) {
						continue;
					}
					var dark = !!bits[index];
					if ((row + col) % 2 === 0) {
						dark = !dark;
					}
					matrix[row][col] = dark;
					index += 1;
				}
			}
			upward = !upward;
		}
	}

	function qrFormatBits(matrix, reserved, size) {
		var bits = 0x77c4;
		for (var i = 0; i < 15; i += 1) {
			var dark = ((bits >>> i) & 1) === 1;
			if (i < 6) {
				qrSet(matrix, reserved, 8, i, dark);
			} else if (i < 8) {
				qrSet(matrix, reserved, 8, i + 1, dark);
			} else {
				qrSet(matrix, reserved, 8, size - 15 + i, dark);
			}
			if (i < 8) {
				qrSet(matrix, reserved, size - i - 1, 8, dark);
			} else if (i < 9) {
				qrSet(matrix, reserved, 15 - i, 8, dark);
			} else {
				qrSet(matrix, reserved, 15 - i - 1, 8, dark);
			}
		}
	}
}());

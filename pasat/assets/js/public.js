(function () {
	'use strict';

	function publicLabel(key, fallback) {
		return window.PASAT_PUBLIC && window.PASAT_PUBLIC[key]
			? window.PASAT_PUBLIC[key]
			: fallback;
	}

	function selectedOption(select) {
		return select && select.options && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
	}

	function setElementText(element, value) {
		if (!element) {
			return;
		}
		element.textContent = value || '';
		element.hidden = !value;
	}

	function focusElement(element) {
		if (!element || typeof element.focus !== 'function') {
			return;
		}

		try {
			element.focus({ preventScroll: false });
		} catch (error) {
			element.focus();
		}
	}

	function setSubmitState(form, submitting) {
		var button = form.querySelector('[type="submit"]');
		var label = button ? button.querySelector('[data-pasat-submit-text]') : null;
		if (!button) {
			return;
		}

		if (!button.getAttribute('data-pasat-submit-label')) {
			button.setAttribute('data-pasat-submit-label', button.textContent);
		}

		button.disabled = submitting;
		form.setAttribute('aria-busy', submitting ? 'true' : 'false');
		if (label) {
			label.textContent = submitting ? publicLabel('signupSubmitting', 'Submitting...') : button.getAttribute('data-pasat-submit-label');
		} else {
			button.textContent = submitting ? publicLabel('signupSubmitting', 'Submitting...') : button.getAttribute('data-pasat-submit-label');
		}
	}

	function signupSuccessMessage(body) {
		if (body && body.status === 'waitlisted') {
			return publicLabel('signupWaitlisted', 'Signup received. You are on the waitlist. Please check your e-mail.');
		}
		if (body && body.status === 'confirmed') {
			return publicLabel('signupConfirmed', 'Signup received. You are confirmed. Please check your e-mail.');
		}
		return publicLabel('signupSuccess', 'Signup received. Please check your e-mail.');
	}

	function showSignupNotice(form, ok, message) {
		var container = form.closest('.pasat-signup') || form.parentNode;
		var region = container.querySelector('[data-pasat-notice-region]');
		var notice;
		if (!region) {
			region = document.createElement('div');
			region.className = 'pasat-notice-region';
			region.setAttribute('data-pasat-notice-region', '');
			region.setAttribute('aria-live', 'polite');
			region.setAttribute('aria-atomic', 'true');
			container.insertBefore(region, container.firstChild);
		}

		notice = region.querySelector('.pasat-js-notice');
		if (!notice) {
			notice = document.createElement('div');
			notice.className = 'pasat-js-notice pasat-notice';
			region.appendChild(notice);
		}

		notice.classList.toggle('pasat-notice--success', ok);
		notice.classList.toggle('pasat-notice--error', !ok);
		notice.setAttribute('role', ok ? 'status' : 'alert');
		notice.setAttribute('tabindex', '-1');
		notice.textContent = message;
		focusElement(notice);
	}

	function invalidControlMessage(control) {
		return control && control.validationMessage
			? control.validationMessage
			: publicLabel('formInvalid', 'Please complete the highlighted field.');
	}

	function setControlInvalid(control, invalid) {
		var field = control ? control.closest('.pasat-field, .pasat-check') : null;
		if (!control) {
			return;
		}

		if (invalid) {
			control.setAttribute('aria-invalid', 'true');
		} else {
			control.removeAttribute('aria-invalid');
		}

		if (field) {
			field.classList.toggle('pasat-field--invalid', invalid);
		}
	}

	function clearValidControl(control) {
		if (!control || !control.closest('[data-pasat-signup-form]')) {
			return;
		}

		if (!control.validity || control.validity.valid) {
			setControlInvalid(control, false);
		}
	}

	function updateAckSection(section) {
		if (!section) {
			return;
		}

		var visible = Array.prototype.some.call(section.querySelectorAll('.pasat-check'), function (check) {
			return !check.classList.contains('pasat-is-hidden');
		});
		section.classList.toggle('pasat-is-hidden', !visible);
	}

	function updateSignupSummary(form) {
		var select = form.querySelector('[name="activity_id"]');
		var option = selectedOption(select);
		var container = form.closest('.pasat-signup') || form.parentNode;
		var summary = container.querySelector('[data-pasat-signup-summary]');
		var warning = form.querySelector('[data-pasat-warning-check]');
		var warningInput = warning ? warning.querySelector('input') : null;
		var warningText = warning ? warning.querySelector('[data-pasat-warning-text]') : null;
		var ackSection = form.querySelector('[data-pasat-ack-section]');
		var ageNote = form.querySelector('[data-pasat-age-note]');
		var hasActivity = option && option.value;
		var warningValue = hasActivity ? option.getAttribute('data-pasat-warning') || '' : (warning ? warning.getAttribute('data-pasat-default-warning') || '' : '');
		var warningRequired = hasActivity && option.getAttribute('data-pasat-warning-required') === '1';

		if (summary) {
			summary.classList.toggle('pasat-signup-summary--empty', !hasActivity);
			setElementText(summary.querySelector('[data-pasat-summary-title]'), hasActivity ? option.getAttribute('data-pasat-title') : summary.getAttribute('data-pasat-empty-title'));
			setElementText(summary.querySelector('[data-pasat-summary-date]'), hasActivity ? option.getAttribute('data-pasat-date') : '');
			setElementText(summary.querySelector('[data-pasat-summary-venue]'), hasActivity ? option.getAttribute('data-pasat-venue') : '');
			setElementText(summary.querySelector('[data-pasat-summary-type]'), hasActivity ? option.getAttribute('data-pasat-type') : '');
			setElementText(summary.querySelector('[data-pasat-summary-age]'), hasActivity ? option.getAttribute('data-pasat-age-note') : '');
			setElementText(summary.querySelector('[data-pasat-summary-description]'), hasActivity ? option.getAttribute('data-pasat-description') : '');
			setElementText(summary.querySelector('[data-pasat-summary-capacity]'), hasActivity ? option.getAttribute('data-pasat-capacity') : summary.getAttribute('data-pasat-empty-meta'));

			var status = summary.querySelector('[data-pasat-summary-status]');
			if (status) {
				status.className = hasActivity
					? 'pasat-status pasat-status--' + (option.getAttribute('data-pasat-status-key') || 'open')
					: 'pasat-status pasat-is-hidden';
				status.textContent = hasActivity ? option.getAttribute('data-pasat-status') || '' : '';
			}
		}

		setElementText(ageNote, hasActivity ? option.getAttribute('data-pasat-age-note') : '');

		if (warning && warningInput && warningText) {
			warning.classList.toggle('pasat-is-hidden', !warningValue);
			warningInput.required = !!(warningValue && warningRequired);
			warningInput.setAttribute('aria-required', warningInput.required ? 'true' : 'false');
			if (!warningValue) {
				warningInput.checked = false;
			}
			warningText.textContent = warningValue;
		}
		updateAckSection(ackSection);
	}

	function initSignupForm(form) {
		var select = form.querySelector('[name="activity_id"]');
		updateSignupSummary(form);
		if (select) {
			select.addEventListener('change', function () {
				updateSignupSummary(form);
			});
		}
	}

	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.matches('[data-pasat-signup-form]') || !window.fetch || !window.PASAT_PUBLIC) {
			return;
		}

		event.preventDefault();
		var data = new FormData(form);
		var payload = {};
		var selectedActivity = form.querySelector('[name="activity_id"]') ? form.querySelector('[name="activity_id"]').value : '';
		data.forEach(function (value, key) {
			payload[key] = value;
		});

		setSubmitState(form, true);

		fetch(window.PASAT_PUBLIC.restUrl + '/signups', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.PASAT_PUBLIC.nonce
			},
			body: JSON.stringify(payload)
		}).then(function (response) {
			return response.json().catch(function () {
				return {};
			}).then(function (body) {
				return { ok: response.ok, body: body };
			});
		}).then(function (result) {
			showSignupNotice(
				form,
				result.ok,
				result.ok
					? signupSuccessMessage(result.body)
					: (result.body && result.body.message ? result.body.message : publicLabel('signupFailed', 'Signup failed.'))
			);
			if (result.ok) {
				form.reset();
				if (selectedActivity && form.querySelector('[name="activity_id"]')) {
					form.querySelector('[name="activity_id"]').value = selectedActivity;
				}
				updateSignupSummary(form);
			}
		}).catch(function () {
			showSignupNotice(form, false, publicLabel('signupNetworkError', 'Signup could not be submitted. Please try again.'));
		}).then(function () {
			setSubmitState(form, false);
		});
	});

	document.addEventListener('invalid', function (event) {
		var control = event.target;
		var form = control && control.closest ? control.closest('[data-pasat-signup-form]') : null;
		if (!form) {
			return;
		}

		setControlInvalid(control, true);
		if (form._pasatInvalidAnnounced) {
			return;
		}

		form._pasatInvalidAnnounced = true;
		showSignupNotice(form, false, invalidControlMessage(control));
		window.setTimeout(function () {
			form._pasatInvalidAnnounced = false;
			focusElement(control);
		}, 0);
	}, true);

	document.addEventListener('input', function (event) {
		clearValidControl(event.target);
	});

	document.addEventListener('change', function (event) {
		clearValidControl(event.target);
	});

	function boardLabel(key, fallback) {
		return window.PASAT_PUBLIC && window.PASAT_PUBLIC.board && window.PASAT_PUBLIC.board[key]
			? window.PASAT_PUBLIC.board[key]
			: fallback;
	}

	function pluralLabel(template, value) {
		return String(template).replace('%d', value);
	}

	function templateLabel(template, value) {
		return String(template).replace('%s', value || '');
	}

	function numberedTemplateLabel(template, values) {
		var output = String(template);
		values.forEach(function (value, index) {
			output = output.replace('%' + (index + 1) + '$d', value);
		});
		return output;
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

	function activityDateParts(value) {
		if (!value) {
			return {
				day: '-',
				label: '',
				month: 'Date',
				time: 'TBA'
			};
		}

		var date = new Date(String(value).replace(' ', 'T') + 'Z');
		if (isNaN(date.getTime())) {
			return {
				day: '-',
				label: value,
				month: 'Date',
				time: value
			};
		}

		return {
			day: date.toLocaleDateString(undefined, { day: 'numeric' }),
			label: date.toLocaleString(),
			month: date.toLocaleDateString(undefined, { month: 'short' }),
			time: date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
		};
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

	function normalizeFilterValue(value) {
		return String(value || '').toLowerCase().trim();
	}

	function initActivityFilters(filterBar) {
		var list = filterBar.closest('.pasat-activity-list');
		if (!list) {
			return;
		}

		var cards = Array.prototype.slice.call(list.querySelectorAll('[data-pasat-activity-card]'));
		var search = filterBar.querySelector('[data-pasat-filter-search]');
		var type = filterBar.querySelector('[data-pasat-filter-type]');
		var venue = filterBar.querySelector('[data-pasat-filter-venue]');
		var reset = filterBar.querySelector('[data-pasat-filter-reset]');
		var empty = list.querySelector('[data-pasat-filter-empty]');
		var count = filterBar.querySelector('[data-pasat-filter-count]');
		var countTemplate = count ? count.getAttribute('data-pasat-filter-template') : '';
		var total = cards.length;

		function applyFilters() {
			var searchValue = normalizeFilterValue(search ? search.value : '');
			var typeValue = type ? type.value : '';
			var venueValue = venue ? venue.value : '';
			var hasFilters = !!(searchValue || typeValue || venueValue);
			var visible = 0;

			cards.forEach(function (card) {
				var matchesSearch = !searchValue || normalizeFilterValue(card.getAttribute('data-pasat-search')).indexOf(searchValue) !== -1;
				var matchesType = !typeValue || card.getAttribute('data-pasat-type') === typeValue;
				var matchesVenue = !venueValue || card.getAttribute('data-pasat-venue') === venueValue;
				var shown = matchesSearch && matchesType && matchesVenue;
				card.hidden = !shown;
				if (shown) {
					visible += 1;
				}
			});

			if (empty) {
				empty.hidden = visible !== 0;
			}

			if (count && countTemplate) {
				count.textContent = numberedTemplateLabel(countTemplate, [visible, total]);
			}

			if (reset) {
				reset.disabled = !hasFilters;
				reset.setAttribute('aria-disabled', hasFilters ? 'false' : 'true');
			}
		}

		[search, type, venue].forEach(function (control) {
			if (control) {
				control.addEventListener('input', applyFilters);
				control.addEventListener('change', applyFilters);
			}
		});

		if (reset) {
			reset.addEventListener('click', function () {
				if (search) {
					search.value = '';
					search.focus();
				}
				if (type) {
					type.value = '';
				}
				if (venue) {
					venue.value = '';
				}
				applyFilters();
			});
		}

		applyFilters();
	}

	function boardOptions(board) {
		return {
			activityType: board.getAttribute('data-pasat-activity-type') || '',
			fewSpotsThreshold: parseInt(board.getAttribute('data-pasat-few-spots-threshold'), 10) || 3,
			hostId: parseInt(board.getAttribute('data-pasat-host-id'), 10) || 0,
			limit: parseInt(board.getAttribute('data-pasat-limit'), 10) || 20,
			mode: board.getAttribute('data-pasat-mode') || 'list',
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
			updated.setAttribute('role', 'status');
			updated.setAttribute('aria-live', 'polite');
			updated.setAttribute('aria-atomic', 'true');
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

	function boardFocusSnapshot(list) {
		var active = document.activeElement;
		var link;
		if (!active || !list.contains(active)) {
			return null;
		}

		link = active.closest ? active.closest('a[href]') : null;
		if (!link || !list.contains(link)) {
			return null;
		}

		return {
			href: link.getAttribute('href')
		};
	}

	function restoreBoardFocus(list, snapshot) {
		if (!snapshot || !snapshot.href) {
			return;
		}

		Array.prototype.some.call(list.querySelectorAll('a[href]'), function (link) {
			if (link.getAttribute('href') !== snapshot.href) {
				return false;
			}

			focusElement(link);
			return true;
		});
	}

	function renderBoard(board, activities) {
		var options = boardOptions(board);
		var list = board.querySelector('[data-pasat-board-items]') || board;
		var focusSnapshot = boardFocusSnapshot(list);
		var nextStates = {};
		list.textContent = '';

		if (!activities.length) {
			appendText(list, 'p', 'pasat-empty', boardLabel('noActivities', 'No public activities are currently available.'));
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
			card.className = 'pasat-card pasat-board-card';
			card.setAttribute('data-pasat-activity-id', activity.id);
			card.setAttribute('data-pasat-board-state', signature);
			nextStates[activity.id] = signature;

			if (previous && previous !== signature) {
				card.classList.add('pasat-board-changed');
				window.setTimeout(function () {
					card.classList.remove('pasat-board-changed');
				}, 2600);
			}

			var parts = activityDateParts(activity.starts_at);
			var dateBlock = document.createElement('div');
			dateBlock.className = 'pasat-card__date';
			dateBlock.setAttribute('aria-label', parts.label || boardLabel('dateTba', 'Date to be announced'));
			appendText(dateBlock, 'span', 'pasat-card__date-month', parts.month);
			appendText(dateBlock, 'span', 'pasat-card__date-day', parts.day);
			appendText(dateBlock, 'span', 'pasat-card__date-time', parts.time);

			var body = document.createElement('div');
			body.className = 'pasat-card__body';
			appendText(body, 'span', 'pasat-chip', activity.activity_type);
			appendText(body, 'h3', 'pasat-card__title', activity.title);
			var details = document.createElement('div');
			details.className = 'pasat-card__details';
			appendText(details, 'span', '', formatActivityDate(activity.starts_at));
			appendText(details, 'span', '', activity.venue_name);
			if (details.childNodes.length) {
				body.appendChild(details);
			}
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

			if (options.showQr && (activity.qr_url || activity.signup_url)) {
				var qrValue = activity.qr_url || activity.signup_url;
				var qrWrap = document.createElement('div');
				qrWrap.className = 'pasat-board-qr-wrap';
				var qr = appendText(qrWrap, 'span', 'pasat-board-qr', boardLabel('qrFallback', 'Signup QR'));
				var link = appendText(qrWrap, 'a', 'pasat-board-qr-link', boardLabel('signUp', 'Sign up'));
				if (qr) {
					qr.setAttribute('data-pasat-qr-value', qrValue);
					qr.setAttribute('aria-label', templateLabel(boardLabel('qrForActivity', 'Signup QR code for %s'), activity.title));
					renderQr(qr, qrValue);
				}
				if (link) {
					link.setAttribute('href', activity.signup_url);
					link.setAttribute('aria-label', templateLabel(boardLabel('signUpForActivity', 'Sign up for %s'), activity.title));
				}
				aside.appendChild(qrWrap);
			}

			card.appendChild(dateBlock);
			card.appendChild(body);
			card.appendChild(aside);
			list.appendChild(card);
		});

		board._pasatStates = nextStates;
		board._pasatLastUpdated = Date.now();
		board._pasatFailures = 0;
		restoreBoardFocus(list, focusSnapshot);
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

	document.querySelectorAll('[data-pasat-signup-form]').forEach(initSignupForm);

	document.querySelectorAll('[data-pasat-activity-filters]').forEach(initActivityFilters);

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

	function mapLabel(key, fallback) {
		return window.PASAT_PUBLIC && window.PASAT_PUBLIC.map && window.PASAT_PUBLIC.map[key]
			? window.PASAT_PUBLIC.map[key]
			: fallback;
	}

	function venuePopup(venue) {
		var wrapper = document.createElement('div');
		wrapper.className = 'pasat-map-popup';
		var title = appendText(wrapper, 'strong', 'pasat-map-popup__title', venue.name);
		var activities = Array.isArray(venue.activities) ? venue.activities : [];
		if (title) {
			title.setAttribute('tabindex', '-1');
		}
		appendText(wrapper, 'p', 'pasat-map-popup__meta', venue.address);
		if (activities.length) {
			appendText(wrapper, 'p', 'pasat-map-popup__label', activities.length === 1 ? mapLabel('activity', 'Activity') : mapLabel('activities', 'Activities'));
			var list = document.createElement('ul');
			list.className = 'pasat-map-popup__activities';
			activities.forEach(function (activity) {
				var item = document.createElement('li');
				var link = document.createElement('a');
				link.href = activity.signup_url;
				link.textContent = activity.title;
				item.appendChild(link);
				if (activity.date_label) {
					item.appendChild(document.createTextNode(' ' + activity.date_label));
				}
				list.appendChild(item);
			});
			wrapper.appendChild(list);
		}
		if (venue.map_url) {
			var directions = document.createElement('a');
			directions.className = 'pasat-map-popup__directions';
			directions.href = venue.map_url;
			directions.target = '_blank';
			directions.rel = 'noopener noreferrer';
			directions.setAttribute('aria-label', templateLabel(mapLabel('directionsToVenue', 'Directions to %s'), venue.name));
			directions.textContent = mapLabel('directions', 'Directions');
			wrapper.appendChild(directions);
		}
		return wrapper;
	}

	function initVenueMap(mapElement) {
		var canvas = mapElement.querySelector('[data-pasat-map-canvas]');
		if (!canvas || mapElement.getAttribute('data-pasat-map-enabled') !== '1' || !window.L || !window.PASAT_PUBLIC) {
			return;
		}

		var venues;
		try {
			venues = JSON.parse(mapElement.getAttribute('data-venues') || '[]');
		} catch (error) {
			venues = [];
		}

		venues = venues.filter(function (venue) {
			return venue.latitude !== null && venue.longitude !== null;
		});
		if (!venues.length) {
			return;
		}

		var config = window.PASAT_PUBLIC.map || {};
		var map = window.L.map(canvas, {
			scrollWheelZoom: false
		});
		var bounds = [];
		var cards = Array.prototype.slice.call(mapElement.querySelectorAll('[data-pasat-venue-card]'));
		var status = mapElement.querySelector('[data-pasat-map-status]');
		var markers = {};

		function activateVenue(venueId, options) {
			var id = String(venueId || '');
			var marker = markers[id];
			var activeCard = null;
			options = options || {};

			cards.forEach(function (card) {
				var active = card.getAttribute('data-pasat-venue-id') === id;
				var button = card.querySelector('[data-pasat-map-focus]');
				card.classList.toggle('pasat-venue-card--active', active);
				if (button) {
					button.setAttribute('aria-pressed', active ? 'true' : 'false');
				}
				if (active) {
					activeCard = card;
				}
			});

			if (marker && options.openMarker) {
				map.panTo(marker.getLatLng());
				marker.openPopup();
			}

			if (activeCard && options.revealCard) {
				activeCard.scrollIntoView({
					block: 'nearest'
				});
			}

			if (activeCard && options.focusCard) {
				focusElement(activeCard);
			}

			if (status && activeCard && options.announce !== false) {
				status.textContent = templateLabel(
					mapLabel('showingVenue', 'Showing %s on the map.'),
					(activeCard.querySelector('.pasat-venue-card__title') || {}).textContent || ''
				);
			}
		}

		window.L.tileLayer(config.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: config.attribution || '&copy; OpenStreetMap contributors',
			maxZoom: 19
		}).addTo(map);

		venues.forEach(function (venue) {
			var latLng = [parseFloat(venue.latitude), parseFloat(venue.longitude)];
			var marker;
			if (isNaN(latLng[0]) || isNaN(latLng[1])) {
				return;
			}
			bounds.push(latLng);
			marker = window.L.marker(latLng).addTo(map);
			marker.bindPopup(venuePopup(venue));
			markers[String(venue.id)] = marker;
			marker.on('click', function () {
				activateVenue(venue.id, {
					revealCard: true
				});
			});
			marker.on('popupopen', function () {
				activateVenue(venue.id, {
					revealCard: true
				});
			});
		});

		cards.forEach(function (card) {
			var venueId = card.getAttribute('data-pasat-venue-id');
			var focusButton = card.querySelector('[data-pasat-map-focus]');
			if (!focusButton || !markers[String(venueId)]) {
				return;
			}

			focusButton.addEventListener('click', function () {
				activateVenue(venueId, {
					openMarker: true
				});
			});
			card.addEventListener('focusin', function () {
				activateVenue(venueId, {
					announce: false
				});
			});
		});

		if (bounds.length === 1) {
			map.setView(bounds[0], parseInt(config.zoom, 10) || 13);
		} else {
			map.fitBounds(bounds, {
				padding: [24, 24]
			});
		}

		mapElement.classList.add('pasat-venue-map--ready');
		window.setTimeout(function () {
			map.invalidateSize();
		}, 100);
	}

	document.querySelectorAll('[data-pasat-venue-map]').forEach(initVenueMap);

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

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
}());

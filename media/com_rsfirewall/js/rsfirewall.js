var RSFirewall = {};

RSFirewall.parseJSON = function (data) {
	if (typeof data != 'object') {
		// parse invalid data:
		var match = data.match(/{.*}/);

		return jQuery.parseJSON(match[0]);
	}

	return jQuery.parseJSON(data);
};

RSFirewall.requestTimeOut = {};
RSFirewall.requestTimeOut.Seconds = 0;
RSFirewall.requestTimeOut.Milliseconds = function () {
	return parseFloat(RSFirewall.requestTimeOut.Seconds) * 1000;
};

/* loading helper */
RSFirewall.removeLoading = function () {
	jQuery('.com-rsfirewall-icon-16-loading').remove();
};
RSFirewall.addLoading = function (where, type) {
	var loader = '<span class="com-rsfirewall-icon-16-loading"></span>';
	if (typeof type === 'undefined') {
		jQuery(where).append(loader);
	} else {
		jQuery(where)[type](loader);
	}
};

document.addEventListener('DOMContentLoaded', function() {
	var buttons, i;

	buttons = document.querySelectorAll('[data-rsfirewall-task]');
	for (i = 0; i < buttons.length; i++) {
		buttons[i].addEventListener('click', function () {
			Joomla.submitbutton(this.getAttribute('data-rsfirewall-task'));
		});
	}
});
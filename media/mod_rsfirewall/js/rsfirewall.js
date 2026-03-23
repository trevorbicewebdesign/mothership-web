jQuery(document).ready(function($) {
	function checkJoomlaVersion()
	{
		$.ajax({
			converters: {
				"text json": RSFirewall.parseJSON
			},
			dataType: 'json',
			type: 'POST',
			url: 'index.php',
			data: {
				'option': 'com_rsfirewall',
				'task': 'getLatestJoomlaVersion'
			},
			error: function(jqXHR, textStatus, errorThrown) {				
				jQuery('#mod-rsfirewall-joomla-version').html(textStatus).addClass('com-rsfirewall-error');
			},
			success: function(json) {
				var message = json.data.message;
				if (json.success == true) {
					if (json.data.is_latest == true) {
						jQuery('#mod-rsfirewall-joomla-version').html(message).addClass('com-rsfirewall-ok');
					} else {
						jQuery('#mod-rsfirewall-joomla-version').html(message).addClass('com-rsfirewall-notice');
					}
				} else {
					jQuery('#mod-rsfirewall-joomla-version').html(message).addClass('com-rsfirewall-error');
				}
			}
		});
	}
	
	function checkRSFirewallVersion()
	{
		$.ajax({
			converters: {
				"text json": RSFirewall.parseJSON
			},
			dataType: 'json',
			type: 'POST',
			url: 'index.php',
			data: {
				'option': 'com_rsfirewall',
				'task': 'getLatestFirewallVersion'
			},
			error: function(jqXHR, textStatus, errorThrown) {
				checkJoomlaVersion();
				jQuery('#mod-rsfirewall-firewall-version').html(textStatus).addClass('com-rsfirewall-error');
			},
			success: function(json) {
				checkJoomlaVersion();

				var message;
				if (json.success == true) {
					if (json.data.is_latest == true) {
						message = Joomla.JText._('MOD_RSFIREWALL_YOU_ARE_RUNNING_LATEST_VERSION').replace('%s', json.data.current);
						jQuery('#mod-rsfirewall-firewall-version').html(message).addClass('com-rsfirewall-ok');
					} else {
						message = Joomla.JText._('MOD_RSFIREWALL_UPDATE_IS_AVAILABLE_RSFIREWALL').replace('%s', json.data.latest);
						jQuery('#mod-rsfirewall-firewall-version').html(message).addClass('com-rsfirewall-notice');
					}
				} else {
					message = json.data.message;
					jQuery('#mod-rsfirewall-firewall-version').html(message).addClass('com-rsfirewall-error');
				}
			}
		});
	}
	
	window.setTimeout(checkRSFirewallVersion, 2000);
});
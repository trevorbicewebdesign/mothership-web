RSFirewall.Continents = {
    '--': ['A1', 'A2', 'O1'],
    'EU': ['AD', 'AL', 'AT', 'AX', 'BA', 'BE', 'BG', 'BY', 'CH', 'CZ', 'CY', 'DE', 'DK', 'EE', 'ES', 'EU', 'FI', 'FO', 'FR', 'GB', 'GG', 'GI', 'GR', 'HR', 'HU', 'IE', 'IM', 'IS', 'IT', 'JE', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME', 'MK', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'RU', 'SE', 'SI', 'SJ', 'SK', 'SM', 'TR', 'UA', 'VA'],
    'AS': ['AE', 'AF', 'AM', 'AP', 'AZ', 'BD', 'BH', 'BN', 'BT', 'CC', 'CN', 'CX', 'GE', 'HK', 'ID', 'IL', 'IN', 'IO', 'IQ', 'IR', 'JO', 'JP', 'KG', 'KH', 'KP', 'KR', 'KW', 'KZ', 'LA', 'LB', 'LK', 'MM', 'MN', 'MO', 'MV', 'MY', 'NP', 'OM', 'PH', 'PK', 'PS', 'QA', 'SA', 'SG', 'SY', 'TH', 'TJ', 'TL', 'TM', 'TW', 'UZ', 'VN', 'YE'],
    'NA': ['AG', 'AI', 'AN', 'AW', 'BB', 'BM', 'BS', 'BZ', 'CA', 'CR', 'CU', 'DM', 'DO', 'GD', 'GL', 'GP', 'GT', 'HN', 'HT', 'JM', 'KN', 'KY', 'LC', 'MQ', 'MS', 'MX', 'NI', 'PA', 'PM', 'PR', 'SV', 'TC', 'TT', 'US', 'VC', 'VG', 'VI'],
    'AF': ['AO', 'BF', 'BI', 'BJ', 'BW', 'CD', 'CF', 'CG', 'CI', 'CM', 'CV', 'DJ', 'DZ', 'EG', 'EH', 'ER', 'ET', 'GA', 'GH', 'GM', 'GN', 'GQ', 'GW', 'KE', 'KM', 'LR', 'LS', 'LY', 'MA', 'MG', 'ML', 'MR', 'MU', 'MW', 'MZ', 'NA', 'NE', 'NG', 'RE', 'RW', 'SC', 'SD', 'SH', 'SL', 'SN', 'SO', 'ST', 'SZ', 'TD', 'TG', 'TN', 'TZ', 'UG', 'YT', 'ZA', 'ZM', 'ZW'],
    'AN': ['AQ', 'BV', 'GS', 'HM', 'TF'],
    'SA': ['AR', 'BO', 'BR', 'CL', 'CO', 'EC', 'FK', 'GF', 'GY', 'PE', 'PY', 'SR', 'UY', 'VE'],
    'OC': ['AS', 'AU', 'CK', 'FJ', 'FM', 'GU', 'KI', 'MH', 'MP', 'NC', 'NF', 'NR', 'NU', 'NZ', 'PF', 'PG', 'PN', 'PW', 'SB', 'TK', 'TO', 'TV', 'UM', 'VU', 'WF', 'WS']
};

RSFirewall.initCountryBlocking = function() {
    RSFirewall.fixCheckAllCountries();
    RSFirewall.fixCheckContinents();

    jQuery(document.getElementsByName('jform[blocked_countries][]')).change(function(){
        RSFirewall.fixCheckAllCountries();
        RSFirewall.fixCheckContinents();
    });
    jQuery(document.getElementsByName('jform[blocked_continents][]')).change(function(){
        RSFirewall.checkCountries(this);
        RSFirewall.fixCheckAllCountries();
        RSFirewall.fixCheckContinents();
    });
    jQuery(document.getElementsByName('jform[blocked_countries_checkall][]')).change(function(){
        RSFirewall.fixCheckAllCountries();
        RSFirewall.fixCheckContinents();
    });
    jQuery(document.getElementsByName('jform[blocked_countries_checkall][]')).click(function(){
        RSFirewall.checkAllCountries(this.checked);
    });
};

RSFirewall.checkCountries = function(el) {
    for (var continent in RSFirewall.Continents) {
        if (RSFirewall.Continents.hasOwnProperty(continent) && el.value === continent) {
            var countries = RSFirewall.Continents[continent];
            for (var i = 0; i < countries.length; i++) {
                jQuery('#jform_blocked_countries').find('[value="' + countries[i] + '"]').prop('checked', el.checked);
            }
        }
    }
};

RSFirewall.checkAllCountries = function(value) {
    var items = document.getElementsByName('jform[blocked_countries][]');
    for (var i = 0; i < items.length; i++) {
        items[i].checked = value;
    }
};

RSFirewall.fixCheckAllCountries = function() {
    var items 	= document.getElementsByName('jform[blocked_countries][]');
    var checked = 0;
    for (var i = 0; i < items.length; i++) {
        if (items[i].checked === true) {
            checked++;
        }
    }
    var checkAll = document.getElementsByName('jform[blocked_countries_checkall][]')[0];
    if (checked === 0 || checked < items.length) {
        checkAll.checked = false;
    } else if (checked === items.length) {
        checkAll.checked = true;
    }
};

RSFirewall.fixCheckContinents = function() {
    var continents = document.getElementsByName('jform[blocked_continents][]');
    for (var i = 0; i < continents.length; i++) {
        var countries = RSFirewall.Continents[continents[i].value];
        var checked	  = 0;
        for (var j = 0; j < countries.length; j++) {
            if (jQuery('#jform_blocked_countries').find('[value="' + countries[j] + '"]').prop('checked')) {
                checked++;
            }
        }
        continents[i].checked = checked === countries.length;
    }
};

RSFirewall.GeoIPDownload = function (element) {
    jQuery(element).attr('disabled', 'disabled');

    var GeoIPDownloadErrorHandler = function(message, url) {
        var $parent = jQuery(element).parent();

        $parent.children('.geoip-error').remove();

        $parent.append('<p class="geoip-error"><strong class="text-error">' + message + '</strong></p>').hide().fadeIn();

        if (typeof url === 'string')
        {
            $parent.append('<p class="geoip-error"><strong>' + Joomla.JText._('COM_RSFIREWALL_GEOIP_DB_TRY_TO_DOWNLOAD_MANUALLY').replace(/%s/g, url) + '</strong></p>');
        }

        $parent.append('<p class="geoip-error">' + Joomla.JText._('COM_RSFIREWALL_GEOIP_DB_CANNOT_DOWNLOAD') + '<br><input type="file" name="jform[geoip_upload][]"></p><p>' + Joomla.JText._('COM_RSFIREWALL_GEOIP_DB_CANNOT_DOWNLOAD_CONTINUED') + '</p>').hide().fadeIn();

        jQuery(element).removeAttr('disabled');
    };

    jQuery.ajax({
        type    : 'POST',
        url     : 'index.php?option=com_rsfirewall',
        dataType: 'json',
        converters: {
            "text json": RSFirewall.parseJSON
        },
        data    : {
            'task': 'configuration.downloadGeoIPDatabase',
            'license_key': document.getElementById('jform_maxmind_license_key').value
        },
        success: function (data, textStatus, jqXHR) {
            if (!data.success) {
                GeoIPDownloadErrorHandler(data.message, data.url);
            } else {
                if (!jQuery('.rsfirewall-geoip-works').length) {
                    jQuery('#country_block .com-rsfirewall-tooltip').after('<div class="alert alert-success rsfirewall-geoip-works"><p>' + data.message + '</p></div>');
                }
                jQuery(document.getElementsByName('jform[blocked_continents][]')).removeAttr('disabled');
                jQuery(document.getElementsByName('jform[blocked_countries][]')).removeAttr('disabled');
                jQuery(document.getElementsByName('jform[blocked_countries_checkall][]')).removeAttr('disabled');

                jQuery(element).parents('.alert').remove();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            GeoIPDownloadErrorHandler(Joomla.JText._('COM_RSFIREWALL_DOWNLOAD_GEOIP_SERVER_ERROR').replace('%s', errorThrown));
        }
    })
};

jQuery(document).ready(function () {
    jQuery('#jform_blocked_countries input, #jform_blocked_continents input, #jform_blocked_countries_checkall input').on('click', function (element) {
        if ((element.target.value === 'US' || element.target.value === 'checkall' || element.target.value === 'NA') && element.target.checked) {
            jQuery('#us-country-blocked').removeClass('com-rsfirewall-hidden')
        } else if ((element.target.value === 'US' || element.target.value === 'checkall' || element.target.value === 'NA') && !element.target.checked) {
            jQuery('#us-country-blocked').addClass('com-rsfirewall-hidden');
        }
    });

    RSFirewall.initCountryBlocking();

    document.getElementsByName('jform[backend_password_parameter]')[0].addEventListener('input', function(){
        this.value = this.value.replace(/\s+|\?|&/g, '_');

        document.getElementById('backend_password_placeholder_container').style.display = 'block';

        populateParameterHint();
    });

    document.getElementsByName('jform[backend_password]')[0].addEventListener('input', function(){
        document.getElementById('backend_password_placeholder_container').style.display = 'block';

        populateParameterHint();
    });

    var populateParameterHint = function() {
        let parameter = document.getElementsByName('jform[backend_password_parameter]')[0],
            password = document.getElementsByName('jform[backend_password]')[0],
            placeholder = document.getElementById('backend_password_placeholder');

        placeholder.innerText = '';

        if (parameter)
        {
            let encodedParameter = encodeURIComponent(parameter.value);
            if (encodedParameter.length > 0)
            {
                placeholder.innerText = encodedParameter + '=';
            }
        }

        let encodedPassword = encodeURIComponent(password.value);
        placeholder.innerText += encodedPassword;
    }

    populateParameterHint();

    var button;
    for (var i = 0; i < document.querySelectorAll('.com-rsfirewall-filemanager').length; i++) {
        button = document.querySelectorAll('.com-rsfirewall-filemanager')[i];

        button.addEventListener('click', function(){
            var fieldname = this.getAttribute('data-name'),
                allowfolders = this.getAttribute('data-allowfolders'),
                allowfiles = this.getAttribute('data-allowfiles'),
                url = 'index.php?option=com_rsfirewall&view=folders&tmpl=component&name=' + fieldname + '&allowfolders=' + allowfolders + '&allowfiles=' + allowfiles;

            window.open(url, 'com_rsfirewall_fileman', 'width=520, height=480,scrollbars=1');
        })
    }

    let geoipButton = document.querySelector('#com-rsfirewall-geoip-download-button');
    if (geoipButton)
    {
        geoipButton.addEventListener('click', function(e){
            RSFirewall.GeoIPDownload(this);
        });
    }
});
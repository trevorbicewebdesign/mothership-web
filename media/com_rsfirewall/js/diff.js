/**
 * Function to download the original file from the remote server
 * and overwrite the one stored locally.
 *
 * @type {{download: RSFirewall.diffs.download}}
 */
RSFirewall.diffs = {
    download: function ($local, $hid, $window) {

        if (!confirm(Joomla.JText._('COM_RSFIREWALL_CONFIRM_OVERWRITE_LOCAL_FILE'))) {
            return false;
        }

        jQuery.ajax({
            type      : 'POST',
            dataType  : 'JSON',
            url       : 'index.php?option=com_rsfirewall',
            data      : {
                task     : 'diff.download',
                localFile: $local
            },
            beforeSend: function () {
                var $buttons = [];
                var $counter = jQuery('#' + $hid, $window).find('td').last();
                var $button = $counter.find('.rsfirewall-download-original');
                var $optional = jQuery('#replace-original');

                $buttons.push($button);
                if ($optional.length) {
                    $buttons.push($optional);
                }

                jQuery.each($buttons, function () {
                    jQuery(this).attr('disabled', 'true').addClass('btn-processing');
                    jQuery(this).html('<span class="icon-refresh"></span> ' + Joomla.JText._("COM_RSFIREWALL_BUTTON_PROCESSING"));
                });
            },
            success   : function (result) {
                var $hashCount = jQuery('#hashCount', $window);
                var $parent = $hashCount.parents('.com-rsfirewall-table-row.alt-row');
                var $counter = jQuery('#' + $hid, $window).find('td').last();
                var $oldValue = parseInt(jQuery('#hashCount', $window).html());
                var $button = $counter.find('.rsfirewall-download-original');
                var $optional = jQuery('#replace-original');
                var $diffButton = jQuery('#diff' + $hid, $window);

                var $buttons = [];

                $buttons.push($button);
                if ($optional.length) {
                    $buttons.push($optional);
                }

                if (result.status == true) {
                    $diffButton.remove();
                    jQuery.each($buttons, function () {
                        jQuery(this).removeClass('btn-processing').addClass('btn-success');
                        jQuery(this).html('<span class="icon-checkmark-2"></span> ' + Joomla.JText._("COM_RSFIREWALL_BUTTON_SUCCESS"));
                    });


                    if ($oldValue == 1) {
                        $parent.find('.com-rsfirewall-not-ok').removeClass('com-rsfirewall-not-ok').addClass('com-rsfirewall-ok');
                        $parent.find('.com-rsfirewall-ok').empty().append('<span>' + Joomla.JText._('COM_RSFIREWALL_HASHES_CORRECT') + '</span>');
                    } else {
                        $hashCount.html($oldValue - 1);
                    }

                } else {
                    jQuery.each($buttons, function () {
                        jQuery(this).removeClass('btn-processing').addClass('btn-failed');
                        jQuery(this).html('<span class="icon-cancel-circle"></span> ' + Joomla.JText._("COM_RSFIREWALL_BUTTON_FAILED"));
                    });
                }

                if ($optional.length) {
                    jQuery('.rsfirewall-replace-original').append('<div class="alert alert-info">' + result.message + '</div>');
                }

                if ($counter.find('#' + $hid + '-message', $window).length) {
                    jQuery('#' + $hid + '-message', $window).remove();
                }

                $counter.append('<span id="' + $hid + '-message">' + result.message + '</span>');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    let button = document.querySelector('#replace-original');
    if (button)
    {
        button.addEventListener('click', function(){
            RSFirewall.diffs.download(this.getAttribute('data-filename'), this.getAttribute('data-hash'), window.opener.document);
        });
    }
});
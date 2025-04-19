function RSFirewallRemoveIgnoredFile($id) {
    if (!confirm(Joomla.JText._('COM_RSFIREWALL_CONFIRM_UNIGNORE'))) {
        return false;
    }
    jQuery.ajax({
        type      : 'POST',
        dataType  : 'JSON',
        url       : 'index.php?option=com_rsfirewall',
        data      : {
            task         : 'ignored.removeFromIgnored',
            ignoredFileId: $id
        },
        beforeSend: function () {
            $button = jQuery('#removeIgnored' + $id);
            $button.attr('disabled', 'true').addClass('btn-processing');
            $button.html('<span class="icon-refresh"></span> ' + Joomla.JText._("COM_RSFIREWALL_BUTTON_PROCESSING"));
        },
        success   : function (result) {
            $button = jQuery('#removeIgnored' + $id);

            if (result.status == true) {
                $button.removeClass('btn-processing').addClass('btn-success');
                $button.html('<span class="icon-checkmark-2"></span> ' + Joomla.JText._("COM_RSFIREWALL_BUTTON_SUCCESS"));
                $button.parents('tr').hide('fast');

            } else {
                $button.removeClass('btn-processing').addClass('btn-failed');
                $button.html('<span class="icon-cancel-circle"></span> ' + Joomla.JText._("COM_RSFIREWALL_BUTTON_FAILED"));
            }
        }
    })
}

document.addEventListener('DOMContentLoaded', function() {
    let buttons = document.querySelectorAll('#com-rsfirewall-ignored-table button');
    if (buttons.length > 0)
    {
        buttons.forEach(function (element){
            element.addEventListener('click', function(){
                RSFirewallRemoveIgnoredFile(this.getAttribute('data-file-id'));
            });
        });
    }
});
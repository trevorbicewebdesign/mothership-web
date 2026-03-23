RSFirewall.Database = {};
RSFirewall.Database.Check = {
    unhide     : function (item) {
        return jQuery(item).removeClass('com-rsfirewall-hidden');
    },
    ignored    : false,
    tables     : [],
    tablesNum  : 0,
    table      : '',
    content    : '',
    prefix     : '',
    startCheck : function () {
        this.table = jQuery('#' + this.prefix + '-table');
        this.content = jQuery('#' + this.prefix);
        if (!this.tables.length) {
            return false;
        }

        this.unhide(this.content);
        this.content.hide().show('fast', function () {
            RSFirewall.Database.Check.stepCheck(0);
        });
    },
    stopCheck  : function () {

    },
    setProgress: function (index) {
        var bar = document.querySelector('#' + this.prefix + '-progress .com-rsfirewall-bar');

        if (bar !== null) {
            var currentProgress = (100 / this.tablesNum) * index;

            bar.style.width = currentProgress + '%';
            bar.innerHTML = parseInt(currentProgress) + '%';
        }
    },
    stepCheck  : function (index) {
        this.setProgress(index);
        if (!this.tables || !this.tables.length) {
            this.stopCheck();
            return false;
        }

        this.unhide(this.table.find('tr')[index + 1]);

        var table = this.tables.pop();
        jQuery.ajax({
            type      : 'POST',
            url       : 'index.php?option=com_rsfirewall',
            data      : {
                task : 'dbcheck.optimize',
                table: table,
                sid  : Math.random()
            },
            beforeSend: function () {
                RSFirewall.addLoading(jQuery('#result' + index));
            },
            success   : function (data) {
                RSFirewall.removeLoading();
                jQuery('#result' + index).html(data);
                if (RSFirewall.requestTimeOut.Seconds > 0) {
                    setTimeout(function () {
                        RSFirewall.Database.Check.stepCheck(index + 1)
                    }, RSFirewall.requestTimeOut.Milliseconds());
                }
                else {
                    RSFirewall.Database.Check.stepCheck(index + 1);
                }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('com-rsfirewall-start-button').addEventListener('click', function(){
        jQuery('#com-rsfirewall-start-button').remove();
        RSFirewall.Database.Check.unhide('#com-rsfirewall-scan-in-progress').hide().fadeIn('slow');

        RSFirewall.Database.Check.prefix = 'com-rsfirewall-database';
        RSFirewall.Database.Check.tablesNum = RSFirewall.Database.Check.tables.length;

        RSFirewall.Database.Check.stopCheck = function() {
            jQuery('#com-rsfirewall-database-progress').fadeOut('fast', function(){jQuery(this).remove()});
            jQuery('#com-rsfirewall-scan-in-progress').remove();
        };

        RSFirewall.Database.Check.startCheck();
    });
});
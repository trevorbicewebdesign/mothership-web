if (typeof RSFormProDirectory != 'object') {
    var RSFormProDirectory = {};
}

if (typeof Joomla === 'object')
{
    Joomla.tableOrdering = function(order, dir, task, form) {
        if (typeof(form) === 'undefined') {
            form = document.getElementById('adminForm');
        }

        form.filter_order.value = order;
        form.filter_order_Dir.value = dir;
        form.task.value = task;
        Joomla.submitform(task, form);
    };
}

RSFormProDirectory.clearUpload = function(name, button, hash) {
    var parent = button.parentNode.parentNode;

    parent.removeChild(button.parentNode);

    var input = document.createElement('input');
    input.setAttribute('name', 'delete[' + name + '][]');
    input.setAttribute('type', 'hidden');
    input.setAttribute('value', hash);

    parent.appendChild(input);
};

RSFormProDirectory.submit = function(task) {
    var form = document.adminForm;

    if (typeof task != 'undefined') {
        form.task.value = task;
    } else {
        form.task.value = '';
    }
    form.submit();
};

RSFormProDirectory.reset = function() {
    var form = document.adminForm;
    form.filter_search.value = '';

    var filters = document.querySelectorAll('.rsfp-directory-dynamic-filters select');
    if (filters.length > 0)
    {
        for (var i = 0; i< filters.length; i++)
        {
            filters[i].selectedIndex = 0;
        }
    }

    RSFormProDirectory.submit();
};

RSFormProDirectory.downloadCSV = function() {
    var selected = false;
    var cids = document.getElementsByName('cid[]');
    for (var i=0; i<cids.length; i++) {
        if (cids[i].checked) {
            selected = true;
            break;
        }
    }

    if (!selected) {
        alert(Joomla.JText._('RSFP_SUBM_DIR_PLEASE_SELECT_AT_LEAST_ONE'));
        return;
    }

    RSFormProDirectory.submit('download');
};

RSFormProDirectory.downloadFullCSV = function(limit, total) {
    document.querySelector('.rsform-dir-progress-wrapper').style.display = 'block';
    RSFormProDirectory.csvBuffer = '';
    RSFormProDirectory.exportProcess(0, limit, total);
};

var exportProcessLastTimeout = 0;
RSFormProDirectory.csvBuffer = '';

RSFormProDirectory.exportProcess = function (start, limit, total) {
    var xml = new window.XMLHttpRequest();
    xml.onreadystatechange = function () {
        var bar = document.getElementById('dirProgressBar');
        if (xml.readyState == 4) {
            Joomla.removeMessages();
            if (xml.status === 200)
            {
                RSFormProDirectory.csvBuffer += xml.responseText;

                start = start + limit;

                var progress = Math.ceil(start * 100 / total);
                bar.style.width = progress + '%';
                bar.innerHTML = progress + '%';

                if (start >= total)
                {
                    // We're done
                    bar.style.width = bar.innerHTML = '100%';

                    // Get filename from headers
                    var disposition = xml.getResponseHeader('Content-Disposition');
                    var filename = 'directory.csv';
                    if (disposition)
                    {
                        var match = disposition.match(/filename="(.*)"/)
                        if (match.length > 0)
                        {
                            filename = match[1];
                        }
                    }

                    var blob = new Blob([RSFormProDirectory.csvBuffer],  { type: 'text/csv;charset=utf-8;' });
                    if (window.navigator.msSaveBlob) { // IE 10+
                        window.navigator.msSaveBlob(blob, filename);
                    } else {
                        var link = document.createElement("a");
                        if (link.download !== undefined) { // feature detection
                            // Browsers that support HTML5 download attribute
                            var url = window.URL.createObjectURL(blob);
                            link.setAttribute("href", url);
                            link.setAttribute("download", filename);
                            link.style.visibility = 'hidden';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);

                            window.URL.revokeObjectURL(blob);
                        }
                    }
                    return true;
                }
            }
            else
            {
                exportProcessLastTimeout += 1000;

                Joomla.renderMessages({'warning': [Joomla.JText._('COM_RSFORM_SUBMISSIONS_DIRECTORY_AN_ERROR_HAS_OCCURRED_ATTEMPTING_TO_CONTINUE_IN_A_FEW_SECONDS').replace('%d', (exportProcessLastTimeout / 1000))]});
            }

            window.setTimeout(function(start, limit, total){
                RSFormProDirectory.exportProcess(start, limit, total);
            }, exportProcessLastTimeout, start, limit, total);
        }
    };

    var params = [
        Joomla.getOptions('csrf.token') + '=1',
        'option=com_rsform',
        'controller=directory',
        'task=download',
        'ajax=1',
        'limitstart=' + start,
        'limit=' + limit,
    ];

    xml.open('POST', document.adminForm.getAttribute('action'), true);
    xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xml.send(params.join('&'));
}

// Legacy
var directorySubmit = RSFormProDirectory.submit;
var directoryReset = RSFormProDirectory.reset;
var directoryDownloadCSV = RSFormProDirectory.downloadCSV;

document.addEventListener('DOMContentLoaded', function(){
    var buttons, i;

    buttons = document.querySelectorAll('[data-directory-click]');
    if (buttons.length > 0) {
        for (i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', function(){
                var params = this.getAttribute('data-directory-params');
                params = params ? JSON.parse(params) : [];
                var self = this;
                RSFormProDirectory[this.getAttribute('data-directory-click')].apply(self, params);
            })
        }
    }

    buttons = document.querySelectorAll('[data-directory-change]');
    if (buttons.length > 0) {
        for (i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('change', function(){
                var params = this.getAttribute('data-directory-params');
                params = params ? JSON.parse(params) : [];
                var self = this;
                RSFormProDirectory[this.getAttribute('data-directory-change')].apply(self, params);
            })
        }
    }
});
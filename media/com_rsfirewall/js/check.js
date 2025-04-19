RSFirewall.System = {};
RSFirewall.Retries = 0;
RSFirewall.MaxRetries = 10;
RSFirewall.RetryTimeout = 10;
RSFirewall.System.ParseCheckCallbacks = {
    checkCoreFilesIntegrity: function(json, details, result, detailsButton) {
        var table;
        if (jQuery('#com-rsfirewall-hashes').length === 0) {
            // let's create the table
            table = jQuery('<table>', {
                'class': 'com-rsfirewall-colored-table',
                'id'   : 'com-rsfirewall-hashes'
            });
            details.append(table);
        }

        table = jQuery('#com-rsfirewall-hashes');

        if (json.data.ignored) {
            RSFirewall.System.Check.ignored = true;
        }

        if (json.data.files && json.data.files.length > 0) {
            var j = table.find('tr').length;

            for (var i = 0; i < json.data.files.length; i++) {
                var file = json.data.files[i];

                var tr = jQuery('<tr>', {
                    'class': j % 2 ? 'blue' : 'yellow',
                    'id'   : 'hash' + j

                });
                var td_checkbox = jQuery('<td>', {
                    width : '1%',
                    nowrap: 'nowrap'
                });
                var checkbox = jQuery('<input />', {
                    type   : 'checkbox',
                    checked: true,
                    name   : 'hashes[]',
                    value  : file.path,
                    id     : 'checkboxHash' + j
                });
                var hidden = jQuery('<input />', {
                    type: 'hidden',
                    name: 'hash_flags[]'
                });
                var label = jQuery('<label>', {'for': 'checkboxHash' + j}).text(file.path);
                var $hid = 'hash' + j;

                var downloadBtn = jQuery('<button type="button" class="rsfirewall-download-original com-rsfirewall-button"></button>')
                    .text(Joomla.JText._('COM_RSFIREWALL_DOWNLOAD_ORIGINAL'))
                    .data('filepath', file.path)
                    .data('hid', $hid)
                    .click(function() {
                        RSFirewall.diffs.download(jQuery(this).data('filepath'), jQuery(this).data('hid'), window.document)
                    });

                if (file.type == 'wrong') {
                    if (typeof file.time == 'string')
                    {
                        var td_type = jQuery('<td>').text(Joomla.JText._('COM_RSFIREWALL_FILE_HAS_BEEN_MODIFIED_AGO').replace('%s', file.time));
                    }
                    else
                    {
                        var td_type = jQuery('<td>').text(Joomla.JText._('COM_RSFIREWALL_FILE_HAS_BEEN_MODIFIED'));
                    }
                    var btn = jQuery('<button type="button" id="diff' + $hid + '" class="com-rsfirewall-button"></button>')
                        .attr('href', 'index.php?option=com_rsfirewall&view=diff&tmpl=component&hid=hash' + j + '&file=' + encodeURIComponent(file.path))
                        .text(Joomla.JText._('COM_RSFIREWALL_VIEW_DIFF'))
                        .click(function () {
                            window.open(jQuery(this).attr('href'), 'diffWindow', 'width=800, height=600, left=50%, location=0, menubar=0, resizable=1, scrollbars=1, toolbar=0, titlebar=1');
                        });

                    td_type.append(btn);
                    td_type.append(downloadBtn);

                } else if (file.type == 'missing') {
                    hidden.val('M');
                    var td_type = jQuery('<td>').text(Joomla.JText._('COM_RSFIREWALL_FILE_IS_MISSING'));
                    td_type.append(downloadBtn);
                }
                td_type.addClass('com-rsfirewall-default-font');

                var td_path = jQuery('<td>').append(label);

                table.append(tr.append(td_checkbox.append(checkbox, hidden), td_path, td_type));
                j++;
            }

        }

        // we haven't reached the end of the file so do another ajax call
        if (json.data.fstart) {
            var stepIndex = RSFirewall.System.Check.steps.indexOf(RSFirewall.System.Check.currentStep);

            if (RSFirewall.requestTimeOut.Seconds > 0) {
                setTimeout(function () {
                    RSFirewall.System.Check.stepCheck(stepIndex, {'fstart': json.data.fstart})
                }, RSFirewall.requestTimeOut.Milliseconds());
            }
            else {
                RSFirewall.System.Check.stepCheck(stepIndex, {'fstart': json.data.fstart});
            }
            // returning true means that this step hasn't finished and we don't need to go to the next step
            return true;
        } else {
            if (table.find('tr').length > 0) {
                var $html = Joomla.JText._('COM_RSFIREWALL_HASHES_INCORRECT').replace('%d', '<span id="hashCount">' + table.find('tr').length + '</span> ');
                result.html($html).addClass('com-rsfirewall-not-ok');
                RSFirewall.System.Check.unhide(detailsButton);
            } else {
                result.text(Joomla.JText._('COM_RSFIREWALL_HASHES_CORRECT')).addClass('com-rsfirewall-ok');
            }
            // returning false means that this step has finished and we need to go to the next step

            if (RSFirewall.System.Check.ignored) {
                jQuery('#com-rsfirewall-ignore-files-button').removeClass('com-rsfirewall-hidden');
            }
            return false;
        }
    },

    checkConfigurationIntegrity: function(json, details, result, detailsButton) {
        // let's create the table
        var table = jQuery('<table>', {'class': 'com-rsfirewall-colored-table'});

        // and populate it
        if (json.data.details) {
            for (var i = 0; i < json.data.details.length; i++) {
                var detail = json.data.details[i];
                var tr = jQuery('<tr>', {'class': i % 2 ? 'blue' : 'yellow'});
                var td_line = jQuery('<td>').html(Joomla.JText._('COM_RSFIREWALL_CONFIGURATION_LINE').replace('%d', detail.line));
                var td_code = jQuery('<td>').text(detail.code);

                table.append(tr.append(td_line, td_code));
            }
        }

        details.append(table);
    },

    checkTemporaryFiles: function(json, details, result, detailsButton) {
        if (json.data.details) {
            var p = jQuery('<p>');
            details.append(p.append(json.data.details.message));

            // let's create the table
            var table = jQuery('<table>', {'class': 'com-rsfirewall-colored-table'});
            var j = 0;
            var limit = 10;

            json.data.details.folders = jQuery.map(json.data.details.folders, function (el) {
                return el
            });
            json.data.details.files = jQuery.map(json.data.details.files, function (el) {
                return el
            });

            for (var i = 0; i < json.data.details.folders.length; i++) {
                var folder = json.data.details.folders[i];
                var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                var td = jQuery('<td>').text('[' + folder + ']');

                table.append(tr.append(td));
                j++;
                if (i >= limit) {
                    var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                    var td = jQuery('<td>').append(jQuery('<em>').text(Joomla.JText._('COM_RSFIREWALL_MORE_FOLDERS').replace('%d', (json.data.details.folders.length - limit))));
                    table.append(tr.append(td));
                    j++;
                    break;
                }
            }
            for (var i = 0; i < json.data.details.files.length; i++) {
                var file = json.data.details.files[i];
                var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                var td = jQuery('<td>').text(file);

                table.append(tr.append(td));
                j++;
                if (i >= limit) {
                    var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                    var td = jQuery('<td>').append(jQuery('<em>').text(Joomla.JText._('COM_RSFIREWALL_MORE_FILES').replace('%d', (json.data.details.files.length - limit))));
                    table.append(tr.append(td));
                    j++;
                    break;
                }
            }

            details.append(table);
        }
    },

    checkDisableFunctions: function(json, details, result, detailsButton) {
        if (json.data.details) {
            var p = jQuery('<p>');
            details.append(p.append(json.data.details));
        }
        if (RSFirewall.System.Check.table.find('.com-rsfirewall-not-ok').length > 0) {
            RSFirewall.System.Check.unhide(jQuery('#com-rsfirewall-server-configuration-fix'));
        }
    },

    checkFolderPermissions: function(json, details, result, detailsButton) {
        if (jQuery('#com-rsfirewall-folders').length == 0) {
            // let's create the table
            var table = jQuery('<table>', {
                'class': 'com-rsfirewall-colored-table',
                'id'   : 'com-rsfirewall-folders'
            });
            details.append(table);
        }

        var table = jQuery('#com-rsfirewall-folders');

        if (json.data.stop) {
            // stop scanning
            if (table.find('tr').length > 0) {
                result.text(Joomla.JText._('COM_RSFIREWALL_FOLDER_PERMISSIONS_INCORRECT').replace('%d', table.find('tr').length)).addClass('com-rsfirewall-not-ok');
                RSFirewall.System.Check.unhide(detailsButton);
            } else {
                result.text(Joomla.JText._('COM_RSFIREWALL_FOLDER_PERMISSIONS_CORRECT')).addClass('com-rsfirewall-ok');
            }

            // finished
            return false;
        } else {
            if (json.data.folders && json.data.folders.length > 0) {
                var j = table.find('tr').length;
                for (var i = 0; i < json.data.folders.length; i++) {
                    var folder = json.data.folders[i];

                    var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                    var td_checkbox = jQuery('<td>', {
                        width : '1%',
                        nowrap: 'nowrap'
                    });
                    var checkbox = jQuery('<input />', {
                        type   : 'checkbox',
                        checked: true,
                        name   : 'folders[]',
                        value  : folder.path,
                        id     : 'checkboxFolder' + j
                    });
                    var label = jQuery('<label>', {'for': 'checkboxFolder' + j}).text(folder.path);
                    var td_path = jQuery('<td>').append(label);
                    var td_perms = jQuery('<td>').text(folder.perms);

                    table.append(tr.append(td_checkbox.append(checkbox), td_path, td_perms));
                    j++;
                }
            }

            if (json.data.next_folder) {
                var stepIndex = RSFirewall.System.Check.steps.indexOf(RSFirewall.System.Check.currentStep);

                var next_folder = json.data.next_folder;
                var next_folder_stripped = json.data.next_folder_stripped;
                result.text(Joomla.JText._('COM_RSFIREWALL_PLEASE_WAIT_WHILE_BUILDING_DIRECTORY_STRUCTURE').replace('%s', next_folder_stripped));

                if (RSFirewall.requestTimeOut.Seconds > 0) {
                    setTimeout(function () {
                        RSFirewall.System.Check.stepCheck(stepIndex, {'folder': next_folder})
                    }, RSFirewall.requestTimeOut.Milliseconds());
                }
                else {
                    RSFirewall.System.Check.stepCheck(stepIndex, {'folder': next_folder});
                }

                // not finished
                return true;
            }
        }
    },

    checkFilePermissions: function(json, details, result, detailsButton) {
        if (jQuery('#com-rsfirewall-files').length == 0) {
            // let's create the table
            var table = jQuery('<table>', {
                'class': 'com-rsfirewall-colored-table',
                'id'   : 'com-rsfirewall-files'
            });
            details.append(table);
        }

        var table = jQuery('#com-rsfirewall-files');

        if (json.data.files && json.data.files.length > 0) {
            var j = table.find('tr').length;
            for (var i = 0; i < json.data.files.length; i++) {
                var file = json.data.files[i];

                var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                var td_checkbox = jQuery('<td>', {
                    width : '1%',
                    nowrap: 'nowrap'
                });
                var checkbox = jQuery('<input />', {
                    type   : 'checkbox',
                    checked: true,
                    name   : 'files[]',
                    value  : file.path,
                    id     : 'checkboxFile' + j
                });
                var label = jQuery('<label>', {'for': 'checkboxFile' + j}).text(file.path);
                var td_path = jQuery('<td>').append(label);
                var td_perms = jQuery('<td>').text(file.perms);

                table.append(tr.append(td_checkbox.append(checkbox), td_path, td_perms));
                j++;
            }
        }

        if (json.data.next_file) {
            var stepIndex = RSFirewall.System.Check.steps.indexOf(RSFirewall.System.Check.currentStep);

            var next_file = json.data.next_file;
            var next_file_stripped = json.data.next_file_stripped;
            result.text(Joomla.JText._('COM_RSFIREWALL_PLEASE_WAIT_WHILE_BUILDING_FILE_STRUCTURE').replace('%s', next_file_stripped));

            if (RSFirewall.requestTimeOut.Seconds > 0) {
                setTimeout(function () {
                    RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_file})
                }, RSFirewall.requestTimeOut.Milliseconds());
            }
            else {
                RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_file});
            }

            // not finished
            return true;
        } else {
            if (json.data.stop) {
                // stop scanning
                if (table.find('tr').length > 0) {
                    result.text(Joomla.JText._('COM_RSFIREWALL_FILE_PERMISSIONS_INCORRECT').replace('%d', table.find('tr').length)).addClass('com-rsfirewall-not-ok');
                    RSFirewall.System.Check.unhide(detailsButton);
                } else {
                    result.text(Joomla.JText._('COM_RSFIREWALL_FILE_PERMISSIONS_CORRECT')).addClass('com-rsfirewall-ok');
                }

                // finished
                return false;
            }
        }
    },

    checkSignatures:  function(json, details, result, detailsButton) {
        if (jQuery('#com-rsfirewall-signatures').length == 0) {
            // let's create the table
            var table = jQuery('<table>', {
                'class': 'com-rsfirewall-colored-table',
                'id'   : 'com-rsfirewall-signatures'
            });
            details.append(table);
        }

        var table = jQuery('#com-rsfirewall-signatures');

        if (json.data.files && json.data.files.length > 0) {
            var j = table.find('tr').length;
            for (var i = 0; i < json.data.files.length; i++) {
                var file = json.data.files[i];

                var tr = jQuery('<tr>', {'class': j % 2 ? 'blue' : 'yellow'});
                var td_checkbox = jQuery('<td>', {
                    valign: 'top',
                    width : '1%',
                    nowrap: 'nowrap'
                });
                var checkbox = jQuery('<input />', {
                    type   : 'checkbox',
                    checked: true,
                    name   : 'ignorefiles[]',
                    value  : file.path,
                    id     : 'checkboxFile' + j
                });
                var td_path = jQuery('<td valign="top" width="20%">').text(file.path);

                if (typeof file.time == 'string')
                {
                    td_path.html(td_path.html() + '<br /><small>' + Joomla.JText._('COM_RSFIREWALL_FILE_HAS_BEEN_MODIFIED_AGO').replace('%s', file.time) + '</small>');
                }

                var td_reason = jQuery('<td valign="top" width="20%">', {'nowrap': 'nowrap'}).text(file.reason);
                var td_match = jQuery('<td valign="top" width="40%">').addClass('broken-word');
                if (file.match) {
                    td_match = td_match.text(file.match.substring(0, 355));
                }

                var td_view = jQuery('<td valign="top" width="1%">').addClass('com-rsfirewall-default-font');

                var btn = jQuery('<button type="button" class="com-rsfirewall-button pull-right"></button>')
                    .attr('href', 'index.php?option=com_rsfirewall&view=file&tmpl=component&file=' + encodeURIComponent(file.path))
                    .text(Joomla.JText._('COM_RSFIREWALL_VIEW_FILE'))
                    .click(function () {
                        window.open(jQuery(this).attr('href'), 'fileWindow', 'width=800, height=600, left=50%, location=0, menubar=0, resizable=1, scrollbars=1, toolbar=0, titlebar=1');
                    });

                td_view.append(btn);

                table.append(tr.append(td_checkbox.append(checkbox), td_path, td_reason, td_match, td_view));
                j++;
            }
        }

        if (json.data.next_file) {
            var stepIndex = RSFirewall.System.Check.steps.indexOf(RSFirewall.System.Check.currentStep);

            var next_file = json.data.next_file;
            var next_file_stripped = json.data.next_file_stripped;
            result.text(Joomla.JText._('COM_RSFIREWALL_PLEASE_WAIT_WHILE_BUILDING_FILE_STRUCTURE').replace('%s', next_file_stripped));

            RSFirewall.next_file = next_file;

            if (RSFirewall.requestTimeOut.Seconds > 0) {
                setTimeout(function () {
                    RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_file})
                }, RSFirewall.requestTimeOut.Milliseconds());
            }
            else {
                RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_file});
            }

            // not finished
            return true;
        } else {
            if (json.data.stop) {
                // stop scanning
                if (table.find('tr').length > 0) {
                    result.text(Joomla.JText._('COM_RSFIREWALL_MALWARE_PLEASE_REVIEW_FILES').replace('%d', table.find('tr').length)).addClass('com-rsfirewall-not-ok');
                    RSFirewall.System.Check.unhide(detailsButton);
                } else {
                    result.text(Joomla.JText._('COM_RSFIREWALL_NO_MALWARE_FOUND')).addClass('com-rsfirewall-ok');
                }

                // finished
                return false;
            }
        }
    }
};
RSFirewall.System.Check = {
    unhide           : function (item) {
        return jQuery(item).removeClass('com-rsfirewall-hidden');
    },
    content          : null,
    table            : null,
    steps            : [],
    currentStep      : '',
    prefix           : '',
    ignored    		 : false,
    isWindows        : false,
    fix              : function (step, currentButton) {
        var parent = jQuery(currentButton).parents('td');

        var data = {
            task: 'fix.' + step,
            sid : Math.random()
        };

        var loaderWrapper;
        if (parent.find('.com-rsfirewall-loader-wrapper').length > 0) {
            loaderWrapper = parent.find('.com-rsfirewall-loader-wrapper');
        } else {
            loaderWrapper = jQuery('<span class="com-rsfirewall-loader-wrapper"></span>');
            loaderWrapper.insertAfter(currentButton);
        }

        loaderWrapper.removeClass('com-rsfirewall-ok com-rsfirewall-not-ok com-rsfirewall-error').empty();

        if (step === 'fixAdminUser') {
            data.username = jQuery('#com-rsfirewall-new-username').val();
        } else if (step === 'fixHashes') {
            data.files = [];
            jQuery('input[name="hashes[]"]:checked').each(function () {
                data.files.push(jQuery(this).val());
            });
            data.flags = [];
            jQuery('input[name="hash_flags[]"]').each(function () {
                data.flags.push(jQuery(this).val());
            });
        } else if (step === 'ignoreFiles') {
            data.files = [];
            jQuery('input[name="ignorefiles[]"]:checked').each(function () {
                data.files.push(jQuery(this).val());
            });
        } else if (step === 'fixFolderPermissions') {
            data.folders = [];
            // adjust the limit
            var limit = this.limit;
            if (jQuery('input[name="folders[]"]:checked').length < this.limit) {
                limit = jQuery('input[name="folders[]"]:checked').length;
            }
            // add the folders to the POST array
            for (var i = 0; i < limit; i++) {
                data.folders.push(jQuery(jQuery('input[name="folders[]"]:checked')[i]).val());
            }

            // how many items are left?
            loaderWrapper.text(Joomla.JText._('COM_RSFIREWALL_ITEMS_LEFT').replace('%d', jQuery('input[name="folders[]"]:checked').length));

            // stop if there are no folders to process
            if (data.folders.length == 0) {
                // show the message
                loaderWrapper.html(Joomla.JText._('COM_RSFIREWALL_FIX_FOLDER_PERMISSIONS_DONE'));
                loaderWrapper.addClass('com-rsfirewall-ok');
                jQuery(currentButton).remove();
                return;
            }
        } else if (step === 'fixFilePermissions') {
            data.files = [];
            // adjust the limit
            var limit = this.limit;
            if (jQuery('input[name="files[]"]:checked').length < this.limit) {
                limit = jQuery('input[name="files[]"]:checked').length;
            }
            // add the files to the POST array
            for (var i = 0; i < limit; i++) {
                data.files.push(jQuery(jQuery('input[name="files[]"]:checked')[i]).val());
            }

            // how many items are left?
            loaderWrapper.text(Joomla.JText._('COM_RSFIREWALL_ITEMS_LEFT').replace('%d', jQuery('input[name="files[]"]:checked').length));

            // stop if there are no files to process
            if (data.files.length == 0) {
                // show the message
                loaderWrapper.html(Joomla.JText._('COM_RSFIREWALL_FIX_FILE_PERMISSIONS_DONE'));
                loaderWrapper.addClass('com-rsfirewall-ok');
                jQuery(currentButton).remove();
                return;
            }
        }

        jQuery.ajax({
            converters: {
                "text json": RSFirewall.parseJSON
            },
            dataType  : 'json',
            type      : 'POST',
            url       : 'index.php?option=com_rsfirewall',
            data      : data,
            beforeSend: function () {
                RSFirewall.addLoading(loaderWrapper);
                jQuery(currentButton).hide();
            },
            error     : function (jqXHR, textStatus, errorThrown) {
                RSFirewall.removeLoading();
                jQuery(currentButton).show();

                loaderWrapper.addClass('com-rsfirewall-error');
                loaderWrapper.html(Joomla.JText._('COM_RSFIREWALL_ERROR_FIX') + jqXHR.status + ' ' + errorThrown);
            },
            success   : function (json) {
                RSFirewall.removeLoading();
                jQuery(currentButton).show();

                if (json.success == true) {
                    if (RSFirewall.System.Check.parseFixDetails(step, json, loaderWrapper, currentButton)) {
                        // returning true means that we need to skip what's below
                        return;
                    }

                    if (typeof json.data.result != 'undefined') {
                        if (json.data.result == true) {
                            loaderWrapper.addClass('com-rsfirewall-ok');
                            jQuery(currentButton).remove();
                        } else {
                            loaderWrapper.addClass('com-rsfirewall-not-ok');
                        }
                    }
                    if (typeof json.data.message != 'undefined') {
                        loaderWrapper.html(json.data.message);
                    }

                } else {
                    loaderWrapper.addClass('com-rsfirewall-error');
                    if (typeof json.data.message != 'undefined') {
                        loaderWrapper.html(json.data.message);
                    }
                }
            }
        });
    },
    setProgress      : function (index) {
        var bar = document.querySelector('#' + this.prefix + '-progress .com-rsfirewall-bar');

        if (bar !== null) {
            var currentProgress = (100 / this.steps.length) * index;

            bar.style.width = currentProgress + '%';
            bar.innerHTML = parseInt(currentProgress) + '%';
        }
    },
    stopCheck        : function () {
        // overwritten
    },
    stepCheck        : function (index, more_data) {
        this.setProgress(index);
        if (typeof(this.steps[index]) == 'undefined') {
            if (typeof this.stopCheck == 'function') {
                this.stopCheck();
            }
            return;
        }

        var trindex = index > 0 ? index * 2 : 0;

        var currentRow = jQuery(this.table.find('tbody tr.com-rsfirewall-table-row')[trindex]);
        var currentText = jQuery(currentRow.find('td span')[0]);
        var currentResult = jQuery(currentRow.find('td span')[1]);
        var currentDetailsRow = jQuery(this.table.find('tbody tr.com-rsfirewall-table-row')[trindex + 1]);
        var currentDetails = jQuery(currentDetailsRow.children('td')[0]);
        var currentDetailsButton = jQuery(this.table.find('.com-rsfirewall-details-button')[index]);
        var currentStep = this.steps[index];
        this.currentStep = currentStep;

        this.unhide(currentRow);

        var default_data = {
            task: 'check.' + currentStep,
            sid : Math.random()
        };
        if (more_data) {
            for (var key in more_data)
                default_data[key] = more_data[key];
        }

        jQuery.ajax({
            converters: {
                "text json": RSFirewall.parseJSON
            },
            dataType  : 'json',
            type      : 'POST',
            url       : 'index.php?option=com_rsfirewall',
            data      : default_data,
            beforeSend: function () {
                RSFirewall.addArrow(currentText);
                RSFirewall.addLoading(currentResult);
            },
            error     : function (jqXHR, textStatus, errorThrown) {
                if (currentStep === 'checkSignatures')
                {
                    // Retry after 10 seconds if the server firewall interfered
                    // Max 10 retries
                    if (RSFirewall.Retries < RSFirewall.MaxRetries)
                    {
                        RSFirewall.Retries++;
                        currentResult.html(Joomla.JText._('COM_RSFIREWALL_ERROR_CHECK_RETRYING'));
                        setTimeout(function () {
                            if (typeof RSFirewall.next_file != 'undefined')
                            {
                                RSFirewall.System.Check.stepCheck(index, {'file': RSFirewall.next_file});
                            }
                            else
                            {
                                RSFirewall.System.Check.stepCheck(index);
                            }
                        }, parseFloat(RSFirewall.RetryTimeout * 1000));

                        return;
                    }
                }
                currentResult.addClass('com-rsfirewall-error');
                currentResult.html(Joomla.JText._('COM_RSFIREWALL_ERROR_CHECK') + jqXHR.status + ' ' + errorThrown);

                RSFirewall.removeArrow();
                if (RSFirewall.requestTimeOut.Seconds > 0) {
                    setTimeout(function () {
                        RSFirewall.System.Check.stepCheck(index + 1)
                    }, RSFirewall.requestTimeOut.Milliseconds());
                }
                else {
                    RSFirewall.System.Check.stepCheck(index + 1);
                }
            },
            success   : function (json) {
                RSFirewall.removeArrow();
                if (json.success == true) {
                    if (typeof json.data.message != 'undefined') {
                        currentResult.html(json.data.message);
                    }
                    if (typeof json.data.result != 'undefined') {
                        currentResult.addClass(json.data.result == true ? 'com-rsfirewall-ok' : 'com-rsfirewall-not-ok');
                        // show the button if we need to provide details
                        if (json.data.result == false) {
                            RSFirewall.System.Check.unhide(currentDetailsButton);
                        }
                    }

                    // a little hack to stop going to the next step
                    // if this step requires extra ajax calls
                    if (RSFirewall.System.Check.parseCheckDetails(currentStep, json, currentDetails, currentResult, currentDetailsButton)) {
                        return;
                    }
                } else {
                    if (typeof json.data.message != 'undefined') {
                        if (currentStep === 'checkCoreFilesIntegrity') {
                            currentResult.addClass('com-rsfirewall-not-ok');
                        } else {
                            currentResult.addClass('com-rsfirewall-error');
                        }
                        currentResult.html(Joomla.JText._('COM_RSFIREWALL_ERROR_CHECK') + json.data.message);
                    }
                }
                if (RSFirewall.requestTimeOut.Seconds > 0) {
                    setTimeout(function () {
                        RSFirewall.System.Check.stepCheck(index + 1)
                    }, RSFirewall.requestTimeOut.Milliseconds());
                }
                else {
                    RSFirewall.System.Check.stepCheck(index + 1);
                }
            }
        });
    },
    startCheck       : function () {
        this.table = jQuery('#' + this.prefix + '-table');
        this.content = jQuery('#' + this.prefix);

        var currentTable = this.table;

        // make buttons clickable
        this.table.find('.com-rsfirewall-details-button').each(function (i, el) {
            jQuery(el).click(function () {
                var row = currentTable.find('tbody tr.com-rsfirewall-table-row')[i * 2 + 1];
                var $row = jQuery(row);
                if ($row.hasClass('com-rsfirewall-hidden')) {
                    RSFirewall.System.Check.unhide(row);
                    $row.hide();
                }
                $row.toggle();
                jQuery(this).children('span').toggleClass(function (j, theClass) {
                    jQuery(this).removeAttr('class');
                    return (theClass === 'icon-arrow-down') ? 'icon-arrow-up' : 'icon-arrow-down';
                });
            });
        });

        this.unhide(this.content);
        this.content.hide().show('fast', function () {
            RSFirewall.System.Check.stepCheck(0);
        });
    },
    /* custom details parsing rules */
    parseFixDetails  : function (step, json, wrapper, button) {
        if (step === 'fixFolderPermissions') {
            if (json.data.results) {
                for (var i = 0; i < json.data.results.length; i++) {
                    var result = jQuery('<span>', {'class': json.data.results[i] == 1 ? 'com-rsfirewall-ok' : 'com-rsfirewall-not-ok'});
                    jQuery(jQuery('input[name="folders[]"]:checked')[0]).replaceWith(result);
                }
                RSFirewall.System.Check.fix(step, button);
                return true;
            }
        } else if (step === 'fixFilePermissions') {
            if (json.data.results) {
                for (var i = 0; i < json.data.results.length; i++) {
                    var result = jQuery('<span>', {'class': json.data.results[i] == 1 ? 'com-rsfirewall-ok' : 'com-rsfirewall-not-ok'});
                    jQuery(jQuery('input[name="files[]"]:checked')[0]).replaceWith(result);
                }
                RSFirewall.System.Check.fix(step, button);
                return true;
            }
        }
    },
    parseCheckDetails: function (step, json, details, result, detailsButton) {
        if (typeof RSFirewall.System.ParseCheckCallbacks[step] === 'function') {
            return RSFirewall.System.ParseCheckCallbacks[step].call(this, json, details, result, detailsButton);
        } else {
            if (json.data.details) {
                var p = jQuery('<p>');
                details.append(p.append(json.data.details));
            }
        }
    }
};

RSFirewall.removeArrow = function () {
    jQuery('#com-rsfirewall-main-content .icon-arrow-right').remove();
};
RSFirewall.addArrow = function (item) {
    if (jQuery('#com-rsfirewall-main-content .icon-arrow-right').length === 0)
    {
        jQuery(item).prepend('<i class="icon-arrow-right"></i> ');
    }
};

RSFirewall.Grade = {
    create: function () {
        // compute the grade value
        // each failed step removes 2 from the total grade
        var totals = jQuery('.com-rsfirewall-count span.com-rsfirewall-not-ok').length;
        var filescanTotals = jQuery('#com-rsfirewall-file-scan-table span.com-rsfirewall-not-ok').length;
        var grade = 100 - ((totals - filescanTotals) * 2);
        var hasErrors = jQuery('.com-rsfirewall-error').length > 0;
        var gradeInput = jQuery('#com-rsfirewall-grade input');

        if (filescanTotals)
        {
            grade -= filescanTotals * 10;
        }

        // If errors occurred, grade is 0 and change the text.
        if (hasErrors) {
            jQuery('#com-rsfirewall-grade h2').addClass('com-rsfirewall-error').text(Joomla.JText._('COM_RSFIREWALL_GRADE_NOT_FINISHED'));
            jQuery('#com-rsfirewall-grade p').text(Joomla.JText._('COM_RSFIREWALL_GRADE_NOT_FINISHED_DESC'));

            gradeInput.remove();
        } else {
            gradeInput.val(grade);

            // green
            gradeInput.knob({
                'min'               : 0,
                'max'               : 100,
                'readOnly'          : true,
                'width'             : 90,
                'height'            : 90,
                'inputColor'        : '#000000',
                'dynamicDraw'       : true,
                'thickness'         : 0.3,
                'tickColorizeValues': true,
                'change'            : function (v) {
                    var grade = v;
                    var color;
                    if (grade <= 75) {
                        color = '#ED7A53';
                    } else if (grade <= 90) {
                        color = '#88BBC8';
                    } else if (grade <= 100) {
                        color = '#9FC569';
                    }
                    this.fgColor = color;
                }
            });

            this.save();
        }

        jQuery("#com-rsfirewall-grade").fadeIn('slow');
    },
    save  : function () {
        jQuery.ajax({
            type: 'POST',
            url : 'index.php?option=com_rsfirewall',
            data: {
                task : 'check.saveGrade',
                grade: jQuery('#com-rsfirewall-grade input').val(),
                sid  : Math.random()
            }
        });
    }
};

function RSFirewallStartCheck() {
    jQuery('#com-rsfirewall-last-run').remove();
    jQuery('#com-rsfirewall-start-button').remove();
    RSFirewall.System.Check.unhide('#com-rsfirewall-scan-in-progress').hide().fadeIn('slow');

    // Joomla! Configuration Check
    RSFirewall.System.Check.prefix = 'com-rsfirewall-joomla-configuration';

    RSFirewall.System.Check.stopCheck = function() {
        jQuery('#com-rsfirewall-joomla-configuration-progress').fadeOut('fast', function(){jQuery(this).remove()});
        RSFirewallServerCheck();
    };
    RSFirewall.System.Check.startCheck();
}

function RSFirewallServerCheck() {
    // Server Configuration Check
    RSFirewall.System.Check.prefix = 'com-rsfirewall-server-configuration';
    RSFirewall.System.Check.steps = ['checkPHPVersion', 'checkAllowURLInclude', 'checkDisableFunctions', 'checkExposePHP'];
    RSFirewall.System.Check.stopCheck = function() {
        jQuery('#com-rsfirewall-server-configuration-progress').fadeOut('fast', function(){jQuery(this).remove()});
        RSFirewallFilesCheck();
    };
    RSFirewall.System.Check.startCheck();
}

function RSFirewallFilesCheck() {
    RSFirewall.System.Check.prefix = 'com-rsfirewall-file-scan';
    RSFirewall.System.Check.steps = [];
    RSFirewall.System.Check.steps.push('checkCoreFilesIntegrity');
    if (!RSFirewall.System.Check.isWindows)
    {
        RSFirewall.System.Check.steps.push('checkFolderPermissions');
        RSFirewall.System.Check.steps.push('checkFilePermissions');
    }
    RSFirewall.System.Check.steps.push('checkSignatures');
    RSFirewall.System.Check.stopCheck = function() {
        jQuery('#com-rsfirewall-scan-in-progress').remove();

        RSFirewall.Grade.create();
        jQuery(window).scrollTop(0);
    };
    RSFirewall.System.Check.startCheck();
}

function RSFirewallChangeAdminUserButtonText() {
    var text 	= jQuery('#com-rsfirewall-new-username').val();
    var button 	= jQuery('#com-rsfirewall-rename-admin-button');

    button.text(Joomla.JText._('COM_RSFIREWALL_RENAME_ADMIN').replace('%s', text));
}

function RSFirewallFix(step, button) {
    // disable the checkboxes
    if (step === 'fixFolderPermissions') {
        jQuery('input[name="folders[]"]').prop('disabled', true);
    } else if (step === 'fixFilePermissions') {
        jQuery('input[name="files[]"]').prop('disabled', true);
    } else if (step === 'fixHashes') {
        jQuery('input[name="hashes[]"]').prop('disabled', true);
    } else if (step === 'ignoreFiles') {
        jQuery('input[name="ignorefiles[]"]').prop('disabled', true);
    }

    RSFirewall.System.Check.fix(step, button);
}

window.onbeforeunload = function() {
    try {
        if (!document.getElementById('com-rsfirewall-scan-in-progress').classList.contains('com-rsfirewall-hidden')) {
            return Joomla.JText._('COM_RSFIREWALL_SCANNING_IN_PROGRESS_LEAVE');
        }
    } catch (err) {}
}

document.addEventListener('DOMContentLoaded', function() {
    let xdebugButton = document.querySelector('#com-rsfirewall-xdebug-warning button');
    if (xdebugButton)
    {
        xdebugButton.addEventListener('click', function(){
            document.getElementById('com-rsfirewall-start-button').removeAttribute('disabled');
            document.getElementById('com-rsfirewall-xdebug-warning').style.display = 'none';
        });
    }

    let startButton = document.querySelector('#com-rsfirewall-start-button');
    if (startButton)
    {
        startButton.addEventListener('click', function(){
            RSFirewallStartCheck();
        });
    }

    let usernameButton = document.querySelector('#com-rsfirewall-new-username');
    if (usernameButton)
    {
        usernameButton.addEventListener('input', function(){
            RSFirewallChangeAdminUserButtonText();
        });
    }

    let fixButtons = document.querySelectorAll('.com-rsfirewall-fix-button');
    if (fixButtons.length > 0)
    {
        fixButtons.forEach(function (element){
            element.addEventListener('click', function(){
                RSFirewallFix(this.getAttribute('data-fix'), this);
            });
        });
    }

    let ignoredFilesButton = document.querySelector('#com-rsfirewall-ignore-files-button');
    if (ignoredFilesButton)
    {
        ignoredFilesButton.addEventListener('click', function(){
            window.open('index.php?option=com_rsfirewall&view=ignored&tmpl=component', 'ignoreWindow', 'width=800, height=600, left=50%, location=0, menubar=0, resizable=1, scrollbars=1, toolbar=0, titlebar=1')
        });
    }
});
jQuery.formTabs = {
    tabTitles: {},
    tabContents: {},

    build: function (startindex) {
        this.each(function (index, el) {
            var tid = jQuery(el).attr('id');
            jQuery.formTabs.grabElements(el,tid);
            jQuery.formTabs.makeTitlesClickable(tid);
            jQuery.formTabs.setAllContentsInactive(tid);
            jQuery.formTabs.setTitleActive(startindex,tid);
            jQuery.formTabs.setContentActive(startindex,tid);
        });
    },

    grabElements: function(el,tid) {
        var children = jQuery(el).children();
        children.each(function(index, child) {
            if (index == 0)
                jQuery.formTabs.tabTitles[tid] = jQuery(child).find('a');
            else if (index == 1)
                jQuery.formTabs.tabContents[tid] = jQuery(child).children();
        });
    },

    setAllTitlesInactive: function (tid) {
        this.tabTitles[tid].each(function(index, title) {
            jQuery(title).removeClass('active');
        });
    },

    setTitleActive: function (index,tid) {
        index = parseInt(index);
        if (tid == 'rsform_directory_tab') document.getElementById('ptab').value = index;
        jQuery(this.tabTitles[tid][index]).addClass('active');
    },

    setAllContentsInactive: function (tid) {
        this.tabContents[tid].each(function(index, content) {
            jQuery(content).addClass('rsfp-hidden');
        });
    },

    setContentActive: function (index,tid) {
        index = parseInt(index);
        jQuery(this.tabContents[tid][index]).removeClass('rsfp-hidden');
    },

    makeTitlesClickable: function (tid) {
        this.tabTitles[tid].each(function(index, title) {
            jQuery(title).click(function () {
                jQuery.formTabs.setAllTitlesInactive(tid);
                jQuery.formTabs.setTitleActive(index,tid);

                jQuery.formTabs.setAllContentsInactive(tid);
                jQuery.formTabs.setContentActive(index,tid);
            });
        });
    }
};

jQuery.fn.extend({
    formTabs: jQuery.formTabs.build
});

function tidyOrderDir() {
    stateLoading();

    var params = [];
    var orders = document.getElementsByName('dirorder[]');
    var cids = document.getElementsByName('dircid[]');
    var formId = document.getElementById('formId').value;

    for (var i = 0; i < orders.length; i++) {
        params.push('cid[' + cids[i].value + ']=' + parseInt(i + 1));
        orders[i].value = i + 1;
    }

    params.push('formId='+formId);

    var xml = buildXmlHttp();

    var url = 'index.php?option=com_rsform&task=directory.saveordering';
    xml.open("POST", url, true);

    params = params.join('&');

    //Send the proper header information along with the request
    xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xml.send(params);
    xml.onreadystatechange = function()
    {
        if (xml.readyState === 4)
        {
            dirAutogenerateLayout();
            stateDone();
        }
    }
}

function dirAutoGenerate() {
    stateLoading();

    var params  = [];
    var cids    = document.getElementsByName('dirindetails[]');
    var orders  = document.getElementsByName('dirorder[]');
    var formId  = document.getElementById('formId').value;

    for (var i = 0; i < cids.length; i++)
    {
        params.push('cid[' + cids[i].value + ']=' + (cids[i].checked ? '1' : '0'));
        params.push('order[' + cids[i].value + ']=' + parseInt(i + 1));
        orders[i].value = i + 1;
    }

    params.push('formId='+formId);

    var xml = buildXmlHttp();

    var url = 'index.php?option=com_rsform&task=directory.savedetails';
    xml.open("POST", url, true);

    params = params.join('&');

    //Send the proper header information along with the request
    xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xml.send(params);
    xml.onreadystatechange = function()
    {
        if (xml.readyState === 4)
        {
            dirAutogenerateLayout();
            stateDone();
        }
    }
}

function changeDirectoryAutoGenerateLayout(value) {
    stateLoading();
    var layoutName = document.querySelector('[name="jform[ViewLayoutName]"]:checked').value;
    var formId = document.getElementById('formId').value;
    value = parseInt(value);

    var xml = buildXmlHttp();
    xml.onreadystatechange = function () {
        if (xml.readyState === 4) {
            document.getElementById('ViewLayout').readOnly = value === 1;

            setCodeMirrorReadonly('ViewLayout', value === 1);

            Joomla.removeMessages();
            if (value === 0)
            {
                Joomla.renderMessages({'warning': [Joomla.JText._('RSFP_SUBM_DIR_AUTOGENERATE_LAYOUT_DISABLED')]});
            }

            stateDone();
        }
    };
    var data = [
        'task=directory.changeAutoGenerateLayout',
        'formId=' + formId,
        'status=' + value,
        'ViewLayoutName=' + layoutName
    ];
    xml.open('POST', 'index.php?option=com_rsform', true);
    xml.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xml.send(data.join('&'));
}

function saveDirectoryLayoutName(layoutName) {
    stateLoading();

	var formId = document.getElementById('formId').value;
    var xml = buildXmlHttp();
    var data = [
        'task=directory.savename',
        'formId=' + formId,
        'ViewLayoutName=' + layoutName
    ];
    xml.open('POST', 'index.php?option=com_rsform', true);
    xml.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xml.send(data.join('&'));
    xml.onreadystatechange = function () {
        if (xml.readyState === 4) {
			dirAutogenerateLayout();
            stateDone();
        }
    }
}

function generateDirectoryLayout(alert) {
    if (alert && !confirm(Joomla.JText._('RSFP_AUTOGENERATE_LAYOUT_WARNING_SURE'))) {
        return;
    }
	var formId = document.getElementById('formId').value;
	var layoutName = document.querySelector('[name="jform[ViewLayoutName]"]:checked').value;
    var hideEmptyValues = document.querySelector('[name="jform[HideEmptyValues]"]:checked').value;
    var showGoogleMap = document.querySelector('[name="jform[ShowGoogleMap]"]:checked').value;

    stateLoading();
    var xml = buildXmlHttp();
    xml.onreadystatechange = function () {
        if (xml.readyState === 4) {
            document.getElementById('ViewLayout').value = xml.responseText;
            if (typeof Joomla.editors.instances['ViewLayout'] != 'undefined')
            {
                Joomla.editors.instances['ViewLayout'].setValue(xml.responseText);
            }
            stateDone();
        }
    };
    var data = [
        'task=directory.generate',
        'layoutName=' + layoutName,
        'formId=' + formId,
        'hideEmptyValues=' + hideEmptyValues,
        'showGoogleMap=' + showGoogleMap
    ];
    xml.open('POST', 'index.php?option=com_rsform', true);
    xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xml.send(data.join('&'));
}

function saveDirectorySetting(settingName, settingValue) {
    stateLoading();

	var formId = document.getElementById('formId').value;
    var xml = buildXmlHttp();
    var data = [
        'task=directory.savesetting',
        'formId=' + formId,
        'settingName=' + settingName,
        'settingValue=' + settingValue
    ];
    xml.open('POST', 'index.php?option=com_rsform', true);
    xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xml.send(data.join('&'));
    xml.onreadystatechange = function () {
        if (xml.readyState === 4) {
			dirAutogenerateLayout();
            stateDone();
        }
    }
}

function dirAutogenerateLayout() {
    if (document.querySelector('[name="jform[ViewLayoutAutogenerate]"]:checked').value === '1') {
        generateDirectoryLayout(false);
    }
}

function dirSelectAll(what) {
    var $elements = jQuery(document.getElementsByName(what + '[]'));
    var $checkbox = jQuery(document.getElementById(what + 'check'));
    $elements.prop('checked', $checkbox.prop('checked'));
}

jQuery(document).ready(function($){
    $('#rsform_directory_tab').formTabs(document.getElementById('ptab').value);
    $('#dirSubmissionsTable tbody').tableDnD({
        onDragClass: 'rsform_dragged',
        onDragStop: function (table, row) {
            tidyOrderDir();
        }
    });

	toggleQuickAdd();

	if (document.querySelector('[name="jform[ViewLayoutAutogenerate]"]:checked').value === '0') {
		Joomla.renderMessages({'warning': [Joomla.JText._('RSFP_SUBM_DIR_AUTOGENERATE_LAYOUT_DISABLED')]});
	}
});
RSFirewall.vmap = {
    /**
     * Holds the LOCATION:THREATS object
     */
    data         : null,
    /**
     * The map DOM selector
     */
    selector     : null,
    /**
     * Constructor
     *
     * @param $id
     */
    init         : function ($id) {
        /**
         * Set the DOM selector
         */
        this.selector = $id;
        /**
         * Load the data using AJAX
         */
        this.loadData();
    },
    /**
     * Helper function
     *
     * Call action here:
     *  - rsfirewall\admin\controllers\logs.php
     *     - rsfirewall\admin\models\logs.php
     *
     *     returns an object ( success: bool | data: object( country code : encounters ) )
     */
    loadData     : function () {
        var self = this;
        jQuery.ajax({
            type      : 'POST',
            dataType  : 'JSON',
            url       : 'index.php?option=com_rsfirewall',
            data      : {
                task: 'logs.getStatistics'
            },
            beforeSend: self.loadPreloader(self.selector),
            success   : function (result) {
                if (result.success) {
                    jQuery('#rsf-overlay-region, #rsf-overlay-cube').fadeOut('fast', function () {
                        jQuery('#rsf-overlay-region, #rsf-overlay-cube').remove();
                    });
                    self.data = result.data;
                    self.renderMap();
                }
            }
        })
    },
    /**
     * Create a pre
     */
    loadPreloader: function (selector) {
        jQuery(selector).prepend('<div class="tpl-overlay" id="rsf-overlay-region"></div>');
        jQuery(selector).append('<div class="sk-folding-cube" id="rsf-overlay-cube"><div class="sk-cube1 sk-cube"></div><div class="sk-cube2 sk-cube"></div><div class="sk-cube4 sk-cube"></div><div class="sk-cube3 sk-cube"></div></div>');
    },
    /**
     * Render the jQuery Vector Map
     *
     * Triggered only if the AJAX request was successful
     */
    renderMap    : function () {
        var self = this;
        jQuery(this.selector).remove('.tpl-overlay, sk-folding-cube');
        /**
         * Draw the map
         */
        jQuery(this.selector).vectorMap(
            {
                map              : 'world_en',
                backgroundColor  : null,
                color            : '#ffffff',
                hoverOpacity     : 0.7,
                selectedColor    : '#666666',
                enableZoom       : true,
                values           : self.data,
                showTooltip      : true,
                scaleColors      : ['#F8C3C4', '#e8363a'],
                normalizeFunction: 'polynomial'
            }
        );
        /**
         * Initiate the tooltips
         */
        jQuery(this.selector).bind('labelShow.jqvmap',
            function (event, label, code) {
                var text = label.text();
                if (typeof self.data[code] != 'undefined') {
                    label.text(text + ' : ' + self.data[code]);
                }
            }
        );
    }
};

jQuery(document).ready(function(){
    RSFirewall.vmap.init("#com-rsfirewall-virtual-map");
});
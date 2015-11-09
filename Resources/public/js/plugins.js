var colorpicker = $('.bs-colorpicker'),
    datepicker = $('.bs-datepicker'),
    datetimepicker = $('.bs-datetimepicker');

/**
 * if elems set loop through each
 * @param obj
 * @param callback
 */
function seteach(obj, callback) {
    if (obj.length > 0 && typeof callback === 'function') {
        obj.each(function() {
            callback(this, $(this));
        });
    }
}

/**
 * Get attribute if set
 * @param obj
 * @param attr
 * @returns {*}
 */
function getAttr(obj, attr) {
    if (typeof obj.attr('data-' + attr) !== 'undefined') {
        return obj.attr('data-' + attr);
    }

    return null;
}

/**
 * Plugins
 */
$(function() {
    // tabs
    $('.nav-tabs li:first-child, .tab-content .tab-pane:first-child').addClass('active');

    // bs-colorpicker
    seteach(colorpicker, function(elem) {
        $(elem).colorpicker();
    });

    // bs-datepicker
    seteach(datepicker, function(elem) {
        $(elem).datepicker({
            format: getAttr($(elem), 'format')
        });
    });

    // bs-datetimepicker
    seteach(datetimepicker, function(elem) {
        $(elem).datetimepicker({
            calendarWeeks: true,
            showTodayButton: true,
            format: getAttr($(elem), 'format')
        });
    });
});


/**
 * Workarounds
 */



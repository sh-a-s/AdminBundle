var colorpicker = $('.bs-colorpicker'),
    datepicker = $('.bs-datepicker'),
    datetimepicker = $('.bs-datetimepicker'),
    nestedsortable = $('.nested-sortable')
;

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

    // nested-sortable
    seteach(nestedsortable, function(elem) {
        // init
        var listtype = 'ol',
            list = $(elem).find(listtype);

        list.nestedSortable({
            listType: listtype,
            forcePlaceholderSize: true,
            placeholder: 'dd-placeholder',
            handle: 'div.drag',
            items: 'li',
            toleranceElement: '> div',
            revert: 150,
            delay: 150,
            scroll: true,
            scrollSpeed: 10,
            stop: function() {
                $.ajax({
                    url: Routing.generate('api_admin_tree_save_state', {
                        bundle: $(elem).attr('data-bundle'),
                        entity: $(elem).attr('data-entity')
                    }),
                    data: {
                        array: list.nestedSortable('toArray')
                    },
                    method: 'POST',
                    type: 'json',
                    success: function(result) {
                        console.log(result);
                    },
                    error: function() {
                        console.error('error');
                    }
                });
            }
        });

        /*setTimeout(function() {
            $(elem).trigger('change');
        }, 200);*/


        // serialize and save
        /*$(elem).on('change', function() {
            //

            console.log($(elem).find('.dd-list').attr('data-class'));

            $.ajax({
                url: Routing.generate('api_admin_tree_save_state', {
                    bundle: $(elem).attr('data-bundle'),
                    entity: $(elem).attr('data-entity')
                }),
                data: {
                    array: $(elem).nestable('serialize')
                },
                method: 'POST',
                type: 'json',
                success: function(result) {
                    console.log(result);
                },
                error: function() {
                    console.error('error');
                }
            });

            console.log( $(elem).nestable('serialize') );

            $('#out').val( JSON.stringify($(elem).nestable('serialize')) );
        });*/
    });
});


/**
 * Workarounds
 */



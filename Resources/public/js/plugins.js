var colorpicker = $('.bs-colorpicker'),
    datepicker = $('.bs-datepicker'),
    datetimepicker = $('.bs-datetimepicker'),
    nestedsortable = $('.nested-sortable'),
    selectize = $('.selectize')
;

function getDescendantProp(obj, desc) {
    var arr = desc.split(".");
    while(arr.length && (obj = obj[arr.shift()]));
    return obj;
}

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

function getAttrs(obj) {
    var attrs = {};

    $.each(obj.attributes, function(key, value) {
        if (value.nodeName.match(/^data/)) {
            attrs[ value.nodeName.replace(/^data\-/, '') ] = value.value;
        }
    });

    return attrs;
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
    /*seteach(nestedsortable, function(elem) {
        // init
        var listtype = 'ol',
            list = $(elem).find(listtype),
            bundle = $(elem).attr('data-bundle'),
            entity = $(elem).attr('data-entity')
        ;

        function save_state(_list) {
            $.ajax({
                url: Routing.generate('api_admin_tree_save_state', {
                    bundle: bundle,
                    entity: entity
                }),
                data: {
                    array: _list.nestedSortable('toArray')
                },
                method: 'POST',
                type: 'json',
                success: function (result) {
                    console.log(result);
                },
                error: function () {
                    console.error('error');
                }
            });
        }

        function init_sortable(_list) {
            _list.nestedSortable({
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
                stop: function () {
                    save_state(_list);
                }
            });
        }

        init_sortable(list);
        //save_state(list);

        $('.ibox-tools a[data-action="refresh"]').on('click', function() {
            $.ajax({
                url: Routing.generate('api_admin_tree_html', {
                    bundle: bundle,
                    entity: entity
                }),
                success: function(result) {
                    $(elem).html(result);
                    init_sortable($(elem).find(listtype));
                },
                error: function() {
                    console.error('error');
                }
            });
        });



        $('.ibox-tools a[data-action="add"]').on('click', function() {
            // get active
            var active = $(elem).find(listtype).find('li.dd-active'),
                id = 0;

            if (active.length > 0) {
                //id = active.attr('data-id');
                var parent = active.parents('li.dd-item');

                if (parent.length > 0) {
                    id = parent.attr('data-id');
                }
            }

            $.ajax({
                url: Routing.generate('api_admin_tree_add', {
                    bundle: bundle,
                    entity: entity,
                    id: id
                }),
                success: function(result) {
                    if (result) {
                        $(location).attr('href', Routing.generate('admin_edit', {
                            bundle: bundle,
                            entity: entity,
                            id: result
                        }));
                    }
                },
                error: function() {
                    console.error('error');
                }
            });
        });
    });*/

    // selectize
    seteach(selectize, function(elem) {
        var attrs = getAttrs(elem),
            instance;

        console.log(attrs);

        /**
         * Ajax Data Call
         */
        $(elem).on('selectize:load', function(e, options) {
            $.ajax({
                url: Routing.generate(options.tree.ajax.route, options.tree.ajax.routeParams),
                beforeSend: function() {
                    // clear
                    instance.clearOptionGroups();
                    instance.clearOptions();
                    instance.clear();

                    // set loading
                    instance.disable();
                    //$(elem).parent().find('.selectize-input input').attr('placeholder', 'Loading...');
                },
                success: function(result) {
                    console.log('result!', result);

                    var order = 0;
                    var current = 0;

                    $.each(result, function(key, value) {
                        order++;

                        instance.registerOptionGroup({
                            $order: order,
                            id: value.id,
                            label: value.label
                        });

                        // children
                        var c_length = value['__children'].length;
                        if (c_length > 0) {

                            for (var i = 0; i < c_length; i++) {
                                var c_entry = value['__children'][i];
                                //console.log(c_entry);
                                instance.addOption({
                                    id: c_entry.id,
                                    parent_id: value.id,
                                    label: c_entry.label
                                });
                            }
                        }

                        current++;
                        if (current == result.length) {
                            var values = [];
                            if (attrs['selectize-value'].match(/\,/)) {
                                values = attrs['selectize-value'].split(',');
                            } else {
                                values = [attrs['selectize-value']];
                            }

                            $.each(values, function(k, value) {
                                instance.addItem(value, true);
                            });

                            //$(elem).parent().find('.selectize-input input').attr('placeholder', '');
                            instance.enable();
                        }
                    });
                },
                error: function(a, b, c) {
                    console.error(a);
                    console.error(b);
                    console.error(c);
                }
            });
        });


        /**
         * Initialize Event
         */
        $(elem).on('selectize:init', function(e, _options) {
            var _this = $(this),
                selectize,
                init = true,
                options = {
                    instance: null,
                    tree: {
                        options: [],
                        optgroups: [],
                        labelField: 'label',
                        valueField: 'id',
                        optgroupField: 'parent_id',
                        optgroupLabelField: 'label',
                        optgroupValueField: 'id',
                        lockOptgroupOrder: true,
                        searchField: ['label'],
                        plugins: ['optgroup_columns'],
                        openOnFocus: true,
                        onChange: function(value) {
                            console.log(value);
                        },
                        ajax: {
                            route: attrs['selectize-route'],
                            routeParams: {
                                bundle: attrs['selectize-bundle'],
                                entity: attrs['selectize-entity'],
                                format: attrs['selectize-format'],
                                type: attrs['selectize-type']
                            },
                            optGroupCheck: function(value) {
                                return value.parent_id == null;
                            }
                        }
                    },
                    basic: {
                        sortField: {
                            field: 'text',
                            direction: 'asc'
                        },
                        dropdownParent: 'body'
                    },
                    tags: {
                        delimiter: ',',
                        persist: false,
                        create: true
                    },
                    suggest: {
                        valueField: 'id',
                        labelField: attrs['selectize-searchfield'],
                        searchField: attrs['selectize-searchfield'],
                        options: [],
                        create: false,
                        render: {
                            option: function(item, escape) {
                                var element = '<div>' + item[attrs['selectize-searchfield']];

                                if (typeof attrs['selectize-hintfield'] !== 'undefined') {
                                    element += ' <span class="opacity-4">' + getDescendantProp(item, attrs['selectize-hintfield']) + '</span>';
                                }

                                element += '</div>';
                                return element;
                            }
                        },
                        load: function(query, callback) {
                            if (!query.length) return callback();
                            $.ajax({
                                url: Routing.generate(attrs['selectize-route']),
                                type: 'GET',
                                dataType: 'json',
                                contentType: 'json',
                                data: {
                                    query: query,
                                    limit: 10,
                                    attributes: attrs['selectize-searchfield']
                                },
                                error: function(a,b,c) {
                                    console.error(a);
                                    console.error(b);
                                    console.error(c);
                                    callback();
                                },
                                success: function(res) {
                                    console.info('result!!', res);
                                    callback(res.data);
                                }
                            });
                        }
                    }
                }
            ;

            var new_options = $.extend(true, {}, options, _options);
            options = new_options;

            switch(attrs.type) {
                case 'tree':
                    selectize = _this.selectize(options.tree);
                    instance = selectize[0].selectize;

                    console.log(Routing.generate(options.tree.ajax.route, options.tree.ajax.routeParams));

                    // load data
                    $(elem).trigger('selectize:load', [options]);

                    break;

                case 'tags':
                    selectize = _this.selectize(options.tags);
                    break;

                case 'suggest':
                    selectize = _this.selectize(options.suggest);

                default:
                case 'basic':
                    selectize = _this.selectize(options.basic);
                    break;
            }

            _this.addClass('form-control').css({
                position: 'absolute',
                display: 'block',
                height: '1px',
                width: '1px',
                top: 0,
                left: 0,
                border: 'none',
                backgroundColor: 'transparent'
            });
            _this.parent().css('position', 'relative');

            _this.on('focus', function() {
                console.log('focused');
            })
        });

        if ($(elem).attr('data-type') !== 'tree'
            || typeof $(elem).attr('data-selectize-init') !== 'undefined') {

            $(elem).trigger('selectize:init');
        }
    });
});


/**
 * Workarounds
 */



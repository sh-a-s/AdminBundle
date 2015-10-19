/*
 *	jQuery Plugin <valiform>
 *	Author: Dennis Senn
 *	Version: 0.8.3
 *
 *	0.8.2 	- fixed obj_value minlength problem on checkLength
 - init_auto_focus :not([type="hidden"]) added to selector
 *	0.8.1	- added support for radio-buttons
 */

if (typeof throttle !== 'undefined' && $.isFunction(throttle)) {
    function throttle(f, delay) {
        var timer = null;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = window.setTimeout(function () {
                    f.apply(context, args);
                },
                delay || 500);
        };
    }
}

if (typeof setCookie !== 'undefined' && $.isFunction(setCookie)) {
    function setCookie(cookie, value) {
        // set date
        cookie.date = new Date();
        cookie.date.setDate(cookie.date.getDate() + cookie.expire);
        cookie.utc_string = cookie.date.toUTCString();

        // set cookie
        document.cookie = cookie.name + "=" + encodeURIComponent(value) + "; expires=" + cookie.utc_string + "; path=" + cookie.path;
    }
}

(function($){
    $.fn.extend({
        valiform: function(options) {
            var options = $.extend({
                minlength: 1,
                regex: true,
                insert_message: false,
                message_prepend: '<b>Fehler:</b> ',
                default_message_regex: 'Dieses Feld enth&auml;lt ung&uuml;ltige Zeichen.',
                default_message_length: 'Geben Sie mindestens [length] Zeichen ein.',
                select_message_length: 'W&auml;hlen Sie eine Option aus der Liste.',
                message_html: '<div htmlfor="[elem_name]" class="regex_error">[message]</div>',
                regex_default: {
                    string: '^[a-zA-ZäöüÄÖÜéàèÉÈ0-9 \'"\+&\_\.\,\/:!@\\n\\r-]+$',
                    rewrite_url: '^[a-zA-Z0-9\_\-]+$',
                    string_all: '.',
                    email: '^[a-zA-Z0-9_\.\-]{2,}\@[a-zA-Z0-9_\.\-]{2,}\.[a-z]{2,4}$',
                    url: '^[a-zA-Z\_0-9]+\.[a-zA-Z\_\.0-9]{2,}\..{2,}$',
                    date: '^[0-9]+\.[0-9]+\.[0-9]{2,4}$', // format DD.MM.[YYYY|YY]
                    currency: '^([0-9]+\.[0-9]{2})|([0-9\'\.]+)$',
                    integer: '^[0-9]+',
                    phone: '^[\+ 0-9]{8,15}',
                    int_float: '^[0-9]+\.[0-9]+$'
                },
                required_mark: false,
                required_mark_html: '<span class="required_mark">*</span>',
                init_auto_focus: false,
                init_check_regex: true,
                submit_by_enter: false,
                disable_submit_init: false,
                invalid_class: 'invalid',
                bootstrap_invalid: true,
                bootstrap_invalid_class: 'has-error',
                bootstrap_valid: true,
                bootstrap_valid_class: 'has-success',
                required_class: 'required',
                valinput_class: 'valinput',
                valinput_hidden_class: 'valinput-hidden',
                save_values: false,
                respect_hidden_inputs: true, // depends on save_values
                cookie_name: 'valiform',
                cookie_options: {
                    expire: 30,
                    path: '/'
                },
                afterInit: false, // function()
                afterCheck: false, // function()
                allValid: false, // function()
                submitType: false //function()
            }, arguments[0] || {});

            return this.each(function() {
                var o = options,
                    form = $(this),
                    form_submitted = false,
                    form_fields = $(this).find('.' + o.valinput_class + ':visible:not(:disabled), .' + o.valinput_hidden_class),
                    form_fields_required = $(this).find('.' + o.required_class + '.' + o.required_class + ':visible:not(:disabled)');


                // get state
                function getState(callback) {
                    if (typeof callback !== 'function') return false;

                    var valid = 0,
                        checked = 0,
                        form_fields_required_length = form_fields_required.length;

                    // check valid
                    form_fields_required.each(function() {
                        // check length & regex
                        if ($(this).val().length >= o.minlength && checkRegex($(this))) {
                            valid++;
                        }

                        // check if finished
                        checked++;
                        if (checked == form_fields_required_length) {
                            callback({
                                all: form_fields.length,
                                required: form_fields_required_length,
                                valid: valid,
                                invalid: form_fields_required.length
                            });
                        }
                    });
                }

                // check length on elem val
                function checkLength(obj) {
                    minlength = o.minlength;
                    message_length = o.default_message_length;
                    var obj_value = false;

                    // change minlength to 1 if select
                    switch (obj.prop('tagName')) {
                        case 'SELECT':
                            minlength = 1;
                            message_length = o.select_message_length;
                            break;
                        case 'DIV':
                            // if of radio_buttons
                            if (obj.hasClass('radio-buttons')) {
                                obj_value = (obj.find('.btn.active').length > 0);

                                // if value is false, check again in 5 milliseconds because of delay
                                if (obj_value === false) {
                                    setTimeout(function() {
                                        // if .active's now ok, trigger change, to check again with valid amount
                                        if (obj.find('.btn.active').length > 0) {
                                            obj.trigger('change');
                                        }
                                    }, 5);
                                }
                            }

                            break;
                        default:
                            minlength = o.minlength;
                            message_length = o.default_message_length;
                            break;
                    }

                    // if longer than o.minlength
                    if ((obj.data('selectpicker') && !obj.val()) || (obj.val().length < minlength && obj_value === false)) {
                        // replace [length] with o.minlength value and show message
                        usr_msg = message_length.replace('[length]', minlength);
                        insertMessage(obj,usr_msg);
                        return false;
                    } else {
                        // remove message if length is ok
                        obj.closest('form').find('div[htmlfor=' + escapeChars(obj.attr('name')) + ']').remove();
                        return true;
                    }
                }

                // escape chars
                function escapeChars(string) {
                    if (string && string.match(/\[.*?\]/)) {
                        string = string.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
                    }

                    return string;
                }

                // handle invalid class
                function toggleInvalidClass(obj, valid) {
                    // remove invalid class
                    if (valid == true) {
                        // if bootstrap
                        if (o.bootstrap_invalid == true) {
                            // remove invalid class
                            obj.closest('.form-group').removeClass(o.bootstrap_invalid_class);

                            // if valid class to be added
                            if (o.bootstrap_valid) {
                                obj.closest('.form-group').addClass(o.bootstrap_valid_class);
                            }

                        // if normal
                        } else {
                            obj.removeClass(o.invalid_class);
                        }

                        // add trigger event
                        $.event.trigger({'type': 'invalid_class_removed'});
                    } else {
                        // if bootstrap
                        if (o.bootstrap_invalid == true) {
                            // add invalid class if not already set
                            if (!obj.closest('.form-group').hasClass(o.bootstrap_invalid_class)) {
                                obj.closest('.form-group').addClass(o.bootstrap_invalid_class);

                                // remove valid class if it is turned on and available
                                if (o.bootstrap_valid && obj.closest('.form-group').hasClass(o.bootstrap_valid_class)) {
                                    obj.closest('.form-group').removeClass(o.bootstrap_valid_class);
                                }
                            }
                        } else {
                            // add invalid class if not already set
                            if (!obj.hasClass(o.invalid_class)) {
                                obj.addClass(o.invalid_class);
                            }
                        }

                        // add trigger event
                        $.event.trigger({'type': 'invalid_class_added'});
                    }

                    // if afterCheck
                    if (o.afterCheck !== false && typeof o.afterCheck == 'function') {
                        getState(function(form_state) {
                            o.afterCheck.call(this, form_state);
                        });
                    }

                    // allValid
                    if (o.allValid !== false && typeof o.allValid == 'function') {
                        getState(function(form_state) {
                            if (form_state.required == form_state.valid) {
                                o.allValid.call(this, form_state);
                            }
                        });
                    }
                }

                // insert predefinded message
                function insertMessage(obj, msg) {
                    // if option:insert_message set
                    if (o.insert_message == true) {
                        // create if not exist
                        if (obj.closest('form').find('div[htmlfor=' + escapeChars(obj.attr('name')) + ']').length == 0) {
                            // get html, fill message and display
                            msg_html = o.message_html.replace('[elem_name]', obj.attr('name'));
                            message = msg_html.replace('[message]', o.message_prepend + msg);
                            obj.parent().append(message);
                        } else {
                            // just change text if already exists
                            obj.closest('form').find('[htmlfor=' + escapeChars(obj.attr('name')) + ']').html(o.message_prepend + msg);
                        }
                    }

                    return true
                }

                // check elem val on predefined regex
                function checkRegex(obj) {
                    // if length
                    if (!checkLength(obj)) return false;

                    // check if regex provided
                    if (obj.attr('data-regex')) {
                        usr_regex = o.regex_default[obj.attr('data-regex')];
                    } else if(obj.attr('data-custom-regex')) {
                        // take data-custom-regex if provided
                        usr_regex = obj.attr('data-custom-regex');
                    } else {
                        // set default regex to grant all if none provided
                        usr_regex = '^.|[\\n\\r]*$';
                    }

                    // get defined message for elem
                    if (obj.attr('regex_message')) {
                        usr_msg = obj.attr('regex_message');
                    } else {
                        // if not message defined, use default_message_regex
                        usr_msg = o.default_message_regex;
                    }

                    // set regex matching string
                    //console.log(usr_regex);
                    regex_match_string = new RegExp(usr_regex, 'g');

                    // if match, return true and remove message
                    if ($.type(obj.val()) != 'string' || // if not string, return true
                        ($.type(obj.val()) == 'string' && obj.val().match(regex_match_string))) {

                        obj.closest('form').find('div[htmlfor=' + escapeChars(obj.attr('name')) + ']').remove();
                        return true;
                    } else {
                        // insert message to show whats wrong
                        insertMessage(obj, usr_msg);
                        return false;
                    }
                }

                // check object procedure
                function checkObj(obj) {
                    length_check = checkLength(obj);

                    // if length ok
                    if (length_check == true) {
                        // if regex false, return true
                        // if regex true, check regex
                        if ((o.regex == true && checkRegex(obj) == true) || o.regex == false) {
                            toggleInvalidClass(obj, true);

                            return true;
                        }
                    }

                    toggleInvalidClass(obj, false);

                    // trigger event
                    $(document).trigger({type: 'valiform_elements_checked'});

                    return false;
                }

                // sign after input to mark as required
                if (o.required_mark == true) {
                    $(this).find('.' + o.required_class).each(function() {
                        $(this).after(o.required_mark_html);
                    });
                }

                // auto focus at initialization
                if (o.init_auto_focus == true) {
                    $(this).find('.' + o.valinput_class + ':not([type="hidden"]):first').focus();
                }

                // do regex checks on init event before submitting
                if (o.init_check_regex == true) {
                    $(this).find('.' + o.valinput_class).each(function() {
                        // on blur
                        $(this).on('blur', function() {
                            // bootstrap
                            if (o.bootstrap_invalid) {
                                if ($(this).closest('.form-group').hasClass(o.bootstrap_invalid_class)
                                    || $(this).closest('.form-group').hasClass(o.bootstrap_valid_class)
                                    || $(this).val().length >= o.minlength) {

                                    toggleInvalidClass($(this), checkRegex($(this)));
                                }
                            } else {

                                // only if val() greater than minlength
                                if ($(this).val().length >= o.minlength) {
                                    toggleInvalidClass($(this), checkRegex($(this)));
                                }
                            }
                        });
                    });
                }

                // detecting enter-press on <input> elements
                if (o.submit_by_enter == true) {
                    $(this).find('input').each(function() {
                        form = $(this);
                        $(this).focus(function() {
                            elem = $(this);
                            $(document).keypress(function(e) {
                                if(e.which == 13) {
                                    form.submit();
                                    return true;
                                }
                            });
                        });
                    });
                }

                // after init
                if (o.afterInit !== false && typeof o.afterInit == 'function') {
                    o.afterInit.call(this);
                }

                // disable submit until minlength kept
                if (o.disable_submit_init == true) {
                    var _this = $(this);

                    // find submit button
                    _this.find('[type="submit"]').prop('disabled', true);

                    // go through elements to check length
                    var count = $(this).find('.' + o.required_class + ':visible').length;

                    // check on every keyup event
                    _this.find('.' + o.required_class + ':visible').on('keyup change', function() {
                        current = 0;

                        // go through elements
                        _this.find('.' + o.required_class + ':visible').each(function() {
                            //console.log($(this).val());

                            if (checkLength($(this))) current++;
                        });

                        // if all length ok
                        if (count == current) {
                            _this.find('[type="submit"]').prop('disabled', false);
                        } else {
                            // if length check not ok
                            _this.find('[type="submit"]').prop('disabled', true);
                        }

                        //console.log(current + '/' + count);
                    });
                }

                // if save_values
                if (o.save_values) {
                    var cookie = {};

                    // if cookie set
                    if ($.cookie(o.cookie_name)) {
                        // get cookie
                        cookie = JSON.parse($.cookie(o.cookie_name));

                        // set values
                        $.each(cookie, function(attr, value) {
                            var field = form.find('.' + o.valinput_class + '[name="' + attr + '"]');

                            // if field exists
                            if (field.length > 0) {
                                switch(field.prop('tagName')) {
                                    case 'SELECT':
                                        field.find('option[value="' + value + '"]').attr('selected', 'selected');
                                        break;
                                    default:
                                        field.val(value);
                                        break;
                                }

                                // trigger blur to check
                                field.trigger('blur');
                            }
                        });
                    }

                    // set cookie name
                    o.cookie_options.name = o.cookie_name;

                    // after keyup of .valinput
                    form_fields.each(function() {
                        // keyup after 500ms
                        $(this).on('keyup change', throttle(function() {
                            // store in object
                            cookie[ $(this).attr('name') ] = $(this).val();

                            // save cookie
                            setCookie(o.cookie_options, JSON.stringify(cookie));

                            setTimeout(function() {
                                console.log( JSON.parse($.cookie(o.cookie_name)) );
                            }, 100);
                        }));
                    });

                    // if hidden inputs should be respected
                    if (o.respect_hidden_inputs) {
                        $('.' + o.valinput_hidden_class).each(function() {
                            $(this).trigger('change');
                        });
                    }
                }

                // catch submit event
                $(this).submit(function(e) {
                    var _form = $(this);

                    if (form_submitted == false) {

                        // each required (check on length/regex)
                        $(this).find('.' + o.required_class + ':visible').each(function() {
                            var _this = $(this);

                            // if selectpicker
                            if ($(this).prev().data('selectpicker')) {
                                _this = $(this).prev();
                            }

                            checkObj(_this);

                            // revalidate on every keyup/change event
                            _this.keyup(function() {
                                checkObj(_this);
                            }).change(function() {
                                checkObj(_this);
                            });
                        });

                        // focus the first occured required field
                        if (o.bootstrap_invalid == false) {
                            $(this).find('.' + o.invalid_class + ':first').focus();
                        } else {
                            $(this).find('.' + o.bootstrap_invalid_class + ':first .form-control').focus();
                        }

                        // if all required fields are valid, submit form
                        // if bootstrap
                        if ((o.bootstrap_invalid == false && $(this).find('.' + o.invalid_class + '.' + o.required_class).length == 0) ||
                            (o.bootstrap_invalid == true && $(this).find('.' + o.bootstrap_invalid_class + '.' + o.required_class).length == 0)) {

                            form_submitted = true;

                            // call the callback and apply the scope:
                            if (typeof o.submitType == 'function') {
                                var form_data = {};

                                // go through form and get values
                                _form.find('.' + o.valinput_class).each(function() {
                                    switch($(this).prop('tagName')) {
                                        case 'INPUT':
                                            if ($(this).attr('type') == 'checkbox') {
                                                var val = $(this).is(':checked') ? 1 : 0;
                                                break;
                                            }
                                        default:
                                            var val = $(this).val();
                                            break;
                                    }

                                    form_data[$(this).attr('name')] = val;
                                });

                                // call function with form_data
                                o.submitType.call(this, form_data);
                            } else {
                                // determine only once submitted
                                e.preventDefault();
                                this.submit();

                                return true;
                            }
                        }
                    }

                    return false;
                });
            });
        }
    });
})(jQuery);
jQuery(document).ready(function ($) {
    'use strict';

    //select2 dropdown
    var select2Dropdown = $('.rsssl-select2');
    if (select2Dropdown.length) {
        select2Dropdown.select2({
            //tags: true,
            width:'400px',
            placeholder: 'Select or Add',
            language: {
                noResults: function() {
                    return '<span id="rsssl-no-results-container">'+rsssl_wizard.no_results+'</span>';
                },
            },
            escapeMarkup: function(markup) {
                return markup;
            },
        });
    }

    $(document).on('click','#rsssl-no-results-container',function(){
        console.log("clicked");
        select2Dropdown.val('none');
        select2Dropdown.trigger('change');
        select2Dropdown.select2('close');
    });

    var copied_element = $('.rsssl-copied-feedback').html();
    $(document).on('click', '.rsssl-copy-content', function () {
        var type = $(this).data('item');
        var success;
        var data = $('.rsssl-'+type).text();
        var temp_element = $("<textarea>");
        $("body").append(temp_element);
        temp_element.val(data).select();
        try {
            success = document.execCommand("copy");
        } catch (e) {
            success = false;
        }
        temp_element.remove();
        if (success) {
            $('<span class="rsssl-copied-feedback-container">'+copied_element+'</span>').insertAfter($(this));
            setTimeout(function(){ $('.rsssl-copied-feedback-container').fadeOut('slow') }, 5000);
        }
    });

    function maybe_show_password_delete_questions(){
        var deletePasswordField = $('.field-group.store_credentials');
        if (deletePasswordField.length) {
            deletePasswordField.addClass('rsssl-hidden');
        }
        var passwordFields = $('.rsssl-password');
        if (deletePasswordField.length) {
            passwordFields.each(function(){
                if ( !$(this).hasClass('rsssl-hidden') ) {
                    console.log('is hidden field');
                    deletePasswordField.removeClass('rsssl-hidden');
                }
            });

        }
    }


    //remove alerts
    window.setTimeout(function () {
        $(".rsssl-hide").fadeTo(500, 0).slideUp(500, function () {
            $(this).remove();
        });
    }, 2000);

    function remove_after_change() {
        $(".rsssl-panel.rsssl-remove-after-change").fadeTo(500, 0).slideUp(500, function () {
            $(this).remove();
        });
    }

    /**
     *
     * On multiple fields, we check if all input type=text and textareas are filled
     *
     * */

    function rsssl_validate_multiple() {
        $('.multiple-field').each(function(){

            var completed=true;
            $(this).find('input[type=text]').each(function () {
                if ($(this).val()===''){
                    completed = false;
                }
            });

            $(this).find('textarea').each(function () {
                if ($(this).val()===''){
                    completed = false;
                }
            });

            var icon = $(this).closest('.rsssl-panel').find('.rsssl-multiple-field-validation i');
            if (completed){
                icon.removeClass('fa-times');
                icon.addClass('fa-check');
            } else {
                icon.addClass('fa-times');
                icon.removeClass('fa-check');
            }
        });
    }
    rsssl_validate_multiple()
    $(document).on('keyup', '.multiple-field input[type=text]', function () {
        rsssl_validate_multiple();
    });
    $(document).on('keyup', '.multiple-field textarea', function () {
        rsssl_validate_multiple();
    });


    //validation of checkboxes
    rsssl_validate_checkboxes();
    $(':checkbox').change(rsssl_validate_checkboxes);

    function rsssl_validate_checkboxes() {
        $('.rsssl-validate-multicheckbox').each(function (i) {
            var set_required = [];
            var all_unchecked = true;
            $(this).find(':checkbox').each(function (i) {

                set_required.push($(this));

                if ($(this).is(':checked')) {
                    all_unchecked = false;
                }
            });
            var container = $(this).closest('.field-group').find('.rsssl-label');
            if (all_unchecked) {
                container.removeClass('valid-multicheckbox');
                container.addClass('invalid-multicheckbox');
                $.each(set_required, function (index, item) {
                    item.prop('required', true);
                    item.addClass('is-required');
                });

            } else {
                container.removeClass('invalid-multicheckbox');
                container.addClass('valid-multicheckbox');
                $.each(set_required, function (index, item) {
                    item.prop('required', false);
                    item.removeClass('is-required');
                });
            }

        });

        //now apply the required.

        check_conditions();
    }

    $(document).on('change', 'input', function (e) {
        check_conditions();
        remove_after_change();
    });

    $(document).on('keyup', 'input', function (e) {
        check_conditions();
        remove_after_change();
    });

    $(document).on('change', 'select', function (e) {
        check_conditions();
        remove_after_change();
    });

    $(document).on('change', 'textarea', function (e) {
        check_conditions();
        remove_after_change();
    });

    $(document).on('keyup', 'textarea', function (e) {
        remove_after_change();
    });

    $(document).on('click', 'button', function (e) {
        remove_after_change();
    });

    if ($("input[name=step]").val() == 2) {
        setTimeout(function () {
            if (typeof tinymce !== 'undefined') {
                for (var i = 0; i < tinymce.editors.length; i++) {
                    tinymce.editors[i].on('NodeChange keyup', function (ed, e) {
                        remove_after_change();
                    });
                }
            }
        }, 5000);
    }


    $(document).on("rssslRenderConditions", check_conditions);

    /*conditional fields*/
    function check_conditions() {
        var value;
        var showIfConditionMet = true;

        $(".condition-check-1").each(function (e) {

            var i;
            for (i = 1; i < 4; i++) {
                var question = 'rsssl_' + $(this).data("condition-question-" + i);
                var condition_type = 'AND';

                if (question == 'rsssl_undefined') return;

                var condition_answer = $(this).data("condition-answer-" + i);

                //remove required attribute of child, and set a class.
                var input = $(this).find('input[type=checkbox]');
                if (!input.length) {
                    input = $(this).find('input');
                }
                if (!input.length) {
                    input = $(this).find('textarea');
                }
                if (!input.length) {
                    input = $(this).find('select');
                }

                if (input.length && input[0].hasAttribute('required')) {
                    input.addClass('is-required');
                }

                //cast into string
                condition_answer += "";

                if (condition_answer.indexOf('NOT ') !== -1) {
                    condition_answer = condition_answer.replace('NOT ', '');
                    showIfConditionMet = false;
                } else {
                    showIfConditionMet = true;
                }
                var condition_answers = [];
                if (condition_answer.indexOf(' OR ') !== -1) {
                    condition_answers = condition_answer.split(' OR ');
                    condition_type = 'OR';
                } else {
                    condition_answers = [condition_answer];
                }

                var container = $(this);
                var conditionMet = false;
                condition_answers.forEach(function (condition_answer) {
                    value = get_input_value(question);

                    if ($('select[name=' + question + ']').length) {
                        value = Array($('select[name=' + question + ']').val());
                    }

                    if ($("input[name='" + question + "[" + condition_answer + "]" + "']").length) {
                        if ($("input[name='" + question + "[" + condition_answer + "]" + "']").is(':checked')) {
                            conditionMet = true;
                            value = [];
                        } else {
                            conditionMet = false;
                            value = [];
                        }
                    }
                    if (showIfConditionMet) {

                        //check if the index of the value is the condition, or, if the value is the condition
                        if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer) || (condition_answer==='EMPTY' && value=='') ) {

                            container.removeClass("rsssl-hidden");
                            //remove required attribute of child, and set a class.
                            if (input.hasClass('is-required')) input.prop('required', true);
                            //prevent further checks if it's an or/and statement
                            conditionMet = true;
                        } else {
                            container.addClass("rsssl-hidden");
                            if (input.hasClass('is-required')) input.prop('required', false);
                        }
                    } else {

                        if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer) || (condition_answer==='EMPTY' && value=='') ) {
                            container.addClass("rsssl-hidden");
                            if (input.hasClass('is-required')) input.prop('required', false);
                        } else {
                            container.removeClass("rsssl-hidden");
                            if (input.hasClass('is-required')) input.prop('required', true);
                            conditionMet = true;
                        }
                    }
                });
                if (!conditionMet) {
                    break;
                }
            }
        });
        maybe_show_password_delete_questions();

    }


    /**
     get checkbox values, array proof.
     */

    function get_input_value(fieldName) {
        var input = $('input[name=' + fieldName + ']');
        if (input.attr('type') === 'text' || input.attr('type') === 'password') {
            return input.val();
        } else {
            var checked_boxes = [];
            $('input[name=' + fieldName + ']:checked').each(function () {
                checked_boxes[checked_boxes.length] = $(this).val();
            });
            return checked_boxes;
        }
    }

});
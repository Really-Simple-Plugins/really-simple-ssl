jQuery(document).ready(function ($) {
    'use strict';
    var t=1;
    var copied_element = $('.rsssl-copied-feedback').html();
    $(document).on('click', '.rsssl-copy-content', function () {
        var type = $(this).data('item');
        console.log(type);
        var element = document.getElementById('rsssl-'+type);
        console.log(element);

        var sel = window.getSelection();
        sel.removeAllRanges();
        var range = document.createRange();
        range.selectNodeContents(element);
        sel.addRange(range);
        var success;
        try {
            success = document.execCommand("copy");
        } catch (e) {
            success = false;
        }

        if (success) {
            $('<span class="rsssl-copied-feedback-container">'+copied_element+'</span>').insertAfter($(this));
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


    //select2 dropdown
    if ($('.rsssl-select2').length) {
        rssslInitSelect2()
    }

    function rssslInitSelect2() {
        $('.rsssl-select2').select2({
            tags: true,
            width:'400px',
        });

        $('.rsssl-select2-no-additions').select2({
            width:'400px',
        });
    }


    /**
     * hide and show custom url
     */
    $(document).on('change', '.rsssl-document-input', function(){
        rsssl_update_document_field();
    });

    function rsssl_update_document_field(){
        if ($('.rsssl-document-field').length){
            $('.rsssl-document-field').each(function(){
                var fieldname = $(this).data('fieldname');
                var value = $('input[name='+fieldname+']:checked').val();
                var urlField = $(this).find('.rsssl-document-custom-url');
                var pageField = $(this).find('.rsssl-document-custom-page');

                if (value==='custom'){
                    pageField.show();
                    pageField.prop('required', true);
                } else {
                    pageField.hide();
                    pageField.prop('required', false);
                }

                if (value==='url'){
                    urlField.show();
                    urlField.prop('required', true);
                } else {
                    urlField.hide();
                    urlField.prop('required', false);
                }



            });
        }
    }

    /**
     * Create missing pages
     */
    $(document).on('click', '#rsssl-create_pages', function(){
        //init loader anim
        var btn = $('#rsssl-create_pages');
        btn.attr('disabled', 'disabled');
        var oldBtnHtml = btn.html();
        btn.html('<div class="rsssl-loader "><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div><div class="rect5"></div></div>');

        //get all page titles from the page
        var pageTitles = {};
        $('.rsssl-create-page-title').each(function(){
            if (pageTitles.hasOwnProperty($(this).data('region'))){
                region = pageTitles[$(this).data('region')];
            } else {
                var region = {};
            }
            region[$(this).attr('name')] = $(this).val();
            pageTitles[$(this).data('region')] = region;
        });

        $.ajax({
            type: "POST",
            url: rsssl_wizard.admin_url,
            dataType: 'json',
            data: ({
                pages: JSON.stringify(pageTitles),
                action: 'rsssl_create_pages'
            }),
            success: function (response) {
                if (response.success) {
                    $('.rsssl-create-page-title').each(function(){
                        $(this).removeClass('rsssl-deleted-page').addClass('rsssl-valid-page');
                        $(this).parent().find('.rsssl-icon').replaceWith(response.icon);
                    });
                    btn.html(response.new_button_text);
                    btn.removeAttr('disabled');
                } else {
                    btn.html(oldBtnHtml);
                }
            }
        });
    });

});
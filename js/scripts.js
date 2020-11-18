jQuery(document).ready(function ($) {
    "use strict";

    $(document).on('click','.rsssl-slider',function () {
        rssslSaveChangesNotice($(this));
    });
    $(document).on('click','.rsssl-container .rsssl-grid-item-content input',function () {
        rssslSaveChangesNotice($(this));
    });
    $(document).on('change','.rsssl-container .rsssl-grid-item-content  input',function () {
        rssslSaveChangesNotice($(this));
    });
    $(document).on('change','.rsssl-container select',function () {
        rssslSaveChangesNotice($(this));
    });
    $('.rsssl-button-save').prop('disabled', true);

    function rssslSaveChangesNotice(obj){
        obj.closest('.rsssl-item').find('.rsssl-save-settings-feedback').fadeIn();
        obj.closest('.rsssl-item').find('.rsssl-button-save').prop('disabled', false);
    }

    // Update the count in the 'Remaining tasks' section of progress block
    function update_open_task_count() {
        $.ajax({
            type: "post",
            data: {
                'action': 'rsssl_get_updated_task_count',
                token  : rsssl.token,
            },
            url: rsssl.ajaxurl,
            success: function (data) {
                if (data !== '') {
                    // Hide completely when there are no tasks left
                    if (data === 0) {
                        $('.open-task-text').text("");
                        $('.open-task-count').text("");
                    }
                    if (data === rsssl.lowest_possible_task_count) {
                        $(".rsssl-progress-text").html(rsssl.finished_text);
                    } else  {
                        var text = rsssl.not_complete_text.replace('%s', data);
                        $(".rsssl-progress-text").html(text);
                    }

                    if (data !== 0) {
                        var current_count = $('#rsssl-remaining-tasks-label').text();
                        var updated_count = current_count.replace(/(?<=\().+?(?=\))/, data) ;
                        // Replace the count if there are open tasks left
                        $('#rsssl-remaining-tasks-label').text(updated_count);
                        $(".rsssl-progress-count").text(data);
                    }
                }
            }
        });
    }

    // Color bullet in support forum block
    $(".rsssl-support-forums a").hover(function() {
        $(this).find('.rsssl-bullet').css("background-color","#FBC43D");
    }, function() {
        $(this).find('.rsssl-bullet').css("background-color",""); //to remove property set it to ''
    });

    $(document).on('click', "#rsssl-remaining-tasks", function (e) {
        if ($('#rsssl-all-tasks').is(":checked")) {
            $('#rsssl-all-tasks').prop("checked", false);
        }
        update_task_toggle_option();
    });

    $(document).on('click', "#rsssl-all-tasks", function (e) {
        if ($('#rsssl-remaining-tasks').is(":checked")) {
            $('#rsssl-remaining-tasks').prop("checked", false);
        }
        update_task_toggle_option();
    });

   function update_task_toggle_option() {
        var allTasks;
        var remainingTasks;
       rsssl_update_toggle_style();

       if ($('#rsssl-all-tasks').is(":checked")) {
           allTasks = 'checked';
           remainingTasks = 'unchecked';
       } else {
           allTasks = 'unchecked';
           remainingTasks = 'checked';
       }

        $.ajax({
            type: "post",
            data: {
                'action': 'rsssl_update_task_toggle_option',
                'token'  : rsssl.token,
                'alltasks' : allTasks,
                'remainingtasks' : remainingTasks,
            },
            url: rsssl.ajaxurl,
            success: function () {
                location.reload();
            }
        });
    }

    rsssl_update_toggle_style();
    function rsssl_update_toggle_style(){
        var allTasks = $('#rsssl-all-tasks');
        if (allTasks.is(":checked")) {
            $(".rsssl-tasks-container.rsssl-all-tasks").addClass('active');
            $(".rsssl-tasks-container.rsssl-remaining-tasks").removeClass('active');
        } else {
            $(".rsssl-tasks-container.rsssl-all-tasks").removeClass('active');
            $(".rsssl-tasks-container.rsssl-remaining-tasks").addClass('active');
        }
    }


    $(document).on("click", ".rsssl-close-warning, .rsssl-close-warning-x",function (event) {
        var type = $(this).closest('.rsssl-dashboard-dismiss').data('dismiss_type');
        var data = {
            'action': 'rsssl_dismiss_settings_notice',
            'type' : type,
            'token'  : rsssl.token,
        };
        $.post(ajaxurl, data, function (response) {});
        $(this).closest('tr').remove();

        $.ajax({
            type: "post",
            data: {
                'action': 'rsssl_get_updated_percentage',
                token  : rsssl.token,
            },
            url: rsssl.ajaxurl,
            success: function (data) {
                if (data != '') {
                    $('.rsssl-progress-percentage').text(data + "%");
                    update_open_task_count();
                }
            }
        });
    });

});
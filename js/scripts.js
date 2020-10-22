jQuery(document).ready(function ($) {
    "use strict";

    $(document).on('click','.rsssl-slider',function () {
        rssslSaveChangesNotice($(this));
    });
    $(document).on('click','.rsssl-text-input',function () {
        rssslSaveChangesNotice($(this));
    });
    $(document).on('change','.rsssl-text-input',function () {
        rssslSaveChangesNotice($(this));
    });
    $('.rsssl-button-save').prop('disabled', true);

    function rssslSaveChangesNotice(obj){
        obj.closest('.rsssl-item').find('.rsssl-save-settings-feedback').fadeIn();
        obj.closest('.rsssl-item').find('.rsssl-button-save').prop('disabled', false);
    }

    // Re-calculate percentage on dimissing notice. Use document, function to allow AJAX call to run more than once.
    $(document).on('click','.rsssl-close-warning',function () {
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
                if (data != '') {
                    // Hide completely when there are no tasks left
                    if (data == 0) {
                        $('.open-task-text').text("");
                        $('.open-task-count').text("");
                        $(".rsssl-progress-text").text(rsssl.finished_text);
                        $(".rsssl-progress-text").append("<a href='https://really-simple-ssl.com/pro'>Really Simple SSL Pro</a>");

                    } else {
                        // Replace the count if there are open tasks left
                        $('.open-task-count').text("(" + data + ")");
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
        var remainingTasks = $('#rsssl-remaining-tasks');
        if (allTasks.is(":checked")) {
            $(".all-task-text").css({"border-bottom": "1px solid lightgrey", "color": "lightgrey"});
            $(".open-task-text ").css({"text-decoration": "none"});
        } else {
            $(".all-task-text").css({"text-decoration": "none"});
            $(".open-task-text ").css({"border-bottom": "1px solid lightgrey", "color": "lightgrey"});
        }
    }


    $(".rsssl-dashboard-dismiss").on("click", ".rsssl-close-warning, .rsssl-close-warning-x",function (event) {
        var type = $(this).closest('.rsssl-dashboard-dismiss').data('dismiss_type');
        var data = {
            'action': 'rsssl_dismiss_settings_notice',
            'type' : type,
            'token'  : rsssl.token,
        };
        $.post(ajaxurl, data, function (response) {});
        $(this).closest('tr').remove();
    });

});
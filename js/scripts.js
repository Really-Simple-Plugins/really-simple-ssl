jQuery(document).ready(function ($) {
    "use strict";

    // Copy debug log to clipboard
    $(document).on('click','#rsssl-debug-log-to-clipboard',function () {
        $.ajax({
            type: "post",
            data: {
                'action': 'rsssl_get_system_status',
                token  : rsssl.token,
            },
            url: rsssl.ajaxurl,
            success: function (data) {
                if (data != '') {
                       let copyFrom = document.createElement("textarea");
                       document.body.appendChild(copyFrom);
                       copyFrom.textContent = data;
                       copyFrom.select();
                       document.execCommand("copy");
                       copyFrom.remove();
                    $('#rsssl-feedback').text(rsssl.copied_text).fadeIn("fast");
                }
            }
        });
    });

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

});
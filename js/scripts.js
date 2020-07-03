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
                    $('.rsssl-progress-percentage').text(data + "%")
                }
            }
        });
    });

    // Color bullet in support forum block
    $(".rsssl-support-forums a").hover(function() {
        $(this).find('.rsssl-bullet').css("background-color","#FBC43D");
    }, function() {
        $(this).find('.rsssl-bullet').css("background-color",""); //to remove property set it to ''
    });

});
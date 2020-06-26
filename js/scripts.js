jQuery(document).ready(function ($) {
    "use strict";

    // Reposition deactivate and keep SSL button
    // var deactivate_keep_ssl_btn =  $(".rsssl-deactivate-keep-ssl").detach();
    // $(".rsssl-deactivate-keep-ssl-button").append(deactivate_keep_ssl_btn);

    $(".rsssl-support-forums a").hover(function() {
        $(this).find('.rsssl-bullet').css("background-color","#FBC43D");
    }, function() {
        $(this).find('.rsssl-bullet').css("background-color",""); //to remove property set it to ''
    });

    $('#rlrsssl_options').click(function() {
        console.log("Clicked switch");
        var checked = $(this).parent('#rlrsssl_options').attr('checked');
        console.log(checked);
        if (this.checked) {
            console.log("Checked");
        } else {
            console.log("Unchecked");
        }
            // $.ajax({
            //     type: "post",
            //     data: {
            //         'action': 'rsssl_save_options',
            //     },
            //     // dataType: "json",
            //     url: travlr.ajaxurl,
            //     success: function (data) {
            //         if (data != '') {
            //
            //         } else {
            //
            //         }
            //     }
            // });
    });
});
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

});
jQuery(document).ready(function ($) {
    "use strict";

    /**
     * Highlight JS
     */
    var sPageURL = window.location.href;
    var queryString = sPageURL.split('?');
    if (queryString.length === 1) return false;
    var setting_name = '';
    var rsssl_variables = queryString[1].split('&');
    for (var key in rsssl_variables) {
        if (rsssl_variables.hasOwnProperty(key)) {
            var output = rsssl_variables[key].split('=');
            if (output[0]==='highlight') {
                setting_name = output[1];
            }
        }
    }

    if(setting_name !== '' && $('#rsssl-maybe-highlight-' + setting_name).length) {
        var tr_element = $('#rsssl-maybe-highlight-' + setting_name).closest('tr');
        $([document.documentElement, document.body]).animate({
            scrollTop: tr_element.offset().top
        }, 1000);
        tr_element.addClass('rsssl-highlight');
    }

    // $(document).on('click','.rsssl-slider',function () {
    //     rssslSaveChangesNotice($(this));
    // });
    // $(document).on('click','.rsssl-container .rsssl-grid-item-content input',function () {
    //     rssslSaveChangesNotice($(this));
    // });
    // $(document).on('change','.rsssl-container .rsssl-grid-item-content  input',function () {
    //     rssslSaveChangesNotice($(this));
    // });
    // $(document).on('change','.rsssl-container select',function () {
    //     rssslSaveChangesNotice($(this));
    // });
    // $('.rsssl-button-save').prop('disabled', true);
    //
    // function rssslSaveChangesNotice(obj){
    //     obj.closest('.rsssl-item').find('.rsssl-save-settings-feedback').fadeIn();
    //     obj.closest('.rsssl-item').find('.rsssl-button-save').prop('disabled', false);
    // }

    // Color bullet in support forum block
    $(".rsssl-support-forums a").hover(function() {
        $(this).find('.rsssl-bullet').css("background-color","#FBC43D");
    }, function() {
        $(this).find('.rsssl-bullet').css("background-color",""); //to remove property set it to ''
    });




    // $(document).on("click", ".rsssl-close-warning, .rsssl-close-warning-x",function (event) {
    //     $.ajax({
    //         type: "post",
    //         data: {
    //             'type' : type,
    //             'action': 'rsssl_dismiss_settings_notice',
    //             token  : rsssl.token,
    //         },
    //         url: rsssl.ajaxurl,
    //         success: function (data) {
    //             if (data.percentage !== '') {
    //                 $('.rsssl-progress-percentage').text(data.percentage + "%");
    //                 var bar = $(".progress-bar-container .progress .bar");
    //                 bar.css("width", data.percentage + '%');
    //                 if (parseInt(data.percentage)>=80){
    //                     bar.removeClass('rsssl-orange');
    //                 } else {
    //                     bar.addClass('rsssl-orange');
    //                 }
    //             }
    //
    //             if (data.tasks !== '') {
    //                 if (data.tasks === rsssl.lowest_possible_task_count) {
    //                     $(".rsssl-progress-text").html(rsssl.finished_text);
    //                 } else  {
    //                     var text = '';
    //                     if (data.tasks === 0) {
    //                         text = rsssl.finished_text;
    //                     } else if (data.tasks === 1 ) {
    //                         text = rsssl.not_complete_text_singular.replace('%s', data.tasks);
    //                     } else {
    //                         text = rsssl.not_complete_text_plural.replace('%s', data.tasks);
    //                     }
    //                     $(".rsssl-progress-text").html(text);
    //                 }
    //
    //                 $('.rsssl_remaining_task_count').html(data.tasks);
    //                 $(".rsssl-progress-count").html(data.tasks);
    //             }
    //         }
    //     });
    // });
});
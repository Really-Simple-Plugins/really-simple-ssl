<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'wp_pre_insert_user_data', 'rsssl_pre_insert_user_data_filter', 10, 4 );
add_action( 'wp_error_added', 'rsssl_handle_wp_error', 15, 4 );

/**
 * @param $data
 * @param $update
 * @param $user_id
 * @param $userdata
 *
 * @return false|mixed
 *
 * Filter user registration
 */
function rsssl_pre_insert_user_data_filter( $data, $update, $user_id, $userdata ) {
    // If login = display_name, do not add user
    error_log( print_r($data, true ) );

    if ($data->user_login === $data->display_name) {
//        add_filter('registration_errors', 'filter_registration_errors');

        // Add notice
        error_log("Login equals display name");
        return false;
    }

    return false;
}

add_filter('registration_errors', 'filter_registration_errors');
function filter_registration_errors($error){
    error_log("Reg err cb");
    if ( $error->get_error_messages( 'empty_data' ) ) {
        $error = new WP_Error( 'empty_data', "Blelellelele. Thanks!" );
    }
    return $error;
}

function rsssl_handle_wp_error($code, $message, $data, $wp_error) {
    error_log("Error!");

//    (new WP_Error)->remove( $code );
//    return new WP_Error( 'empty_data', __('Login name equals display name.", "really-simple-ssl" ) );

//    error_log(print_r($wp_error, true));
//    error_log(print_r($code, true));
//    error_log(print_r($data, true));
//    error_log(print_r($message, true));


    if ( $code === 'empty_data' ) {
        error_log("Empty data");
        $message = 'yes';

        return $message;
        // Show different error message. But how?
//        return new WP_Error('empty_data', __('Login name equals display name.', 'really-simple-ssl'));
    }

}
<?php
defined('ABSPATH') or die();
/**
 * @param $response
 * @param $handler
 * @param WP_REST_Request $request
 * @return mixed|WP_Error
 *
 * Hook into REST API requests
 */
function authorize_rest_api_requests( $response, $handler, WP_REST_Request $request ) {
    // allowed routes, whitelist option?
//    $routes = array(
//        '/wp/v2/csp etc',
//    );

    // Check if authorization header is set
    if ( ! $request->get_header( 'authorization' ) ) {
        return new WP_Error( 'authorization', 'Unauthorized access.', array( 'status' => 401 ) );
    }

    // if ( rsssl_get_networkwide_option('rsssl_restrict_rest_api') === 'restrict-roles' ) {
    // Check for certain role and allowed route
    if ( ! in_array( 'administrator', wp_get_current_user()->roles ) ) {
        return new WP_Error( 'forbidden', 'Access forbidden.', array( 'status' => 403 ) );
    }
    // }

    // if ( rsssl_get_networkwide_option('rsssl_restrict_rest_api') === 'logged-in-users' ) {
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'forbidden', 'Access forbidden to non-logged in users.', array( 'status' => 403 ) );
    }
    // }

    // if ( rsssl_get_networkwide_option('rsssl_restrict_rest_api') === 'application-passwords' ) {
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'forbidden', 'Access forbidden to non-logged in users.', array( 'status' => 403 ) );
    }
    // }

    return $response;

}

/**
 * @return void
 * Disable REST API
 */
function rsssl_disable_rest_api() {
    add_filter('json_enabled', '__return_false');
    add_filter('json_jsonp_enabled', '__return_false');
}

add_filter( 'rest_request_before_callbacks', 'authorize_rest_api_requests', 10, 3 );
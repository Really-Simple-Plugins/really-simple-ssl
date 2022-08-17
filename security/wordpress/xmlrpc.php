<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

function rsssl_handle_xmlrpc_request() {
    rsssl_filter_xmlrpc_requests();
	rsssl_log_xmlrpc_request();
}

/**
 * @return void
 * Filter XMLRPC requests based on whitelist/blacklist
 */
function rsssl_filter_xmlrpc_requests()
{
    // if in_array($ip, $whitelist) {
     // allow
    // } else {
        // deny
    //}

    if ( is_user_logged_in() ) {
        // allow
    }

    // default deny
    // return false;
}

/**
 * @param $method
 * @return array
 * Log XMLRPC requests
 */
function rsssl_log_xmlrpc_request()
{
	$data = array(
		'type' => 'xmlrpc',
		'action' => 'xmlrpc_request',
		'referrer' => wp_get_referer(),
		'user_id' => get_current_user_id(),
	);

	rsssl_log_to_learning_mode_table( $data );
}

/**
 * @return void
 * Maybe disable XMLRPC
 */
add_filter('xmlrpc_enabled', '__return_false');
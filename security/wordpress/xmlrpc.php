<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'xmlrpc_notice', 50, 1);
}

function rsssl_handle_xmlrpc_request() {
    rsssl_filter_xmlrpc_requests();
	rsssl_log_xmlrpc_request();
}

function xmlrpc_notice( $notices ) {
    $notices['xmlrpc'] = array(
        'callback' => 'rsssl_xmlrpc_notice',
        'score' => 10,
        'output' => array(
            'xmlrpc-on' => array(
                'msg' => __("XMLRPC is enabled on your site.", "really-simple-ssl"),
                'icon' => 'warning',
                'plusone' => true,
            ),
        ),
    );

    return $notices;
}

/**
 * @return string
 * Add a notice for this integration
 */
function rsssl_xmlrpc_notice()
{
    if ( rsssl_xmlrpc_allowed() ) {
        return 'xmlrpc-on';
    }

	return false;
}

add_action('init', 'rsssl_xmlrpc_allowed');


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
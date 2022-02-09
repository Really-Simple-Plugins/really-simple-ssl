<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'xmlrpc_notice', 50, 1);
}

function rsssl_handle_xmlrpc_request() {
	error_log("Handling XMLRPC request");
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
 * Check if XML-RPC requests are allowed on this site
 * POST a request, if the request returns a 200 response code the request is allowed
 */
function rsssl_xmlrpc_allowed()
{

	error_log("test xmlrpc req");

//    if ( ! get_transient( 'rsssl_xmlrpc_allowed' ) ) {

        if ( function_exists( 'curl_init' ) ) {
            $url = site_url() . '/xmlrpc.php';

            $ch = curl_init($url);

            // XML-RPC listMethods call
            // Valid XML-RPC request
            $xmlstring = '<?xml version="1.0" encoding="utf-8"?> 
                            <methodCall>
                            <methodName>system.listMethods</methodName>
                            <params></params>
                            </methodCall>';

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // Post string
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlstring );
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds

            curl_exec($ch);

            $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response_code === 200) {
                set_transient( 'rsssl_xmlrpc_allowed', true, DAY_IN_SECONDS );
                return true;
            } else {
                set_transient( 'rsssl_xmlrpc_allowed', false, DAY_IN_SECONDS );
                return false;
            }

        }

//    } else {
//        return get_transient( 'rsssl_xmlrpc_allowed' );
//    }

    return false;

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
function maybe_disable_xmlrpc()
{
    // if rsssl_xmlrpc_disabled
    // add_filter('xmlrpc_enabled', '__return_false');
}
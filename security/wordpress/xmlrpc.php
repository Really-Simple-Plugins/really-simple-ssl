<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

// Hook into XML-RPC call
if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
    error_log("Detected XMLRPC req");
    add_action( 'xmlrpc_call', 'rsssl_handle_xmlrpc_request' );
}

add_action( 'xmlrpc_call', 'rsssl_handle_xmlrpc_request' );


// Add notice in backend
if ( is_admin() ) {
    add_filter('rsssl_notices', 'xmlrpc_notice', 50, 1);
}

if ( ! function_exists( 'rsssl_handle_xmlrpc_request' ) ) {
    function rsssl_handle_xmlrpc_request( $method ) {
        error_log("xmlrpc handler" . $method);
//        error_log(print_r($request, true));
        rsssl_log_xmlrpc_request( $method );
        rsssl_filter_xmlrpc_requests( $method );
    }
}

if ( ! function_exists( 'xmlrpc_notice' ) ) {
    function xmlrpc_notice() {
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
}

/**
 * @return string
 * Add a notice for this integration
 */
if ( ! function_exists('rsssl_xmlrpc_notice' ) ) {
    function rsssl_xmlrpc_notice()
    {
        if ( rsssl_xmlrpc_allowed() ) {
            return 'xmlrpc-on';
        }
    }
}

/**
 * Check if XML-RPC requests are allowed on this site
 * POST a request, if the request returns a 200 response code the request is allowed
 */
if ( ! function_exists('rsssl_xmlrpc_allowed' ) ) {
    function rsssl_xmlrpc_allowed()
    {

        if ( ! get_transient( 'rsssl_xmlrpc_allowed' ) ) {

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

                $response = curl_exec($ch);
                error_log(print_r($response, true));

                $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($response_code === 200) {
                    set_transient( 'rsssl_xmlrpc_allowed', true, DAY_IN_SECONDS );
                    return true;
                } else {
                    set_transient( 'rsssl_xmlrpc_allowed', false, DAY_IN_SECONDS );
                    return false;
                }

            }

        } else {
            return get_transient( 'rsssl_xmlrpc_allowed' );
        }

        return false;

    }
}

/**
 * @return void
 * Filter XMLRPC requests based on whitelist/blacklist
 */
if ( ! function_exists( 'rsssl_filter_xmlrpc_requests' ) ) {
    function rsssl_filter_xmlrpc_requests( $method )
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
}

if ( ! function_exists( 'rsssl_log_xmlrpc_request' ) ) {
    function rsssl_log_xmlrpc_request( $method )
    {

    }
}

if ( ! function_exists( 'maybe_disable_xmlrpc' ) ) {
    function maybe_disable_xmlrpc()
    {
        // if rsssl_xmlrpc_disabled
        // add_filter('xmlrpc_enabled', '__return_false');
    }
}
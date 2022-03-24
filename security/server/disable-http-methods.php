<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return void
 * Test if HTTP methods STACK and TRACE are allowed
 */
function rsssl_test_stack()
{
    if ( ! get_transient( 'rsssl_http_options_allowed' ) ) {

        if (function_exists('curl_init')) {

            $url = site_url();
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url );
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
	        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_HEADER, true);
		    curl_setopt($ch,CURLOPT_NOBODY, true);
		    curl_setopt($ch,CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds

            curl_exec($ch);
            if (curl_errno($ch)) {
	            echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);
            set_transient('rsssl_http_options_allowed', 'not-allowed', DAY_IN_SECONDS);
            exit;

        }

		set_transient('rsssl_http_options_allowed', 'allowed', DAY_IN_SECONDS);
    }
}

add_action('admin_init', 'rsssl_disable_http_methods');

/**
 * @return void
 * Disable TRACE & STACK HTTP methods
 */
function rsssl_disable_http_methods()
{
    if ( RSSSL()->rsssl_server->get_server() == 'apache' ) {

	    $htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();
	    if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {
		    $htaccess = file_get_contents($htaccess_file);
		    if ( stripos($htaccess, '^(TRACE') !== false || stripos($htaccess, '^(STACK') !== false ) {
			    update_option('rsssl_disable_http_methods', false);
			    return;
		    } else {
			    // insert into .htaccess
			    update_option('rsssl_disable_http_methods', true);
			    rsssl_wrap_headers();
		    }
	    }
	}

    if ( RSSSL()->rsssl_server->get_server() == 'nginx' ) {
		//	    add_header Allow "GET, POST, HEAD" always;
		//if ( $request_method !~ ^(GET|POST|HEAD)$ ) {
		//	    return 405;
    }

}
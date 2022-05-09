<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_action('admin_init', 'rsssl_disable_http_methods');

/**
 * @return void
 * Disable TRACE & STACK HTTP methods
 */
function rsssl_disable_http_methods()
{

	if ( ! rsssl_test_if_http_methods_allowed() ) return;

    if ( rsssl_get_server() == 'apache' ) {

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

    if ( rsssl_get_server() == 'nginx' ) {
		add_filter('rsssl_notices', 'rsssl_http_methods_nginx', 20, 5);
    }
}

/**
 * @param $notices
 *
 * @return void
 *
 * Add http methods on NGINX notice
 */
function rsssl_http_methods_nginx( $notices ) {
	$notices['user_id_one'] = array(
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("HTTP methods allowed, add the following code to your nginx.conf file to block:", "really-simple-ssl")
				. rsssl_wrap_http_methods_code_nginx() ,
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);
}

/**
 * @return string
 *
 * Wrap http methods code on NGINX
 */
function rsssl_wrap_http_methods_code_nginx() {
	$code = '<code>';
	$code .= 'add_header Allow "GET, POST, HEAD" always;' . '<br>';
	$code .= 'if ( $request_method !~ ^(GET|POST|HEAD)$ ) {' . '<br>';
	$code .= '&nbsp;&nbsp;&nbsp;&nbsp;return 405;' . '<br>';
	$code .= '}' . '<br>';
	$code .= '</code>';

	return $code;
	//if ( $request_method !~ ^(GET|POST|HEAD)$ ) {
	//	    return 405;
}
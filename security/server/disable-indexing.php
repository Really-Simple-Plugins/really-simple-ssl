<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return void
 *
 * Wrap rsssl_disable_indexing on admin_init to access rsssl admin htaccess file function
 */
function rsssl_disable_indexing_wrapper() {
	add_action('admin_init', 'rsssl_disable_indexing' );
}

/**
 * @return void
 * Disable indexing
 */
function rsssl_disable_indexing() {

	if ( ! current_user_can( 'manage_options' ) ) return;

	if ( rsssl_get_server() == 'apache' ) {
	    // Get .htaccess
	    $htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();
	    if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {
		     $htaccess = file_get_contents($htaccess_file);
			if ( stripos($htaccess, 'options -indexes') !== false ) {
				update_option('rsssl_disable_indexing', false);
				return;
			} else {
			    update_option('rsssl_disable_indexing', true);
				rsssl_wrap_headers();
			}
	    }
    }

    if ( rsssl_get_server() == 'nginx' ) {
	    add_filter('rsssl_notices', 'rsssl_indexing_nginx_notice', 50, 5);
    }
}

/**
 * @param $notices
 *
 * @return mixed
 *
 * Show notice on NGINX for disabling directory indexing
 */
function rsssl_indexing_nginx_notice( $notices ) {
	$notices['indexing-nginx'] = array(
		'callback' => '_true_',
		'score' => 5,
		'output' => array(
			'true' => array(
				'msg' => __("The code to block indexing cannot be added automatically on nginx. Add the following code to your nginx.conf server block:", "really-simple-ssl")
				         . "<br>" . rsssl_indexing_nginx_code(),
				'icon' => 'open',
				'dismissible' => true,
			),
		),
	);

	return $notices;
}

/**
 * @return string
 *
 * Return NGINX code to block indexing
 */
function rsssl_indexing_nginx_code() {
	$code = '<code>';
	$code .= 'server {' . '<br>';
	$code .= '&nbsp;&nbsp;&nbsp;&nbsp;location /{anydir} {' . '<br>';
	$code .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;autoindex off;' . '<br>';
	$code .= '&nbsp;&nbsp;&nbsp;&nbsp;}' . '<br>';
	$code .= '}';
	$code .= '</code>';

	return $code;
}
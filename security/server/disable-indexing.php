<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return bool
 * Disable indexing
 */

function rsssl_disable_indexing() {
	$success = rsssl_wrap_htaccess('Options -Indexes');
	return $success===true;
}
add_action('admin_init','rsssl_disable_indexing');

/**
 * @param $notices
 *
 * @return mixed
 *
 * Show notice on NGINX for disabling directory indexing
 */
function rsssl_indexing_nginx_notice( $notices ) {
	if ( rsssl_get_server() == 'nginx' ) {
		$notices['indexing-nginx'] = array(
			'callback' => '_true_',
			'score'    => 5,
			'output'   => array(
				'true' => array(
					'msg'         => __( "The code to block indexing cannot be added automatically on nginx. Add the following code to your nginx.conf server block:", "really-simple-ssl" )
					                 . "<br>" . rsssl_indexing_nginx_code(),
					'icon'        => 'open',
					'dismissible' => true,
				),
			),
		);
	}

	return $notices;
}
add_filter('rsssl_notices', 'rsssl_indexing_nginx_notice');

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
<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Disable indexing
 * @param array $rules
 * @return []
 */

function rsssl_disable_indexing_rules( $rules ) {
	$rules[] = ['rules' => "\n" . 'Options -Indexes', 'identifier' => 'Options -Indexes'];
	return $rules;
}
add_filter('rsssl_htaccess_security_rules', 'rsssl_disable_indexing_rules');
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
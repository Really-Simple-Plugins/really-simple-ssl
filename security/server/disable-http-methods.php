<?php defined( 'ABSPATH' ) or die();

/**
 * Disable TRACE & STACK HTTP methods
 *
 * @return string
 *
 */
function rsssl_disable_http_methods_rules( $rules )
{
	$rules .= "\n" . "<LimitExcept GET POST" . ">" . "\n" ."deny from all" . "\n" . "</LimitExcept>";
	return $rules;
}
add_filter('rsssl_htaccess_security_rules', 'rsssl_disable_http_methods_rules' );

/**
 * @param $notices
 *
 * @return void
 *
 * Add http methods on NGINX notice
 */
function rsssl_http_methods_nginx( $notices ) {
	if ( rsssl_get_server() == 'nginx' ) {
		$notices['http_methods_nginx'] = array(
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
	return $notices;
}
add_filter('rsssl_notices', 'rsssl_http_methods_nginx');

/**
 * @return string
 *
 * Wrap http methods code on NGINX
 */
function rsssl_wrap_http_methods_code_nginx() {
	$code = '<code>';
	$code .= 'limit_except GET POST {' . '<br>';
	$code .= '&nbsp;&nbsp;&nbsp;&nbsp;deny all;' . '<br>';
	$code .= '}' . '<br>';
	$code .= '</code>';

	return $code;
}
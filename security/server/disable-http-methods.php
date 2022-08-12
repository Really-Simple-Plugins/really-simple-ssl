<?php defined( 'ABSPATH' ) or die();

/**
 * Disable TRACE & STACK HTTP methods
 * @param array $rules
 *
 * @return []
 *
 */
function rsssl_disable_http_methods_rules( $rules )
{
	$rule = '';
	if ( rsssl_get_server() === 'apache') {
		$rule = "\n" . addslashes("\n" . "<LimitExcept GET POST OPTIONS>" . "\n" . "deny from all" . "\n" . "</LimitExcept>");
	}

	if ( rsssl_get_server() === 'litespeed') {
		$rule = "\n" . "RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK|PUT|PATCH|DELETE|COPY|HEAD|LINK|UNLINK|PURGE|LOCK|UNLOCK|PROPFIND|VIEW)";
		$rule .= "\n" . "RewriteRule .* - [F]" . "\n";
	}

	$rules[] = ['rules' => $rule, 'identifier' => 'TRACE|STACK'];

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
	$code .= 'limit_except GET POST OPTIONS {' . '<br>';
	$code .= '&nbsp;&nbsp;&nbsp;&nbsp;deny all;' . '<br>';
	$code .= '}' . '<br>';
	$code .= '</code>';

	return $code;
}

//if ($_SERVER['REQUEST_METHOD'] === 'GET') {
//	header('Method Not Allowed', true, 405);
//	echo "GET method requests are not accepted for this resource";
//	exit;
//}

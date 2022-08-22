<?php defined( 'ABSPATH' ) or die();

function rsssl_add_http_method_rules($rules){
	$rules .= "\n";
	$rules .= "//disable http methods\n";
	$rules .= '$is_rest_request = isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"], "wp-json/")!==false && isset($_SERVER["HTTP_X_WP_NONCE"]);'."\n";
	$rules .= '$is_rest_request = $is_rest_request || isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"], "admin-ajax.php")!==false;'."\n";
	$rules .= 'if ( !$is_rest_request ) {'."\n";
	$rules .= '	$current_method = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"]: false;'."\n";
	$rules .= '	if( !in_array($current_method, ["GET", "POST"]) ){'."\n";
	$rules .= '		header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);'."\n";
	$rules .= '		exit;'."\n";
	$rules .= '	}'."\n";
	$rules .= '}'."\n\n";

	return $rules;
}
add_filter('rsssl_firewall_rules', 'rsssl_add_http_method_rules');

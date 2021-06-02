<?php
# No need for the template engine
define( 'WP_USE_THEMES', false );

#find the base path
define( 'BASE_PATH', rsssl_find_wordpress_base_path()."/" );

# Load WordPress Core
require_once( BASE_PATH.'wp-load.php' );
require_once( BASE_PATH.'wp-includes/class-phpass.php' );
require_once( BASE_PATH . 'wp-admin/includes/image.php' );

if ( !current_user_can('manage_options') ) {
	die();
}
if ( !isset($_GET["type"]) ) {
	die();
}

if (!isset($_GET['token'])) {
	die();
}

if (!wp_verify_nonce($_GET['token'], 'rsssl_download_cert')){
	die();
}

$type = sanitize_title($_GET['type']);
switch($type) {
	case 'certificate':
		$file = get_option('rsssl_certificate_path');
		$file_name = 'certificate.cert';
		break;
	case 'private_key':
		$file = get_option('rsssl_private_key_path');
		$file_name = 'private.pem';
		break;
	case 'intermediate':
		$file = get_option('rsssl_intermediate_path');
		$file_name = 'intermediate.pem';
		break;
	default:
		$file = false;
}

if (!file_exists($file)) {
	echo __("File missing. Please retry the previous steps.", "really-simple-ssl");
	die();
} else {
	$content = file_get_contents($file);
}

$fp = fopen($file, 'rb');
if ($fp) {
	if (function_exists('mb_strlen')) {
		$fsize = mb_strlen($content, '8bit');
	} else {
		$fsize = strlen($content);
	}
	$path_parts = pathinfo($file);

	header("Content-type: text/plain");
	header("Content-Disposition: attachment; filename=\"".$file_name."\"");
	header("Content-length: $fsize");
	header("Cache-Control: private",false); // required for certain browsers
	header("Pragma: public"); // required
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Transfer-Encoding: binary");
	echo $content;
} else {
	echo "Someting went wrong #2";
}
fclose($fp);


function rsssl_find_wordpress_base_path() {
	$dir = dirname(__FILE__);
	do {
		if( file_exists($dir."/wp-config.php") ) {
			if (file_exists($dir."/current")){
				return $dir.'/current';
			} else {
				return $dir;
			}
		}
	} while( $dir = realpath("$dir/..") );
	return null;
}

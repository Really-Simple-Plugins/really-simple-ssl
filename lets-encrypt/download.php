<?php
# No need for the template engine
define( 'WP_USE_THEMES', false );

#find the base path
define( 'BASE_PATH', rsssl_find_wordpress_base_path()."/" );
# Load WordPress Core
if ( !file_exists(BASE_PATH . 'wp-load.php') ) {
	die("WordPress not installed here");
}
require_once( BASE_PATH.'wp-load.php' );
require_once( ABSPATH.'wp-includes/class-phpass.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

if ( !rsssl_user_can_manage() ) {
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
	$content = __("File missing. Please retry the previous steps.", "really-simple-ssl");
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
	echo "Something went wrong #2";
}
fclose($fp);


function rsssl_find_wordpress_base_path()
{
	$path = dirname(__FILE__);

	do {
		if (file_exists($path . "/wp-config.php")) {
			//check if the wp-load.php file exists here. If not, we assume it's in a subdir.
			if ( file_exists( $path . '/wp-load.php') ) {
				return $path;
			} else {
				//wp not in this directory. Look in each folder to see if it's there.
				if ( file_exists( $path ) && $handle = opendir( $path ) ) {
					while ( false !== ( $file = readdir( $handle ) ) ) {
						if ( $file != "." && $file != ".." ) {
							$file = $path .'/' . $file;
							if ( is_dir( $file ) && file_exists( $file . '/wp-load.php') ) {
								$path = $file;
								break;
							}
						}
					}
					closedir( $handle );
				}
			}

			return $path;
		}
	} while ($path = realpath("$path/.."));

	return false;
}

<?php
# No need for the template engine
define( 'WP_USE_THEMES', false );

#find the base path
define( 'BASE_PATH', find_wordpress_base_path()."/" );

# Load WordPress Core
require_once( BASE_PATH . 'wp-load.php' );
require_once( BASE_PATH . 'wp-includes/class-phpass.php' );
require_once( BASE_PATH . 'wp-admin/includes/image.php' );
require_once( BASE_PATH . 'wp-admin/includes/plugin.php');

#Load plugin functionality
require_once( dirname( __FILE__ ) .  '/class-certificate.php' );
require_once( dirname( __FILE__ ) .  '/class-server.php' );
require_once( dirname( __FILE__ ) .  '/class-admin.php' );

$really_simple_ssl = new rsssl_admin();
$server = new rsssl_server();
$certificate = new rsssl_certificate();
if (is_multisite()) {
	require_once( dirname( __FILE__ ) .  '/class-multisite.php' );
	$rsssl_multisite = new rsssl_multisite();
}

if ( current_user_can( 'manage_options' ) ) {

	ob_start();

	if (defined('RSSSL_SAFE_MODE') && RSSSL_SAFE_MODE) echo "SAFE MODE\n";

	echo "General\n";
	echo "Plugin version: " . rsssl_version ."\n";

	if ($certificate->is_valid()) {
		echo "SSL certificate is valid\n";
	} else {
		echo "Invalid SSL certificate\n";
	}
	echo ($really_simple_ssl->ssl_enabled) ? "SSL is enabled\n\n" : "SSL is not yet enabled\n\n";

	echo "Options\n";
	if ($really_simple_ssl->autoreplace_insecure_links) echo "* Mixed content fixer\n";
	if ($really_simple_ssl->wp_redirect) echo "* WordPress redirect\n";
	if ($really_simple_ssl->htaccess_redirect) echo "* htaccess redirect\n";
	if ($really_simple_ssl->do_not_edit_htaccess) echo "* Stop editing the .htaccess file\n";
	if ($really_simple_ssl->switch_mixed_content_fixer_hook) echo "* Use alternative method to fix mixed content\n";
	if ($really_simple_ssl->dismiss_all_notices) echo "* Dismiss all Really Simple SSL notices\n";
	echo "\n";

	echo "Server information\n";
	echo "Server: " . $server->get_server() . "\n";
	echo "SSL Type: $really_simple_ssl->ssl_type\n";
	if (defined('rsssl_pro_path')) {
	    echo "TLS Version: " . RSSSL_PRO()->rsssl_premium_options->get_tls_version() . "\n";
    }
	if (is_multisite()) {
		echo "MULTISITE\n";
		echo (!RSSSL()->rsssl_multisite->ssl_enabled_networkwide) ? "SSL is being activated per site\n" : "SSL is activated network wide\n";
	}

	echo $really_simple_ssl->debug_log;

	echo "\n\nConstants\n";

	if (defined('RSSSL_FORCE_ACTIVATE')) echo "RSSSL_FORCE_ACTIVATE defined\n";
	if (defined('RSSSL_NO_FLUSH')) echo "RSSSL_NO_FLUSH defined";
	if (defined('RSSSL_DISMISS_ACTIVATE_SSL_NOTICE')) echo "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE defined\n";
	if (defined('RLRSSSL_DO_NOT_EDIT_HTACCESS')) echo "RLRSSSL_DO_NOT_EDIT_HTACCESS defined\n";
	if (defined('RSSSL_SAFE_MODE')) echo "RSSSL_SAFE_MODE defined\n";
	if (defined("RSSSL_SERVER_OVERRIDE")) echo "RSSSL_SERVER_OVERRIDE defined\n";

	if(    !defined('RSSSL_FORCE_ACTIVATE')
	       && !defined('RSSSL_NO_FLUSH')
	       && !defined('RSSSL_DISMISS_ACTIVATE_SSL_NOTICE')
	       && !defined('RLRSSSL_DO_NOT_EDIT_HTACCESS')
	       && !defined('RSSSL_SAFE_MODE')
	       && !defined("RSSSL_SERVER_OVERRIDE")
	) echo "No constants defined\n";

	$content = ob_get_clean();


	if ( function_exists( 'mb_strlen' ) ) {
		$fsize = mb_strlen( $content, '8bit' );
	} else {
		$fsize = strlen( $content );
	}
	$file_name = 'really-simple-ssl-system-status.txt';
	header( "Content-type: application/octet-stream" );

	//direct downloaden
	header( "Content-Disposition: attachment; filename=\"" . $file_name
	        . "\"" );

	//open in browser
	//header("Content-Disposition: inline; filename=\"".$file_name."\"");
	header( "Content-length: $fsize" );
	header( "Cache-Control: private",
		false ); // required for certain browsers

	header( "Pragma: public" ); // required
	header( "Expires: 0" );
	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header( "Content-Transfer-Encoding: binary" );

	echo $content;

} else {
	echo 'Permissions error, access denied';
}

function find_wordpress_base_path() {
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

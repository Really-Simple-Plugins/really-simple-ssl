<?php
# No need for the template engine
if (!defined('WP_USE_THEMES')) define( 'WP_USE_THEMES', false );
//we set wp admin to true, so the backend features get loaded.
if (!defined('RSSSL_DOING_SYSTEM_STATUS')) define( 'RSSSL_DOING_SYSTEM_STATUS' , true);

#find the base path
if (!defined('BASE_PATH'))  define( 'BASE_PATH', find_wordpress_base_path()."/" );

# Load WordPress Core
if ( !file_exists(BASE_PATH . 'wp-load.php') ) {
	die("WordPress not installed here");
}
require_once( BASE_PATH . 'wp-load.php' );
require_once( BASE_PATH . 'wp-includes/class-phpass.php' );
require_once( BASE_PATH . 'wp-admin/includes/image.php' );
require_once( BASE_PATH . 'wp-admin/includes/plugin.php');

//by deleting these we make sure these functions run again
delete_transient('rsssl_testpage');
function rsssl_get_system_status(){
	$output = '';
	if ( defined( 'RSSSL_SAFE_MODE' ) && RSSSL_SAFE_MODE ) {
		$output .=  "SAFE MODE\n";
	}

	global $wp_version;

	$output .=  "General\n";
	$output .=  "Domain: " . site_url() . "\n";
	$output .=  "Plugin version: " . rsssl_version . "\n";
	$output .=  "WordPress version: " . $wp_version . "\n";

	if ( RSSSL()->certificate->is_valid() ) {
		$output .=  "SSL certificate is valid\n";
	} else {
		if ( !function_exists('stream_context_get_params') ) {
			$output .=  "stream_context_get_params not available\n";
		} else if ( RSSSL()->certificate->detection_failed() ) {
			$output .=  "Not able to detect certificate\n";
		} else {
			$output .=  "Invalid SSL certificate\n";
		}
	}

	$output .=  ( rsssl_get_option("ssl_enabled") ) ? "SSL is enabled\n\n"
		: "SSL is not yet enabled\n\n";

	$output .=  "Options\n";
	if ( rsssl_get_option("mixed_content_fixer") ) {
		$output .=  "* Mixed content fixer\n";
	}
	$output .=  "* WordPress redirect".rsssl_get_option("redirect")."\n";

	if ( rsssl_get_option("switch_mixed_content_fixer_hook") ) {
		$output .=  "* Use alternative method to fix mixed content\n";
	}
	if ( rsssl_get_option("dismiss_all_notices") ) {
		$output .=  "* Dismiss all Really Simple SSL notices\n";
	}
	$output .=  "\n";

	$output .=  "Server information\n";
	$output .=  "Server: " . RSSSL()->server->get_server() . "\n";
	$output .=  "SSL Type: " . RSSSL()->admin->ssl_type . "\n";

	if ( function_exists('phpversion')) {
		$output .=  "PHP Version: " . phpversion() . "\n";
	}

	if ( is_multisite() ) {
		$output .=  "MULTISITE\n";
	}

	if ( rsssl_is_networkwide_active() ) {
		$output .=  "Really Simple SSL network wide activated\n";
	} else if ( is_multisite() ){
		$output .=  "Really Simple SSL per site activated\n";
	}

	$output .=  "<br>" . "<b>" . "SSL Configuration" . "</b>";
	$domain = RSSSL()->certificate->get_domain();
	$certinfo = RSSSL()->certificate->get_certinfo($domain);
	if ( !$certinfo ) {
		$output .=  "SSL certificate not valid<br>";
	}

	$domain_valid = RSSSL()->certificate->is_domain_valid($certinfo, $domain);
	if ( !$domain_valid ) {
		$output .=  "Domain on certificate does not match website's domain<br>";
	}

	$date_valid = RSSSL()->certificate->is_date_valid($certinfo);
	if ( !$date_valid ) {
		$output .=  "Date on certificate expired or not valid<br>";
	}
	$filecontents = get_transient('rsssl_testpage');
	if ( strpos($filecontents, "#SSL TEST PAGE#") !== false ) {
		$output .=  "SSL test page loaded successfully<br>";
	} else {
		$output .=  "Could not open testpage<br>";
	}
	if ( RSSSL()->admin->wpconfig_siteurl_not_fixed ) {
		$output .=  "siteurl or home url defines found in wpconfig<br>";
	}
	if ( RSSSL()->admin->wpconfig_siteurl_not_fixed ) {
		$output .=  "not able to fix wpconfig siteurl/homeurl.<br>";
	}

	if ( !is_writable(RSSSL()->admin->find_wp_config_path()) ) {
		$output .=  "wp-config.php not writable<br>";
	}
	$output .=  "Detected SSL setup: ".RSSSL()->admin->ssl_type."<br>";
	if ( file_exists(RSSSL()->admin->htaccess_file()) ){
		$output .=  "htaccess file exists.<br>";
		if ( !is_writable(RSSSL()->admin->htaccess_file()) ) {
			$output .=  "htaccess file not writable.<br>";
		}
	} else {
		$output .=  "no htaccess file available.<br>";
	}

	if (get_transient('rsssl_htaccess_test_success') === 'success') {
		$output .=  "htaccess redirect tested successfully.<br>";
	} else if (get_transient('rsssl_htaccess_test_success') === 'error') {
		$output .=  "htaccess redirect test failed.<br>";
	} else if (get_transient('rsssl_htaccess_test_success') === 'no-response') {
		$output .=  "htaccess redirect test failed: no response from server.<br>";
	}
	$mixed_content_fixer_detected = get_transient('rsssl_mixed_content_fixer_detected');
	if ($mixed_content_fixer_detected === 'no-response'){
		$output .=  "Could not connect to webpage to detect mixed content fixer<br>";
	}
	if ($mixed_content_fixer_detected === 'not-found'){
		$output .=  "Mixed content marker not found in websource<br>";
	}
	if ($mixed_content_fixer_detected === 'error'){
		$output .=  "Mixed content marker not found: unknown error<br>";
	}
	if ($mixed_content_fixer_detected === 'curl-error'){
		//Site has has a cURL error
		$output .=  "Mixed content fixer could not be detected: cURL error<br>";
	}
	if ($mixed_content_fixer_detected === 'found'){
		$output .=  "Mixed content fixer successfully detected<br>";
	}
	if ( !rsssl_get_option('mixed_content_fixer') ) {
		$output .=  "Mixed content fixer not enabled<br>";
	}
	if ( !RSSSL()->admin->htaccess_contains_redirect_rules() ) {
		$output .=  ".htaccess does not contain default Really Simple SSL redirect.<br>";
	}


	$output .=  "\nConstants\n";

	if ( defined( 'RSSSL_FORCE_ACTIVATE' ) ) {
		$output .=  "RSSSL_FORCE_ACTIVATE defined\n";
	}
	if ( defined( 'RSSSL_NO_FLUSH' ) ) {
		$output .=  "RSSSL_NO_FLUSH defined";
	}
	if ( defined( 'RSSSL_DISMISS_ACTIVATE_SSL_NOTICE' ) ) {
		$output .=  "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE defined\n";
	}
	if ( defined( 'RLRSSSL_DO_NOT_EDIT_HTACCESS' ) ) {
		$output .=  "RLRSSSL_DO_NOT_EDIT_HTACCESS defined\n";
	}
	if ( defined( 'RSSSL_SAFE_MODE' ) ) {
		$output .=  "RSSSL_SAFE_MODE defined\n";
	}
	if ( defined( "RSSSL_SERVER_OVERRIDE" ) ) {
		$output .=  "RSSSL_SERVER_OVERRIDE defined\n";
	}

	if ( ! defined( 'RSSSL_FORCE_ACTIVATE' )
	     && ! defined( 'RSSSL_NO_FLUSH' )
	     && ! defined( 'RSSSL_DISMISS_ACTIVATE_SSL_NOTICE' )
	     && ! defined( 'RLRSSSL_DO_NOT_EDIT_HTACCESS' )
	     && ! defined( 'RSSSL_SAFE_MODE' )
	     && ! defined( "RSSSL_SERVER_OVERRIDE" )
	) {
		$output .=  "No constants defined\n";
	}
	return $output;
}

if ( rsssl_user_can_manage() && isset($_GET['download']) ) {
	$content = rsssl_get_system_status();
	$fsize = function_exists( 'mb_strlen' ) ? mb_strlen( $content, '8bit' ) : strlen( $content );
	$file_name = 'really-simple-ssl-system-status.txt';

	//direct download
	header( "Content-type: application/octet-stream" );
	header( "Content-Disposition: attachment; filename=\"" . $file_name . "\"" );
	//open in browser
	//header("Content-Disposition: inline; filename=\"".$file_name."\"");
	header( "Content-length: $fsize" );
	header( "Cache-Control: private", false ); // required for certain browsers
	header( "Pragma: public" ); // required
	header( "Expires: 0" );
	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header( "Content-Transfer-Encoding: binary" );
	echo $content;
}

function find_wordpress_base_path()
{
	$path = __DIR__;

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

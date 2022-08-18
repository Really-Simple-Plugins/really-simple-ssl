<?php
# No need for the template engine
define( 'WP_USE_THEMES', false );
//we set wp admin to true, so the backend features get loaded.
if (!defined('RSSSL_DOING_SYSTEM_STATUS')) define( 'RSSSL_DOING_SYSTEM_STATUS' , true);

#find the base path
define( 'BASE_PATH', find_wordpress_base_path()."/" );

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
delete_transient('rsssl_domain_list');

if ( current_user_can( 'manage_options' ) ) {

	ob_start();
	if ( defined( 'RSSSL_SAFE_MODE' ) && RSSSL_SAFE_MODE ) {
		echo "SAFE MODE\n";
	}

	global $wp_version;

	echo "General\n";
	echo "Domain: " . site_url() . "\n";
	echo "Plugin version: " . rsssl_version . "\n";
	echo "WordPress version: " . $wp_version . "\n";

	if ( RSSSL()->rsssl_certificate->is_valid() ) {
		echo "SSL certificate is valid\n";
	} else {
		if ( !function_exists('stream_context_get_params') ) {
			echo "stream_context_get_params not available\n";
		} else if ( RSSSL()->rsssl_certificate->detection_failed() ) {
			echo "Not able to detect certificate\n";
		} else {
			echo "Invalid SSL certificate\n";
		}
	}

	echo ( RSSSL()->really_simple_ssl->ssl_enabled ) ? "SSL is enabled\n\n"
		: "SSL is not yet enabled\n\n";

	echo "Options\n";
	if ( RSSSL()->really_simple_ssl->autoreplace_insecure_links ) {
		echo "* Mixed content fixer\n";
	}
	if ( RSSSL()->really_simple_ssl->wp_redirect ) {
		echo "* WordPress redirect\n";
	}
	if ( RSSSL()->really_simple_ssl->htaccess_redirect ) {
		echo "* htaccess redirect\n";
	}

	if ( rsssl_get_option("switch_mixed_content_fixer_hook") ) {
		echo "* Use alternative method to fix mixed content\n";
	}
	if ( rsssl_get_option("dismiss_all_notices") ) {
		echo "* Dismiss all Really Simple SSL notices\n";
	}
	echo "\n";

	echo "Server information\n";
	echo "Server: " . RSSSL()->rsssl_server->get_server() . "\n";
	echo "SSL Type: " . RSSSL()->really_simple_ssl->ssl_type . "\n";

	if ( function_exists('phpversion')) {
		echo "PHP Version: " . phpversion() . "\n";
	}

	if ( is_multisite() ) {
		echo "MULTISITE\n";
		echo ( ! RSSSL()->rsssl_multisite->ssl_enabled_networkwide )
			? "SSL is being activated per site\n"
			: "SSL is activated network wide\n";
	}

	do_action( "rsssl_system_status" );

	echo "<br>" . "<b>" . "SSL Configuration" . "</b>";
	$domain = RSSSL()->rsssl_certificate->get_domain();
	$certinfo = RSSSL()->rsssl_certificate->get_certinfo($domain);
	if ( !$certinfo ) {
		echo "SSL certificate not valid<br>";
	}

	$domain_valid = RSSSL()->rsssl_certificate->is_domain_valid($certinfo, $domain);
	if ( !$domain_valid ) {
		echo "Domain on certificate does not match website's domain<br>";
	}

	$date_valid = RSSSL()->rsssl_certificate->is_date_valid($certinfo);
	if ( !$date_valid ) {
		echo "Date on certificate expired or not valid<br>";
	}
	$filecontents = get_transient('rsssl_testpage');
	if ( strpos($filecontents, "#SSL TEST PAGE#") !== false ) {
		echo "SSL test page loaded successfully<br>";
	} else {
		echo "Could not open testpage<br>";
	}
	if ( RSSSL()->really_simple_ssl->wpconfig_siteurl_not_fixed ) {
		echo "siteurl or home url defines found in wpconfig<br>";
	}
	if ( RSSSL()->really_simple_ssl->wpconfig_siteurl_not_fixed ) {
		echo "not able to fix wpconfig siteurl/homeurl.<br>";
	}
	if ( RSSSL()->really_simple_ssl->wpconfig_loadbalancer_fix_failed ) {
		echo "wp config loadbalancer fix FAILED.<br>";
	}
	if ( !is_writable(RSSSL()->really_simple_ssl->find_wp_config_path()) ) {
		echo "wp-config.php not writable<br>";
	}
	echo "Detected SSL setup: ".RSSSL()->really_simple_ssl->ssl_type()."<br>";
	if ( file_exists(RSSSL()->really_simple_ssl->htaccess_file()) ){
		echo "htaccess file exists.<br>";
		if ( !is_writable(RSSSL()->really_simple_ssl->htaccess_file()) ) {
			echo "htaccess file not writable.<br>";
		}
	} else {
		echo "no htaccess file available.<br>";
	}

	if (get_transient('rsssl_htaccess_test_success') === 'success') {
		echo "htaccess redirect tested successfully.<br>";
	} else if (get_transient('rsssl_htaccess_test_success') === 'error') {
		echo "htaccess redirect test failed.<br>";
	} else if (get_transient('rsssl_htaccess_test_success') === 'no-response') {
		echo "htaccess redirect test failed: no response from server.<br>";
	}
	$mixed_content_fixer_detected = get_transient('rsssl_mixed_content_fixer_detected');
	if ($mixed_content_fixer_detected === 'no-response'){
		echo "Could not connect to webpage to detect mixed content fixer<br>";
	}
	if ($mixed_content_fixer_detected === 'not-found'){
		echo "Mixed content marker not found in websource<br>";
	}
	if ($mixed_content_fixer_detected === 'error'){
		echo "Mixed content marker not found: unknown error<br>";
	}
	if ($mixed_content_fixer_detected === 'curl-error'){
		//Site has has a cURL error
		echo "Mixed content fixer could not be detected: cURL error<br>";
	}
	if ($mixed_content_fixer_detected === 'found'){
		echo "Mixed content fixer successfully detected<br>";
	}
	if ($mixed_content_fixer_detected === 'not-enabled') {
		echo "Mixed content fixer not enabled<br>";
	}
	if ( !RSSSL()->really_simple_ssl->htaccess_contains_redirect_rules() ) {
		echo ".htaccess does not contain default Really Simple SSL redirect.<br>";
	}


	echo "\nConstants\n";

	if ( defined( 'RSSSL_FORCE_ACTIVATE' ) ) {
		echo "RSSSL_FORCE_ACTIVATE defined\n";
	}
	if ( defined( 'RSSSL_NO_FLUSH' ) ) {
		echo "RSSSL_NO_FLUSH defined";
	}
	if ( defined( 'RSSSL_DISMISS_ACTIVATE_SSL_NOTICE' ) ) {
		echo "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE defined\n";
	}
	if ( defined( 'RLRSSSL_DO_NOT_EDIT_HTACCESS' ) ) {
		echo "RLRSSSL_DO_NOT_EDIT_HTACCESS defined\n";
	}
	if ( defined( 'RSSSL_SAFE_MODE' ) ) {
		echo "RSSSL_SAFE_MODE defined\n";
	}
	if ( defined( "RSSSL_SERVER_OVERRIDE" ) ) {
		echo "RSSSL_SERVER_OVERRIDE defined\n";
	}

	if ( ! defined( 'RSSSL_FORCE_ACTIVATE' )
	     && ! defined( 'RSSSL_NO_FLUSH' )
	     && ! defined( 'RSSSL_DISMISS_ACTIVATE_SSL_NOTICE' )
	     && ! defined( 'RLRSSSL_DO_NOT_EDIT_HTACCESS' )
	     && ! defined( 'RSSSL_SAFE_MODE' )
	     && ! defined( "RSSSL_SERVER_OVERRIDE" )
	) {
		echo "No constants defined\n";
	}

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
	//should not be here, so redirect to home
	wp_redirect( home_url() );
	exit;
}

function find_wordpress_base_path()
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

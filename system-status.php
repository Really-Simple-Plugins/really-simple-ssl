<?php
# No need for the template engine
if ( ! defined( 'WP_USE_THEMES' ) ) {
	define( 'WP_USE_THEMES', false ); // phpcs:ignore
}
//we set wp admin to true, so the backend features get loaded.
if ( ! defined( 'RSSSL_DOING_SYSTEM_STATUS' ) ) {
	define( 'RSSSL_DOING_SYSTEM_STATUS', true ); // phpcs:ignore
}

#find the base path
if ( ! defined( 'BASE_PATH' ) ) {
	define( 'BASE_PATH', rsssl_find_wordpress_base_path() . '/' );
}

# Load WordPress Core
if ( ! file_exists( BASE_PATH . 'wp-load.php' ) ) {
	die( 'WordPress not installed here' );
}
require_once BASE_PATH . 'wp-load.php';
require_once ABSPATH . 'wp-includes/class-phpass.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

//by deleting these we make sure these functions run again
delete_transient( 'rsssl_testpage' );
function rsssl_get_system_status() {
	$output = '';
	global $wp_version;

	$output .= "General\n";
	$output .= 'Domain: ' . site_url() . "\n";
	$output .= 'Plugin version: ' . rsssl_version . "\n";
	$output .= 'WordPress version: ' . $wp_version . "\n";

	if ( RSSSL()->certificate->is_valid() ) {
		$output .= "SSL certificate is valid\n";
	} else {
		if ( ! function_exists( 'stream_context_get_params' ) ) {
			$output .= "stream_context_get_params not available\n";
		} elseif ( RSSSL()->certificate->detection_failed() ) {
			$output .= "Not able to detect certificate\n";
		} else {
			$output .= "Invalid SSL certificate\n";
		}
	}

	$output .= ( rsssl_get_option( 'ssl_enabled' ) ) ? "SSL is enabled\n\n"
		: "SSL is not yet enabled\n\n";

	$output .= "Options\n";
	if ( rsssl_get_option( 'mixed_content_fixer' ) ) {
		$output .= "* Mixed content fixer\n";
	}
	$output .= '* WordPress redirect' . rsssl_get_option( 'redirect' ) . "\n";

	if ( rsssl_get_option( 'switch_mixed_content_fixer_hook' ) ) {
		$output .= "* Use alternative method to fix mixed content\n";
	}
	if ( rsssl_get_option( 'dismiss_all_notices' ) ) {
		$output .= "* Dismiss all Really Simple Security notices\n";
	}
	$output .= "\n";

	$output .= "Server information:\n";
	$output .= 'Server: ' . RSSSL()->server->get_server() . "\n";
	$output .= 'SSL Type: ' . RSSSL()->admin->ssl_type . "\n";

	if ( function_exists( 'phpversion' ) ) {
		$output .= 'PHP Version: ' . phpversion() . "\n\n";
	}

	if ( is_multisite() ) {
		$output .= "MULTISITE\n";
	}

	if ( rsssl_is_networkwide_active() ) {
		$output .= "Really Simple Security network wide activated\n";
	} elseif ( is_multisite() ) {
		$output .= "Really Simple Security per site activated\n";
	}

	$output   .= 'SSL Configuration:' . "\n";
	$domain   = RSSSL()->certificate->get_domain();
	$certinfo = RSSSL()->certificate->get_certinfo( $domain );
	if ( ! $certinfo ) {
		$output .= 'SSL certificate not valid' . "\n";
	}

	$domain_valid = RSSSL()->certificate->is_domain_valid( $certinfo, $domain );
	if ( ! $domain_valid ) {
		$output .= "Domain on certificate does not match website's domain" . "\n";
	}

	$date_valid = RSSSL()->certificate->is_date_valid( $certinfo );
	if ( ! $date_valid ) {
		$output .= 'Date on certificate expired or not valid' . "\n";
	}
	$filecontents = get_transient( 'rsssl_testpage' );
	if ( strpos( $filecontents, '#SSL TEST PAGE#' ) !== false ) {
		$output .= 'SSL test page loaded successfully' . "\n";
	} else {
		$output .= 'Could not open testpage' . "\n";
	}
	if ( RSSSL()->admin->wpconfig_siteurl_not_fixed ) {
		$output .= 'siteurl or home url defines found in wp-config.php' . "\n";
	}
	if ( RSSSL()->admin->wpconfig_siteurl_not_fixed ) {
		$output .= 'not able to fix wpconfig siteurl/homeurl.' . "\n";
	}

	if ( ! is_writable( rsssl_find_wp_config_path() ) ) {
		$output .= 'wp-config.php not writable<br>';
	}
	$output .= 'Detected SSL setup: ' . RSSSL()->admin->ssl_type . "\n";
	if ( file_exists( RSSSL()->admin->htaccess_file() ) ) {
		$output .= 'htaccess file exists.' . "\n";
		if ( ! is_writable( RSSSL()->admin->htaccess_file() ) ) {
			$output .= 'htaccess file not writable.' . "\n";
		}
	} else {
		$output .= 'no htaccess file available.' . "\n";
	}

	if ( get_transient( 'rsssl_htaccess_test_success' ) === 'success' ) {
		$output .= 'htaccess redirect tested successfully.' . "\n";
	} elseif ( get_transient( 'rsssl_htaccess_test_success' ) === 'error' ) {
		$output .= 'htaccess redirect test failed.' . "\n";
	} elseif ( get_transient( 'rsssl_htaccess_test_success' ) === 'no-response' ) {
		$output .= 'htaccess redirect test failed: no response from server.' . "\n";
	}
	$mixed_content_fixer_detected = get_transient( 'rsssl_mixed_content_fixer_detected' );
	if ( 'no-response' === $mixed_content_fixer_detected ) {
		$output .= 'Could not connect to webpage to detect mixed content fixer' . "\n";
	}
	if ( 'not-found' === $mixed_content_fixer_detected ) {
		$output .= 'Mixed content marker not found in websource' . "\n";
	}
	if ( 'error' === $mixed_content_fixer_detected ) {
		$output .= 'Mixed content marker not found: unknown error' . "\n";
	}
	if ( 'curl-error' === $mixed_content_fixer_detected ) {
		//Site has has a cURL error
		$output .= 'Mixed content fixer could not be detected: cURL error' . "\n";
	}
	if ( 'found' === $mixed_content_fixer_detected ) {
		$output .= 'Mixed content fixer successfully detected' . "\n";
	}
	if ( ! rsssl_get_option( 'mixed_content_fixer' ) ) {
		$output .= 'Mixed content fixer not enabled' . "\n";
	}
	if ( ! RSSSL()->admin->htaccess_contains_redirect_rules() ) {
		$output .= '.htaccess does not contain default Really Simple Security redirect.' . "\n";
	}

	$output .= "\nConstants\n";

	if ( defined( 'RSSSL_FORCE_ACTIVATE' ) ) {
		$output .= "RSSSL_FORCE_ACTIVATE defined\n";
	}
	if ( defined( 'RSSSL_NO_FLUSH' ) ) {
		$output .= "RSSSL_NO_FLUSH defined\n";
	}
	if ( defined( 'RSSSL_DISMISS_ACTIVATE_SSL_NOTICE' ) ) {
		$output .= "RSSSL_DISMISS_ACTIVATE_SSL_NOTICE defined\n";
	}
	if ( defined( 'RSSSL_SAFE_MODE' ) ) {
		$output .= "RSSSL_SAFE_MODE defined\n";
	}
	if ( defined( 'RSSSL_SERVER_OVERRIDE' ) ) {
		$output .= "RSSSL_SERVER_OVERRIDE defined\n";
	}
	if ( defined( 'rsssl_no_rest_api_redirect' ) ) {
		$output .= "rsssl_no_rest_api_redirect defined\n";
	}
	if ( defined( 'rsssl_no_wp_redirect' ) ) {
		$output .= "rsssl_no_wp_redirect defined\n";
	}
	if ( defined( 'RSSSL_CONTENT_FIXER_ON_INIT' ) ) {
		$output .= "RSSSL_CONTENT_FIXER_ON_INIT defined\n";
	}
	if ( defined( 'FORCE_SSL_ADMIN' ) ) {
		$output .= "FORCE_SSL_ADMIN defined\n";
	}
	if ( defined( 'RSSSL_CSP_MAX_REQUESTS' ) ) {
		$output .= "RSSSL_CSP_MAX_REQUESTS defined\n";
	}
	if ( defined( 'RSSSL_DISABLE_CHANGE_LOGIN_URL' ) ) {
		$output .= "RSSSL_DISABLE_CHANGE_LOGIN_URL defined\n";
	}
	if ( defined( 'RSSSL_LEARNING_MODE' ) ) {
		$output .= "RSSSL_LEARNING_MODE defined\n";
	}
	if ( defined( 'RSSSL_DEACTIVATING_FREE' ) ) {
		$output .= "RSSSL_DEACTIVATING_FREE defined\n";
	}
	if ( defined( 'RSSSL_UPGRADING_TO_PRO' ) ) {
		$output .= "RSSSL_UPGRADING_TO_PRO defined\n";
	}

	if ( ! defined( 'RSSSL_FORCE_ACTIVATE' )
	     && ! defined( 'RSSSL_NO_FLUSH' )
	     && ! defined( 'RSSSL_DISMISS_ACTIVATE_SSL_NOTICE' )
	     && ! defined( 'RSSSL_SAFE_MODE' )
	     && ! defined( 'RSSSL_SERVER_OVERRIDE' )
	     && ! defined( 'rsssl_no_rest_api_redirect' )
	     && ! defined( 'rsssl_no_wp_redirect' )
	     && ! defined( 'RSSSL_CONTENT_FIXER_ON_INIT' )
	     && ! defined( 'FORCE_SSL_ADMIN' )
	     && ! defined( 'RSSSL_CSP_MAX_REQUESTS' )
	     && ! defined( 'RSSSL_DISABLE_CHANGE_LOGIN_URL' )
	     && ! defined( 'RSSSL_LEARNING_MODE' )
	     && ! defined( 'RSSSL_DEACTIVATING_FREE' )
	     && ! defined( 'RSSSL_UPGRADING_TO_PRO' )
	) {
		$output .= "No constants defined\n";
	}

	$output .= "\n";

	$output .= "rsssl_options:\n";

	if ( is_multisite() && rsssl_is_networkwide_active() ) {
		$stored_options = get_site_option( 'rsssl_options', [] );
	} else {
		$stored_options = get_option( 'rsssl_options', [] );
	}

	unset($stored_options['permissions_policy']);
	unset($stored_options['upgrade_insecure_requests']);
	unset($stored_options['x_xss_protection']);
	unset($stored_options['x_content_type_options']);
	unset($stored_options['x_frame_options']);
	unset($stored_options['referrer_policy']);
	unset($stored_options['content_security_policy']);
	unset($stored_options['xmlrpc_status_lm_enabled_once']);
	unset($stored_options['csp_status_lm_enabled_once']);
	unset($stored_options['csp_frame_ancestors_urls']);
	unset($stored_options['file_change_exclusions']);
	unset($stored_options['license']);
	unset($stored_options['cross_origin_opener_policy']);
	unset($stored_options['cross_origin_resource_policy']);
	unset($stored_options['cross_origin_embedder_policy']);


	$output .= print_r( $stored_options, true ) . "\n\n";

	$output .= "Installed plugins:\n";
	$output .= rsssl_system_status_get_installed_plugins() . "\n\n";

	if ( rsssl_get_option( 'enable_firewall' ) == 1 ) {
		$output .= "Blocked regions firewall: \n";
		$output .= rsssl_system_status_get_blocked_countries_firewall() . "\n\n";

		$output .= "Whitelist firewall: \n";
		$output .= rsssl_system_status_get_whitelist() . "\n\n";

		$output .= "Blocked IPs firewall: \n";
		$output .= rsssl_system_status_get_blocked_ips() . "\n\n";
	}

	if ( rsssl_get_option("enable_limited_login_attempts") == 1 ) {
		$output .= "Blocked regions LLA: \n";
		$output .= rsssl_system_status_get_blocked_countries_lla() . "\n\n";

		$output .= "Blocked users LLA: \n";
		$output .= rsssl_system_status_get_blocked_users_lla() . "\n\n";

		$output .= "Blocked ips LLA: \n";
		$output .= rsssl_system_status_get_blocked_ips_lla() . "\n\n";
	}

	if ( rsssl_get_option( 'login_protection_enabled' ) == 1 ) {
		$output .= "Users with 2FA enabled: \n";
		$output .= rsssl_system_status_get_2fa_users() . "\n\n";
	}

	return $output;
}

if ( rsssl_user_can_manage() && isset( $_GET['download'] ) ) {
	$rsssl_content   = rsssl_get_system_status();
	$rsssl_fsize     = function_exists( 'mb_strlen' ) ? mb_strlen( $rsssl_content, '8bit' ) : strlen( $rsssl_content );
	$rsssl_file_name = 'really-simple-ssl-system-status.txt';

	//direct download
	header( 'Content-type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename="' . $rsssl_file_name . '"' );
	//open in browser
	//header("Content-Disposition: inline; filename=\"".$file_name."\"");
	header( "Content-length: $rsssl_fsize" );
	header( 'Cache-Control: private', false ); // required for certain browsers
	header( 'Pragma: public' ); // required
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Content-Transfer-Encoding: binary' );
	echo $rsssl_content;
}

function rsssl_find_wordpress_base_path() {
	$path = __DIR__;

	// Check for Bitnami WordPress installation
	if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && $_SERVER['DOCUMENT_ROOT'] === '/opt/bitnami/wordpress' ) {
		return '/opt/bitnami/wordpress';
	}

	do {
		if ( file_exists( $path . '/wp-config.php' ) ) {
			//check if the wp-load.php file exists here. If not, we assume it's in a subdir.
			if ( file_exists( $path . '/wp-load.php' ) ) {
				return $path;
			} else {
				//wp not in this directory. Look in each folder to see if it's there.
				if ( file_exists( $path ) && $handle = opendir( $path ) ) { //phpcs:ignore
					while ( false !== ( $file = readdir( $handle ) ) ) {//phpcs:ignore
						if ( '.' !== $file && '..' !== $file ) {
							$file = $path . '/' . $file;
							if ( is_dir( $file ) && file_exists( $file . '/wp-load.php' ) ) {
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
	} while ( $path = realpath( "$path/.." ) ); //phpcs:ignore

	return false;
}

function rsssl_system_status_get_installed_plugins() {
	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	// Load the plugin admin functions
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Get the list of all installed plugins
	$all_plugins = get_plugins();
	$plugin_list = array();

	// Loop through plugins to format the list with name and version
	foreach ( $all_plugins as $plugin_path => $plugin_data ) {
		$plugin_list[] = $plugin_data['Name'] . ' (' . $plugin_data['Version'] . ')';
	}

	// Return the list as a comma-separated string
	return implode( ', ', $plugin_list );
}

function rsssl_system_status_get_blocked_countries_firewall() {

	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	global $wpdb;
	$table_name   = $wpdb->base_prefix . 'rsssl_geo_block';
	$query_string = $wpdb->prepare(
		"SELECT iso2_code FROM {$table_name} WHERE data_type = %s AND ip_address is NULL",
		'country'
	);
	// phpcs:ignore
	$result         = $wpdb->get_results( $query_string );
	$column_results = array_column( $result, 'iso2_code' );

	return implode( ',', $column_results );
}

function rsssl_system_status_get_whitelist() {

	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	global $wpdb;
	$table_name   = $wpdb->base_prefix . 'rsssl_geo_block';
	$query_string = $wpdb->prepare(
		"SELECT ip_address FROM {$table_name} WHERE data_type = %s",
		'trusted'
	);
	// phpcs:ignore
	$result         = $wpdb->get_results( $query_string );
	$column_results = array_column( $result, 'ip_address' );

	return implode( ',', $column_results );

}

function rsssl_system_status_get_blocked_countries_lla() {

	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->base_prefix . 'rsssl_login_attempts';

	// Query to get all the blocked countries from the login attempts table where attempt_type is 'country'
	$query_string = $wpdb->prepare(
		"SELECT attempt_value FROM {$table_name} WHERE attempt_type = %s AND status = %s",
		'country', 'blocked'
	);

	// phpcs:ignore
	$result         = $wpdb->get_results( $query_string );
	$column_results = array_column( $result, 'attempt_value' );

	if ( empty( $column_results ) ) {
		return "No blocked countries found.";
	}

	return implode( ',', $column_results );
}

function rsssl_system_status_get_blocked_ips() {
	if ( ! current_user_can( 'manage_security' ) ) {
		return '';
	}

	$output = '';

	global $wpdb;
	$sql = $wpdb->prepare(
		"SELECT ip_address FROM {$wpdb->base_prefix}rsssl_geo_block WHERE blocked = %d AND data_type = %s",
		1,
		'404'
	);

	$results = $wpdb->get_results( $sql, ARRAY_A );

	if ( empty( $results ) ) {
		return "No blocked IPs found.";
	}

	foreach ( $results as $row ) {
		$output .= $row['ip_address'] . "\n";
	}

	return $output;
}

function rsssl_system_status_get_blocked_users_lla() {
	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->base_prefix . 'rsssl_login_attempts';

	// Query to get all blocked users from login attempts where attempt_type is 'username'
	$query_string = $wpdb->prepare(
		"SELECT attempt_value FROM {$table_name} WHERE attempt_type = %s AND status = %s",
		'username', 'blocked'
	);

	// phpcs:ignore
	$result = $wpdb->get_results( $query_string );
	$column_results = array_column( $result, 'attempt_value' );

	if ( empty( $column_results ) ) {
		return "No blocked users found.";
	}

	return implode( ',', $column_results );
}

function rsssl_system_status_get_blocked_ips_lla() {
	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->base_prefix . 'rsssl_login_attempts';

	// Query to get blocked single IPs (without CIDR notation) from login attempts
	$query_string = $wpdb->prepare(
		"SELECT attempt_value FROM {$table_name} WHERE attempt_type = %s AND status = %s AND attempt_value NOT LIKE %s",
		'source_ip', 'blocked', '%/%'
	);

	// phpcs:ignore
	$result = $wpdb->get_results( $query_string );
	$column_results = array_column( $result, 'attempt_value' );

	if ( empty( $column_results ) ) {
		return "No blocked users found.";
	}

	return implode( ',', $column_results );
}

function rsssl_system_status_get_2fa_users() {
	if ( ! current_user_can( 'manage_security' ) ) {
		return;
	}

	global $wpdb;

	// Query to get all users with TOTP or email 2FA status
	$query = "
		SELECT user_id, meta_key, meta_value
		FROM {$wpdb->usermeta}
		WHERE meta_key IN ('rsssl_two_fa_status_totp', 'rsssl_two_fa_status_email')
	";

	// Execute the query
	$results = $wpdb->get_results( $query );

	// If no results, return a message
	if ( empty( $results ) ) {
		return 'No users found with 2FA settings.';
	}

	// Array to store users and their 2FA methods
	$users_2fa = array();

	// Organize the 2FA status by user
	foreach ( $results as $row ) {
		if ( ! isset( $users_2fa[ $row->user_id ] ) ) {
			$users_2fa[ $row->user_id ] = array(
				'rsssl_two_fa_status_totp' => '',
				'rsssl_two_fa_status_email' => '',
			);
		}

		// Update TOTP or email 2FA status
		$users_2fa[ $row->user_id ][ $row->meta_key ] = $row->meta_value;
	}

	// Prepare output for users with active 2FA methods
	$output = array();
	foreach ( $users_2fa as $user_id => $user_data ) {
		$user_info = get_userdata( $user_id );

		// Determine the active 2FA method
		if ( $user_data['rsssl_two_fa_status_totp'] === 'active' ) {
			$output[] = $user_info->user_login . ' - TOTP';
		} elseif ( $user_data['rsssl_two_fa_status_email'] === 'active' ) {
			$output[] = $user_info->user_login . ' - Email';
		}
	}

	// If no users are found with 2FA, add a note
	if ( empty( $output ) ) {
		return 'No users found with 2FA enabled.';
	}

	// Return a newline-separated list of users and their 2FA statuses
	return implode( "\n", $output );
}

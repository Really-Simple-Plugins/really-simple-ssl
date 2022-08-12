<?php
defined( 'ABSPATH' ) or die();
/**
 * @return bool
 *
 * Check if user registration is allowed
 */
function rsssl_user_registration_allowed() {
	if ( get_option( 'users_can_register' ) !== false ) {
		return true;
	}

	return false;
}
/**
 * Check if XML-RPC requests are allowed on this site
 * POST a request, if the request returns a 200 response code the request is allowed
 */
function rsssl_xmlrpc_allowed()
{
	if ( ! get_transient( 'rsssl_xmlrpc_allowed' ) ) {

		if ( function_exists( 'curl_init' ) ) {
			$url = site_url() . '/xmlrpc.php';

			$ch = curl_init($url);

			// XML-RPC listMethods call
			// Valid XML-RPC request
			$xmlstring = '<?xml version="1.0" encoding="utf-8"?> 
                            <methodCall>
                            <methodName>system.listMethods</methodName>
                            <params></params>
                            </methodCall>';

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// Post string
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlstring );
			curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds

			curl_exec($ch);

			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($response_code === 200) {
				set_transient( 'rsssl_xmlrpc_allowed', 'allowed', DAY_IN_SECONDS );
				return true;
			} else {
				set_transient( 'rsssl_xmlrpc_allowed', 'not-allowed', DAY_IN_SECONDS );
				return false;
			}
		}

	} else {
		return get_transient( 'rsssl_xmlrpc_allowed' );
	}

	return false;

}

/**
 * @return bool
 * Test if HTTP methods are allowed
 */
function rsssl_http_methods_allowed()
{
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	$tested = get_transient( 'rsssl_http_methods_allowed' );

	if ( ! $tested ) {

		$tested = [];

		if ( function_exists('curl_init' ) ) {

			$methods = array(
				'GET',
				'POST',
				'PUT',
				'DELETE',
				'HEAD',
				'OPTIONS',
				'CONNECT',
				'TRACE',
				'TRACK',
				'PATCH',
				'COPY',
				'LINK',
				'UNLINK',
				'PURGE',
				'LOCK',
				'UNLOCK',
				'PROPFIND',
				'VIEW',
			);

			foreach ( $methods as $method ) {
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, site_url() );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch,CURLOPT_NOBODY, true);
				curl_setopt($ch,CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds
				curl_exec($ch);
				if ( curl_errno( $ch ) == 405 || curl_errno( $ch ) == 403 ) {
					$tested['not-allowed'][] = $method;
				} else {
					$tested['allowed'][] = $method;
				}
				curl_close($ch);
			}
			set_transient('rsssl_http_methods_allowed', $tested);
		}
	}

	if ( ! empty($tested['allowed'])) {
		return true;
	}
	return false;
}
add_action('admin_init', 'rsssl_http_methods_allowed');

/**
 * Check if file editing is allowed
 *
 * @return bool
 */
function rsssl_file_editing_allowed() {
	if ( defined('DISALLOW_FILE_EDIT' ) ) {
		return false;
	}
	return true;
}

/**
 * @return bool
 *
 * Check if DB has default wp_ prefix
 */
function rsssl_is_default_wp_prefix() {

	global $wpdb;

	if ( $wpdb->prefix === 'wp_') {
		return true;
	}

	return false;
}

/**
 * @return bool
 *
 * Check if user admin exists
 */
function rsssl_has_admin_user() {

	$users = get_users();
	foreach ( $users as $user ) {
		if ( $user->data->user_login === 'admin') {
			return true;
		}
	}

	return false;
}

/**
 * @return bool
 *
 * Check if user ID 1 exists end if user enumeration has been disabled
 */
function rsssl_id_one_no_enumeration() {
	$user_id_one = get_user_by( 'id', 1 );
	if ( $user_id_one && ! rsssl_get_option( 'disable_user_enumeration' ) ) {
		return true;
	}

	return false;
}

/**
 * Check if display name is the same as login
 */
function rsssl_display_name_equals_login() {
	$user = wp_get_current_user();
	if ( $user->data->user_login === $user->data->display_name ) {
		return true;
	}

	return false;
}

/**
 * Check if debugging in WordPress is enabled
 * @return bool
 */
function rsssl_is_debug_log_enabled() {
	if ( defined('WP_DEBUG') && defined('WP_DEBUG_LOG') ) {
		return true;
	}

	return false;
}

function rsssl_get_debug_log_value(){
	$wpconfig_path = rsssl_find_wp_config_path();
	if ( !$wpconfig_path ) return false;

	$wpconfig      = file_get_contents( $wpconfig_path );

	// Get WP_DEBUG_LOG declaration
	$regex = "/^\s*define\([ ]{0,2}[\'|\"]WP_DEBUG_LOG[\'|\"][ ]{0,2},[ ]{0,2}(.*)[ ]{0,2}\);/m";
	preg_match( $regex, $wpconfig, $matches );
	if ($matches && isset($matches[1]) ){
		return $matches[1];
	} else {
		return 'true';
	}
}
function rsssl_debugging_enabled(){
	return defined('WP_DEBUG') && WP_DEBUG;
}
/**
 * Check if default.log is in default location
 * @return bool
 */
function rsssl_debug_log_in_default_location() {
	$debug_log_value = rsssl_get_debug_log_value();
	// If str contains true, location is default
    if ( trim($debug_log_value) === 'true' ) {
        return true;
    }
    return false;
}

/**
 * Check if debug location is not default, and if that is done by RSSSL>
 * @return bool
*/
function rsssl_enabled_by_rsssl($option_name, $test){
	$test_result = false;
	if (function_exists($test)) {
		$test_result = $test();
	}

	if ( $test_result && !rsssl_get_value('change_debug_log_location') ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Check if WordPress version is above 5.6 for application password support
 * @return bool
 */
function rsssl_wordpress_version_above_5_6() {
	global $wp_version;
	if ( $wp_version < 5.6 ) {
		return false;
	}

	return true;
}

/**
 * @return string
 * Test if code execution is allowed in /uploads folder
 */
function rsssl_code_execution_allowed()
{
	$result = false;
	$upload_dir = wp_get_upload_dir();
	$test_file = $upload_dir['basedir'] . '/' . 'code-execution.php';
	if ( is_writable($upload_dir['basedir'] )  ) {
		if ( ! file_exists( $test_file ) ) {
			copy( rsssl_path . 'security/tests/code-execution.php', $test_file );
		}
	}

	if ( file_exists( $test_file ) ) {
		require_once( $test_file );
		if ( function_exists( 'rsssl_test_code_execution' ) && rsssl_test_code_execution() ) {
			$result = true;
		}
	}

	return $result;
}

/**
 * Test if directory indexing is allowed
 * @return bool
 */
function rsssl_directory_indexing_allowed() {
	$status = get_transient('rsssl_directory_indexing_status');
	if ( !$status ) {
		$test_folder = 'rssslbrowsingtest';
		$test_dir = trailingslashit(ABSPATH) . $test_folder;
		if ( ! is_dir( $test_dir ) ) {
			mkdir( $test_dir, 755 );
		}
		$response = wp_remote_get(trailingslashit( site_url($test_folder) ) );
		if ( is_dir( $test_dir )  ) {
			rmdir( $test_dir );
		}

		// WP_Error won't contain response code, return false
		if ( is_wp_error( $response ) ) {
			$status = 'error';
		} else {
			$response_code = $response['response']['code'];
			if ( $response_code === 403 ) {
				$status = 'forbidden';
			} else {
				$status = 'allowed';
			}
		}
		set_transient('rsssl_directory_indexing_status', $status, WEEK_IN_SECONDS );
	}

	if ($status==='allowed') {
		return true;
	} else {
		return false;
	}
}
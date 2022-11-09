<?php
defined( 'ABSPATH' ) or die();

/**
 * Check if XML-RPC requests are allowed on this site
 * POST a request, if the request returns a 200 response code the request is allowed
 */
function rsssl_xmlrpc_allowed()
{
	$allowed = get_transient( 'rsssl_xmlrpc_allowed' );
	if ( !$allowed ) {
		$allowed = 'allowed';
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
				$allowed = 'allowed';
			} else {
				$allowed = 'not-allowed';
			}
		}
		set_transient( 'rsssl_xmlrpc_allowed', $allowed, DAY_IN_SECONDS );
	}
	return $allowed === 'allowed';
}

/**
 * @return bool
 * Test if HTTP methods are allowed
 */
function rsssl_http_methods_allowed()
{
	if ( ! rsssl_user_can_manage() ) {
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

	if ( !empty($tested['allowed'])) {
		return true;
	}
	return false;
}

/**
 * @return bool
 *
 * Check if DB has default wp_ prefix
 */

function rsssl_is_default_wp_prefix() {
	global $wpdb;
	if ( $wpdb->prefix === 'wp_' ) {
		return true;
	}
	return false;
}

function rsssl_xmlrpc_enabled(){
	return apply_filters('xmlrpc_enabled', true );
}

/**
 * @return bool
 *
 * Check if user admin exists
 */

function rsssl_has_admin_user() {
	if ( !rsssl_user_can_manage() ) {
		return false;
	}
	$count = wp_cache_get('rsssl_admin_user_count', 'really-simple-ssl');
	if ( $count === false ){
		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}users WHERE user_login = 'admin'" );
		wp_cache_set('rsssl_admin_user_count', $count, 'really-simple-ssl', HOUR_IN_SECONDS);
	}

	if ( $count > 0 ) {
		return true;
	}
	return false;
}

/**
 * Check if username is valid for use
 * @return bool
 */
function rsssl_new_username_valid(): bool {
	$new_user_login = trim(sanitize_user(rsssl_get_option('new_admin_user_login')));
	if ( $new_user_login === 'admin' ) {
		return false;
	}
	$user_exists = get_user_by('login', $new_user_login);
	if ( $user_exists ) {
		return false;
	}

	return is_string($new_user_login) && strlen($new_user_login)>2;
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
 * For backward compatibility we need to wrap this function, as older versions do not have this function (<5.6)
 * @return bool
 */
function rsssl_wp_is_application_passwords_available(){
	if ( function_exists('wp_is_application_passwords_available') ) {
		return wp_is_application_passwords_available();
	} else {
		return false;
	}
}

/**
 * Get users where display name is the same as login
 *
 * @param bool $return_users
 *
 * @return bool | array
 *
 */

function rsssl_get_users_where_display_name_is_login( $return_users=false ) {
	$found_users = [];
	$users = wp_cache_get('rsssl_admin_users', 'really-simple-ssl');
	if ( !$users ){
		$args = array(
			'role'    => 'administrator',
		);
		$users = get_users( $args );
		wp_cache_set('rsssl_admin_users', $users, 'really-simple-ssl', HOUR_IN_SECONDS);
	}

	foreach ( $users as $user ) {
		if ($user->display_name === $user->user_login) {
			$found_users[] = $user->user_login;
		}
	}

	// Maybe return users in integration
	if ( $return_users ) {
		return $found_users;
	} else if ( count($found_users)>0 ) {
		return true;
	}

	return false;
}

/**
 * Check if debugging in WordPress is enabled
 *
 * @return bool
 */
function rsssl_is_debugging_enabled() {
	return ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG );
}

function rsssl_debug_log_value_is_default(){
	$value = rsssl_get_debug_log_value();

	return (string) $value === 'true';
}

/**
 * Get value of debug_log constant
 * Please note that for a value 'true', you should check for the string value === 'true'
 * @return bool|string
 */

function rsssl_get_debug_log_value(){
	if ( !defined('WP_DEBUG_LOG')) {
		return false;
	}
	$wpconfig_path = rsssl_find_wp_config_path();

	if ( !$wpconfig_path ) {
		return false;
	}
	$wpconfig      = file_get_contents( $wpconfig_path );

	// Get WP_DEBUG_LOG declaration
	$regex = "/^\s*define\([ ]{0,2}[\'|\"]WP_DEBUG_LOG[\'|\"][ ]{0,2},[ ]{0,2}(.*)[ ]{0,2}\);/m";
	preg_match( $regex, $wpconfig, $matches );
	if ($matches && isset($matches[1]) ){
		return trim($matches[1]);
	}

	return false;
}

/**
 * Check if the debug log file exists in the default location, and if it contains our bogus info
 * @return bool
 *
 */
function rsssl_debug_log_file_exists_in_default_location(){
	$default_file = trailingslashit(WP_CONTENT_DIR).'debug.log';
	if ( !file_exists($default_file) ) {
		return false;
	}
	//limit max length of string to 500
	$content = file_get_contents($default_file, false, null, 0, 500 );
	return trim( $content ) !== 'Access denied';
}

/**
 * @return string
 * Test if code execution is allowed in /uploads folder
 */
function rsssl_code_execution_allowed()
{
	$code_execution_allowed = get_transient('rsssl_code_execution_allowed_status');
	if ( !$code_execution_allowed ) {
		$upload_dir = wp_get_upload_dir();
		//set a default
		$code_execution_allowed = 'not-allowed';
		$test_file = $upload_dir['basedir'] . '/' . 'code-execution.php';
		if ( is_writable($upload_dir['basedir'] ) && ! file_exists( $test_file ) ) {
			try {
				copy( rsssl_path . 'security/tests/code-execution.php', $test_file );
			} catch (Exception $e) {
				$code_execution_allowed = 'not-allowed';
			}
		}

		if ( file_exists( $test_file ) ) {
			$uploads    = wp_upload_dir();
			$upload_url = trailingslashit($uploads['baseurl']).'code-execution.php';
			$response = wp_remote_get($upload_url);
			if ( !is_wp_error($response) ) {
				if ( is_array( $response ) ) {
					$status = wp_remote_retrieve_response_code( $response );
					$web_source = wp_remote_retrieve_body( $response );
				}

				if ( $status != 200 ) {
					//Could not connect to website
					$code_execution_allowed = 'not-allowed';
				} elseif ( strpos( $web_source, "RSSSL CODE EXECUTION MARKER" ) === false ) {
					//Mixed content fixer marker not found in the websource
					$code_execution_allowed = 'not-allowed';
				} else {
					$code_execution_allowed = 'allowed';
				}
			} else {
				$code_execution_allowed = 'not-allowed';
			}
		}
		set_transient('rsssl_code_execution_allowed_status', $code_execution_allowed, DAY_IN_SECONDS);
	}

	return $code_execution_allowed === 'allowed';
}

/**
 * Test if directory indexing is allowed
 * We assume allowed if test is not possible due to restrictions. Only an explicity 403 on the response results in "forbidden".
 * On non htaccess servers, the default is non indexing, so we return forbidden.
 *
 * @return bool
 */
function rsssl_directory_indexing_allowed() {
	$status = get_transient('rsssl_directory_indexing_status');
	if ( !$status ) {
		if ( !rsssl_uses_htaccess() ) {
			$status = 'forbidden';
		} else {
			$status = 'allowed';
			try {
				$test_folder = 'indexing-test';
				$test_dir = trailingslashit(ABSPATH) . $test_folder;
				if ( ! is_dir( $test_dir ) ) {
					mkdir( $test_dir, 0755 );
				}

				$response = wp_remote_get(trailingslashit( site_url($test_folder) ) );
				if ( is_dir( $test_dir )  ) {
					rmdir( $test_dir );
				}

				// WP_Error doesn't contain response code, return false
				if ( !is_wp_error( $response ) ) {
					$response_code = $response['response']['code'];
					if ( $response_code === 403 ) {
						$status = 'forbidden';
					}
				}
			} catch( Exception $e ) {

			}
		}
		set_transient('rsssl_directory_indexing_status', $status, DAY_IN_SECONDS );
	}

	if ( $status==='forbidden' ) {
		return false;
	} else {
		return true;
	}
}


/**
 * Check if file editing is allowed
 * @return bool
 */
function rsssl_file_editing_allowed()
{
	if ( function_exists('wp_is_block_theme') && wp_is_block_theme() ) {
		return false;
	}
	return !defined('DISALLOW_FILE_EDIT' ) || !DISALLOW_FILE_EDIT;
}

/**
 * Check if user registration is allowed
 * @return bool
 */
function rsssl_user_registration_allowed()
{
	return get_option( 'users_can_register' );
}

/**
 * Check if page source contains WordPress version information
 * @return bool
 */

function rsssl_src_contains_wp_version() {
	$wp_version = get_bloginfo( 'version' );
	$result = get_transient('rsssl_wp_version_detected' );
	if ( !$result ) {
		$result = 'found';
		$web_source = "";
		//check if the mixed content fixer is active
		$response = wp_remote_get( home_url() );
		if ( ! is_wp_error( $response ) ) {
			if ( is_array( $response ) ) {
				$status     = wp_remote_retrieve_response_code( $response );
				$web_source = wp_remote_retrieve_body( $response );
			}

			if ( $status != 200 ) {
				$result = 'no-response';
			} elseif ( strpos( $web_source, 'ver='.$wp_version ) === false ) {
				$result = 'not-found';
			} else {
				$result = 'found';
			}
		}
		set_transient( 'rsssl_wp_version_detected', $result, DAY_IN_SECONDS );
	}
	return $result==='found';
}
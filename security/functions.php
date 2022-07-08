<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return string
 * Delete transients
 */
//if ( ! function_exists('rsssl_delete_transients' ) ) {
//    function rsssl_delete_transients()
//    {
//        $transients = array(
//	        'rsssl_xmlrpc_allowed',
//			'rsssl_wp_version_detected',
//			'rsssl_http_options_allowed',
//        );
//
//        foreach ( $transients as $transient ) {
//            delete_transient( $transient );
//        }
//    }
//}
/**
 * Complete a fix for an issue, either user triggered, or automatic
 * @param $fix
 *
 * @return void
 */
function rsssl_do_fix($fix){
	if ( !current_user_can('manage_options')) {
		return;
	}

	if ( !rsssl_has_fix($fix) && function_exists($fix)) {
		$completed[]=$fix;
		$fix();
		$completed = get_option('rsssl_completed_fixes', []);
		update_option('rsssl_completed_fixes', $completed );
	} elseif ($fix && !function_exists($fix) ) {
		error_log("Really Simple SSL: fix function $fix not found");
	}

}

function rsssl_has_fix($fix){
	$completed = get_option('rsssl_completed_fixes', []);
	if ( !in_array($fix, $completed)) {
		return false;
	}
	return true;
}

/**
 * Wrap the security headers
 */
if ( ! function_exists('rsssl_wrap_headers' ) ) {
	function rsssl_wrap_headers() {

		$htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();

		if ( file_exists( $htaccess_file ) && is_writable( $htaccess_file ) ) {

			$htaccess = file_get_contents($htaccess_file);

			$rules = '';

			$start = "\n" . '#Begin Really Simple Security Headers';
			$end   = "\n" . '#End Really Simple Security Headers' . "\n";

			if ( !get_option( 'disable_indexing' ) ) {
				$rules .= "\n" . "Options -Indexes";
			}

			if ( rsssl_get_option('disable_http_methods' ) !== false ) {
				$rules .= "\n" . "RewriteCond %{REQUEST_METHOD} ^(TRACE|STACK)" . "\n" .
				         "RewriteRule .* - [F]";
			}

            if ( !get_option('disable_user_enumeration') ) {
                $rules .= "RewriteCond %{QUERY_STRING} ^author= [NC]" . "\n" .
                "RewriteRule .* - [F,L]" . "\n" .
                "RewriteRule ^author/ - [F,L]";
            }

			file_put_contents($htaccess_file, $htaccess . $start . $rules . $end);
		}
	}
}

/**
 * @return int
 * Get user ID
 */
function rsssl_get_user_id() {

	if ( is_user_logged_in() ) {
		global $user;

		return $user->ID;
	}

	return 0;
}

/**
 * @return string|null
 * Get the wp-config.php path
 */
function rsssl_find_wp_config_path()
{
    //limit nr of iterations to 20
    $i = 0;
    $maxiterations = 20;
    $dir = dirname(__FILE__);
    do {
        $i++;
        if (file_exists($dir . "/wp-config.php")) {
            return $dir . "/wp-config.php";
        }
    } while (($dir = realpath("$dir/..")) && ($i < $maxiterations));
    return null;
}

/**
 * Returns the server type of the plugin user.
 *
 * @return string|bool server type the user is using of false if undetectable.
 */

function rsssl_get_server() {
	//Allows to override server authentication for testing or other reasons.
	if ( defined( 'RSSSL_SERVER_OVERRIDE' ) ) {
		return RSSSL_SERVER_OVERRIDE;
	}

	$server_raw = strtolower( htmlspecialchars( $_SERVER['SERVER_SOFTWARE'] ) );

	//figure out what server they're using
	if ( strpos( $server_raw, 'apache' ) !== false ) {
		return 'apache';
	} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {
		return 'nginx';
	} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {
		return 'litespeed';
	} else { //unsupported server
		return false;
	}
}

/**
 * @return string
 * Generate a random prefix
 */

function rsssl_generate_random_string($length) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randomString = '';

	for ($i = 0; $i < $length; $i++) {
		$index = rand(0, strlen($characters) - 1);
		$randomString .= $characters[$index];
	}

	return $randomString;
}

/**
 * @return array|bool
 *
 * Get users where display name is login name
 */
function get_users_where_display_name_is_login() {

	$users = rsssl_display_name_equals_login( true );

	if ( $users ) {
		return true;
	}

	return false;
}

/**
 * @return string
 *
 * Get users as string to display
 */
function rsssl_list_users_where_display_name_is_login_name() {

	if ( is_array( get_users_where_display_name_is_login() ) ) {
		return implode( ', ', get_users_where_display_name_is_login() );
	}

	return '';
}
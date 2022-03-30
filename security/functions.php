<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * @return string
 * Delete transients
 */
if ( ! function_exists('rsssl_delete_transients' ) ) {
    function rsssl_delete_transients()
    {
        $transients = array(
	        'rsssl_xmlrpc_allowed',
			'rsssl_wp_version_detected',
			'rsssl_http_options_allowed',
        );

        foreach ( $transients as $transient ) {
            delete_transient( $transient );
        }
    }
}

/**
 * Check if string contains numbers
 */
if ( ! function_exists('rsssl_contains_numbers' ) ) {
	function rsssl_contains_numbers( $string ) {
		return preg_match( '/\\d/', $string ) > 0;
	}
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

			if ( get_option( 'rsssl_sec_disabled_indexing' ) !== false ) {
				$rules .= "\n" . "Options -Indexes";
			}

			if ( get_option('rsssl_disable_http_methods' ) !== false ) {
				$rules .= "\n" . "RewriteCond %{REQUEST_METHOD} ^(TRACE|STACK)" . "\n" .
				         "RewriteRule .* - [F]";
			}

            if ( get_option('rsssl_disable_user_enumeration') !== false ) {
                $rules .= "RewriteCond %{QUERY_STRING} ^author= [NC]" . "\n" .
                "RewriteRule .* - [F,L]" . "\n" .
                "RewriteRule ^author/ - [F,L]";
            }

			file_put_contents($htaccess_file, $htaccess . $start . $rules . $end);
		}
	}
}

/**
 * @return bool
 * Check if WordPress version is above 5.6 for application password support
 */
function rsssl_wordpress_version_above_5_6() {
	global $wp_version;
	if ( $wp_version < 5.6 ) {
		return false;
	}

	return true;
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
 * @param $func
 * @param $is_condition
 *
 * @return string
 * Validate function
 */
function rsssl_validate_function($func, $is_condition = false ){
	$invert = false;
	if (strpos($func, 'NOT ') !== FALSE ) {
		$func = str_replace('NOT ', '', $func);
		$invert = true;
	}

	if ( $func === '_true_') {
		$output = true;
	} else if ( $func === '_false_' ) {
		$output = false;
	} else {
		if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $func, $matches)) {
			$base = $matches[1];
			$class = $matches[2];
			$function = $matches[3];
			$output = call_user_func( array( $base()->{$class}, $function ) );
		} else {
			$output = $func();
		}

		if ( $invert ) {
			$output = !$output;
		}
	}

	//stringify booleans
	if (!$is_condition) {
		if ( $output === false || $output === 0 ) {
			$output = 'false';
		}
		if ( $output === true || $output === 1 ) {
			$output = 'true';
		}
	}
	return sanitize_text_field($output);
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
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
			'rsssl_stack_allowed',
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

function rsssl_wordpress_version_above_5_6() {
	global $wp_version;
	if ( $wp_version < 5.6 ) {
		return false;
	}

	return true;
}

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
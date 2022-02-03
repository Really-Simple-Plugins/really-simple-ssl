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

			error_log($htaccess);
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

			error_log($htaccess . $start . $rules . $end);

			file_put_contents($htaccess_file, $htaccess . $start . $rules . $end);
		}
	}
}
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
if ( ! function_exists('contains_numbers' ) ) {
	function contains_numbers( $string ) {
		return preg_match( '/\\d/', $string ) > 0;
	}
}
if ( ! function_exists('find_wp_config_path' ) ) {
    function find_wp_config_path()
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
}
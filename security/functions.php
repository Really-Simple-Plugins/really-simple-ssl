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

if ( ! function_exists('rsssl_get_abspath' ) ) {
	function rsssl_get_abspath()
	{

		$path = ABSPATH;
		if (is_subdirectory_install()) {
			$siteUrl = site_url();
			$homeUrl = home_url();
			$diff = str_replace($homeUrl, "", $siteUrl);
			$diff = trim($diff, "/");
			$pos = strrpos($path, $diff);
			if ($pos !== false) {
				$path = substr_replace($path, "", $pos, strlen($diff));
				$path = trim($path, "/");
				$path = "/" . $path . "/";
			}
		}

		return $path;

	}
}

function is_subdirectory_install() {
	if (strlen(site_url()) > strlen(home_url())) {
		return true;
	}
	return false;
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

if ( ! function_exists('rsssl_get_server' ) ) {
	function rsssl_get_server() {
		//Allows to override server authentication for testing or other reasons.
		if ( defined( 'RSSSL_SERVER_OVERRIDE' ) ) {
			return RSSSL_SERVER_OVERRIDE;
		}
		$server_raw = strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) );

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
}

if ( ! function_exists('rsssl_htaccess_file' ) ) {
	function rsssl_htaccess_file() {
		if ( rsssl_uses_htaccess_conf() ) {
			$htaccess_file = realpath( dirname( rsssl_get_abspath() ) . "/conf/htaccess.conf" );
		} else {
			$htaccess_file = rsssl_get_abspath() . ".htaccess";
		}

		return $htaccess_file;
	}
}

if ( ! function_exists('rsssl_uses_htaccess_conf') ) {
	function rsssl_uses_htaccess_conf() {
		$htaccess_conf_file = dirname( rsssl_get_abspath() ) . "/conf/htaccess.conf";
		//conf/htaccess.conf can be outside of open basedir, return false if so
		$open_basedir = ini_get( "open_basedir" );

		if ( ! empty( $open_basedir ) ) {
			return false;
		}

		if ( is_file( $htaccess_conf_file ) ) {
			return true;
		} else {
			return false;
		}
	}
}
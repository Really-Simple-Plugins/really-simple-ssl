<?php
defined('ABSPATH') or die("you do not have access to this page!");

class rsssl_letsencrypt {

	private static $_this;

	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl' ), get_class( $this ) ) );
		}

		self::$_this = $this;

	}

	static function this() {
		return self::$_this;
	}

	/**
	 * Create .well-known/acme-challenge directory if not existing
	 * @return bool
	 */
	public function manual_directory_creation_needed() {
		$root_directory = ABSPATH;
		if ( ! file_exists( $root_directory . '/.well-known' ) ) {
			mkdir( $root_directory . '/.well-known' );
		}
		if ( ! file_exists( $root_directory . '/.well-known/acme-challenge' ) ) {
			mkdir( $root_directory . '/.well-known/acme-challenge' );
		}

		if ( file_exists( $root_directory . '/.well-known/challenge' ) ){
			return true;
		} else {
			return false;
		}

	}
}

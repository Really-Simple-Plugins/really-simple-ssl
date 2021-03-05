<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( 'rsssl_server' ) ) {
	class rsssl_server {
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
			}
			self::$_this = $this;
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * @Since 2.5.1
		 * Checks if the server uses .htaccess
		 * @return bool
		 */

		public function uses_htaccess() {
			// No .htaccess on WP Engine
			if ( function_exists( 'is_wpe' ) && is_wpe() ) {
				return false;
			}

			if ( $this->get_server() == "apache" || $this->get_server() == "litespeed" ) {
				return true;
			}

			return false;
		}

		/**
		 * Returns the server type of the plugin user.
		 *
		 * @return string|bool server type the user is using of false if undetectable.
		 */

		public function get_server() {
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
	} //class closure
}

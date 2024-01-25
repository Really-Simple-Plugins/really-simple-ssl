<?php
defined( 'ABSPATH' ) or die( 'you do not have access to this page!' );

if ( ! class_exists( 'rsssl_server' ) ) {
	class rsssl_server {
		private static $_this;

		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( 'you cannot create a second instance.' );
			}
			self::$_this = $this;
		}

		public static function this() {
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

			if ( $this->get_server() === 'apache' || $this->get_server() === 'litespeed' ) {
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

			$server_raw = strtolower( htmlspecialchars( $_SERVER['SERVER_SOFTWARE'] ) );
			if ( strpos( $server_raw, 'apache' ) !== false ) {
				return 'apache';
			} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {
				return 'nginx';
			} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {
				return 'litespeed';
			} elseif ( strpos( $server_raw, 'openresty' ) !== false ) {
				return 'openresty';
			} elseif ( strpos( $server_raw, 'microsoft-iis' ) !== false ) {
				return 'microsoft-iis';
			} else { //unsupported server
				return false;
			}
		}

		/**
		 * Check if the apache version is at least 2.4
		 * @return bool
		 */
		public function apache_version_min_24() {
			$version = $_SERVER['SERVER_SOFTWARE'] ?? false;
			//check if version is higher then 2.4.
			if ( preg_match( '/Apache\/(2\.[4-9])/', $version, $matches ) ) {
				return true;
			}
			return false;
		}
	} //class closure
}

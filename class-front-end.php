<?php
defined( 'ABSPATH' ) or die( 'you do not have access to this page!' );

if ( ! class_exists( 'rsssl_front_end' ) ) {

	class rsssl_front_end {

		private static $_this;
		public $wp_redirect;
		public $ssl_enabled;

		public function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( 'you cannot create a second instance.' );
			}

			self::$_this       = $this;
			$this->ssl_enabled = rsssl_get_option( 'ssl_enabled' );
			$this->wp_redirect = rsssl_get_option( 'redirect', 'redirect' ) === 'wp_redirect';
			add_action( 'rest_api_init', array( $this, 'wp_rest_api_force_ssl' ), ~PHP_INT_MAX );
		}

		public static function this() {
			return self::$_this;
		}

		/**
		 * PHP redirect, when ssl is true.
		 *
		 * @since  2.2
		 *
		 * @access public
		 *
		 */

		public function force_ssl() {
			if ( $this->ssl_enabled && $this->wp_redirect ) {
				add_action( 'wp', array( $this, 'wp_redirect_to_ssl' ), 40, 3 );
			}
		}


		/**
		 * Force SSL on wp rest api
		 *
		 * @since  2.5.14
		 *
		 * @access public
		 *
		 */

		public function wp_rest_api_force_ssl(): void {
			//check for Command Line
			if ( php_sapi_name() === 'cli' ) {
				return;
			}

			if ( ! array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
				return;
			}

			if ( $this->ssl_enabled && ! is_ssl() && ! ( defined( 'rsssl_no_rest_api_redirect' ) && rsssl_no_rest_api_redirect ) ) {
				$redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				wp_redirect( $redirect_url, 301 );
				exit;
			}
		}


		/**
		 * Redirect using wp redirect
		 *
		 * @since  2.5.0
		 *
		 * @access public
		 *
		 */

		public function wp_redirect_to_ssl(): void {
			if ( ! array_key_exists( 'HTTP_HOST', $_SERVER ) ) {
				return;
			}

			if ( ! is_ssl() && ! ( defined( 'rsssl_no_wp_redirect' ) && rsssl_no_wp_redirect ) ) {
				$redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$redirect_url = apply_filters( 'rsssl_wp_redirect_url', $redirect_url );
				wp_redirect( $redirect_url, 301, 'WordPress - Really Simple Security' );
				exit;
			}
		}
	}
}

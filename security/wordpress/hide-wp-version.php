<?php
defined( 'ABSPATH' ) or die();
if ( ! class_exists( 'rsssl_hide_wp_version' ) ) {
	class rsssl_hide_wp_version {
		private static $_this;
		public $new_version = false;
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( "you cannot create a second instance of a singleton class" );
			}

			self::$_this = $this;
			add_action( 'init', array($this, 'remove_wp_version') );
			add_filter( 'rsssl_fixer_output', array( $this, 'replace_wp_version') );
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Remove WordPress version info from page source
		 *
		 * @return void
		 */
		public function remove_wp_version() {
			// remove <meta name="generator" content="WordPress VERSION" />
			add_filter( 'the_generator', function () {
				return '';
			} );
			// remove WP ?ver=5.X.X from css/js
			add_filter( 'style_loader_src', array( $this, 'remove_css_js_version' ), 9999 );
			add_filter( 'script_loader_src', array ($this, 'remove_css_js_version'), 9999 );
			remove_action( 'wp_head', 'wp_generator' ); // remove wordpress version
			remove_action( 'wp_head', 'index_rel_link' ); // remove link to index page
			remove_action( 'wp_head', 'wlwmanifest_link' ); // remove wlwmanifest.xml (needed to support windows live writer)
			remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 ); // Remove shortlink
		}

		/**
		 * Generate a random version number
		 *
		 * @return string
		 */
		public function generate_rand_version() {
			if ( !$this->new_version) {
				$wp_version = get_bloginfo( 'version' );
				$token      = get_option( 'rsssl_wp_version_token' );
				if ( ! $token ) {
					$token = str_shuffle( time() );
					update_option( 'rsssl_wp_version_token', $token );
				}

				$this->new_version = hash( 'md5', $token );
			}
			return $this->new_version;
		}

		/**
		 * @param string $html
		 *
		 * @return string
		 *
		 */
		public function replace_wp_version( $html ) {
			$wp_version  = get_bloginfo( 'version' );
			$new_version = $this->generate_rand_version();

			return str_replace( '?ver=' . $wp_version, '?ver=' . $new_version, $html );
		}

		/**
		 * @param $src
		 *
		 * @return mixed|string
		 * Remove WordPress version from css and js strings
		 */
		public function remove_css_js_version( $src ) {
			if ( empty($src) ) {
				return $src;
			}

			if ( strpos( $src, '?ver=' ) && strpos( $src, 'wp-includes' ) ) {
				$wp_version  = get_bloginfo( 'version' );
				$new_version = $this->generate_rand_version();
				$src         = str_replace( '?ver=' . $wp_version, '?ver=' . $new_version, $src );
			}

			return $src;
		}
	}
}
RSSSL_SECURITY()->components['hide-wp-version'] = new rsssl_hide_wp_version();
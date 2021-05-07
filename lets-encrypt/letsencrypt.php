<?php
defined('ABSPATH') or die("you do not have access to this page!");
if (rsssl_letsencrypt_generation_allowed()) {

	class RSSSL_LETSENCRYPT {
		private static $instance;

		public $wizard;
		public $field;
		public $config;
		public $letsencrypt_handler;

		private function __construct() {

		}

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RSSSL_LETSENCRYPT ) ) {
				self::$instance = new RSSSL_LETSENCRYPT;
				self::$instance->setup_constants();
				self::$instance->includes();
				if ( is_admin() ) {
					self::$instance->field               = new rsssl_field();
					self::$instance->wizard              = new rsssl_wizard();
					self::$instance->config              = new rsssl_config();
					self::$instance->letsencrypt_handler = new rsssl_letsencrypt_handler();
				}
				self::$instance->hooks();
			}

			return self::$instance;
		}

		private function setup_constants() {

		}

		private function includes() {
			if ( is_admin() ) {
				require_once( rsssl_path . 'lets-encrypt/wizard/assets/icons.php' );
				require_once( rsssl_path . 'lets-encrypt/wizard/class-field.php' );
				require_once( rsssl_path . 'lets-encrypt/wizard/class-wizard.php' );
				require_once( rsssl_path . 'lets-encrypt/wizard/config/class-config.php' );
				require_once( rsssl_path . 'lets-encrypt/class-letsencrypt-handler.php' );
			}
		}

		private function hooks() {


		}

		/**
		 * Notice about possible compatibility issues with add ons
		 */
		public static function admin_notices() {

		}
	}

	function RSSSL_LE() {
		return RSSSL_LETSENCRYPT::instance();
	}

	add_action( 'plugins_loaded', 'RSSSL_LE', 9 );
}


/**
 * Capability handling for lets encrypt
 * @return bool
 */
function rsssl_letsencrypt_generation_allowed(){
	if (current_user_can('manage_options') || wp_doing_cron() ) return true;

	return false;
}